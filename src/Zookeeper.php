<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use Amp\Socket\Socket;
use Closure;
use Psr\Log\LoggerInterface;
use Vajexal\AmpZookeeper\Data\Stat;
use Vajexal\AmpZookeeper\Exception\KeeperException;
use Vajexal\AmpZookeeper\Proto\ConnectRequest;
use Vajexal\AmpZookeeper\Proto\ConnectResponse;
use Vajexal\AmpZookeeper\Proto\CreateRequest;
use Vajexal\AmpZookeeper\Proto\CreateResponse;
use Vajexal\AmpZookeeper\Proto\DeleteRequest;
use Vajexal\AmpZookeeper\Proto\ExistsRequest;
use Vajexal\AmpZookeeper\Proto\ExistsResponse;
use Vajexal\AmpZookeeper\Proto\GetChildrenRequest;
use Vajexal\AmpZookeeper\Proto\GetChildrenResponse;
use Vajexal\AmpZookeeper\Proto\GetDataRequest;
use Vajexal\AmpZookeeper\Proto\GetDataResponse;
use Vajexal\AmpZookeeper\Proto\GetEphemeralsRequest;
use Vajexal\AmpZookeeper\Proto\GetEphemeralsResponse;
use Vajexal\AmpZookeeper\Proto\RemoveWatchesRequest;
use Vajexal\AmpZookeeper\Proto\ReplyHeader;
use Vajexal\AmpZookeeper\Proto\RequestHeader;
use Vajexal\AmpZookeeper\Proto\SetDataRequest;
use Vajexal\AmpZookeeper\Proto\SetDataResponse;
use Vajexal\AmpZookeeper\Proto\SyncRequest;
use Vajexal\AmpZookeeper\Proto\SyncResponse;
use Vajexal\AmpZookeeper\Proto\WatcherEvent;
use function Amp\call;
use function Amp\Socket\connect;

class Zookeeper
{
    private const NOTIFICATION_XID = -1;
    private const PING_XID         = -2;

    private const MAX_SEND_PING_INTERVAL = 10000;

    /** @var Packet[] */
    private array           $queue                 = [];
    private int             $xid                   = 0;
    private Socket          $socket;
    private LoggerInterface $logger;
    private int             $sessionTimeout        = 0;
    private string          $pingWatcherId         = '';
    private int             $maxIdleTime           = 0;
    private int             $lastSend              = 0;
    private string          $waitForRecordClass    = '';
    private ?Deferred       $waitForRecordDeferred = null;
    private ?Closure        $watcher;

    private function __construct()
    {
    }

    /**
     * @param ZookeeperConfig|null $config
     * @return Promise<self>
     */
    public static function connect(ZookeeperConfig $config = null): Promise
    {
        $config ??= new ZookeeperConfig;

        return call(function () use ($config) {
            $zk = new self;

            $zk->logger  = $config->getLogger();
            $zk->watcher = $config->getWatcher();

            $connectStringParser = new ConnectStringParser($config->getConnectString());

            /** @var EncryptableSocket $socket */
            $zk->socket = yield connect($connectStringParser->getRandomServer()->getAuthority());

            yield $zk->sendConnectRequest($config);

            Promise\rethrow($zk->listenForPackets());

            /** @var ConnectResponse $response */
            $response = yield $zk->waitForRecord(ConnectResponse::class);

            $zk->sessionTimeout = $response->getTimeOut();
            $zk->setupPing();

            return $zk;
        });
    }

    /**
     * @return Promise<void>
     */
    public function close(): Promise
    {
        return call(function () {
            if ($this->pingWatcherId) {
                Loop::cancel($this->pingWatcherId);
                $this->pingWatcherId = '';
            }

            if ($this->socket->isClosed()) {
                return;
            }

            $requestHeader = new RequestHeader($this->xid, OpCode::CLOSE_SESSION);
            $packet        = new Packet($requestHeader);

            yield $this->writePacket($packet);
        });
    }

    /**
     * @param string $path
     * @param string $data
     * @param int $createMode
     * @return Promise<void>
     */
    public function create(string $path, string $data, int $createMode = CreateMode::PERSISTENT): Promise
    {
        CreateMode::validate($createMode);
        PathUtils::validatePath($path, CreateMode::isSequential($createMode));

        $requestHeader = new RequestHeader($this->xid, OpCode::CREATE);
        $request       = new CreateRequest($path, $data, Ids::openACLUnsafe(), $createMode);
        $packet        = new Packet($requestHeader, $request, CreateResponse::class);

        return $this->writePacket($packet);
    }

    /**
     * @param string $path
     * @return Promise<void>
     */
    public function delete(string $path): Promise
    {
        PathUtils::validatePath($path);

        $requestHeader = new RequestHeader($this->xid, OpCode::DELETE);
        $request       = new DeleteRequest($path, -1);
        $packet        = new Packet($requestHeader, $request);

        return $this->writePacket($packet);
    }

    /**
     * @param string $path
     * @param bool $watch
     * @return Promise<bool>
     */
    public function exists(string $path, bool $watch = false): Promise
    {
        return call(function () use ($path, $watch) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::EXISTS);
            $request       = new ExistsRequest($path, $watch);
            $packet        = new Packet($requestHeader, $request, ExistsResponse::class);

            try {
                /** @var ExistsResponse $response */
                $response = yield $this->writePacket($packet);

                return $response->getStat()->getCzxid() !== -1;
            } catch (KeeperException $e) {
                if ($e->getCode() === KeeperException::NO_NODE) {
                    return false;
                }

                throw $e;
            }
        });
    }

    /**
     * @param string $path
     * @param bool $watch
     * @return Promise<string>
     */
    public function get(string $path, bool $watch = false): Promise
    {
        return call(function () use ($path, $watch) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::GET_DATA);
            $request       = new GetDataRequest($path, $watch);
            $packet        = new Packet($requestHeader, $request, GetDataResponse::class);

            /** @var GetDataResponse $response */
            $response = yield $this->writePacket($packet);

            return $response->getData();
        });
    }

    /**
     * @param string $path
     * @param bool $watch
     * @return Promise<Stat>
     */
    public function stat(string $path, bool $watch = false): Promise
    {
        return call(function () use ($path, $watch) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::GET_DATA);
            $request       = new GetDataRequest($path, $watch);
            $packet        = new Packet($requestHeader, $request, GetDataResponse::class);

            /** @var GetDataResponse $response */
            $response = yield $this->writePacket($packet);

            return $response->getStat();
        });
    }

    /**
     * @param string $path
     * @param string $data
     * @return Promise<void>
     */
    public function set(string $path, string $data): Promise
    {
        PathUtils::validatePath($path);

        $requestHeader = new RequestHeader($this->xid, OpCode::SET_DATA);
        $request       = new SetDataRequest($path, $data, -1);
        $packet        = new Packet($requestHeader, $request, SetDataResponse::class);

        return $this->writePacket($packet);
    }

    /**
     * @param string $path
     * @param bool $watch
     * @return Promise<array>
     */
    public function getChildren(string $path, bool $watch = false): Promise
    {
        return call(function () use ($path, $watch) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::GET_CHILDREN);
            $request       = new GetChildrenRequest($path, $watch);
            $packet        = new Packet($requestHeader, $request, GetChildrenResponse::class);

            /** @var GetChildrenResponse $response */
            $response = yield $this->writePacket($packet);

            return $response->getChildren();
        });
    }

    public function sync(string $path): Promise
    {
        PathUtils::validatePath($path);

        $requestHeader = new RequestHeader($this->xid, OpCode::SYNC);
        $request       = new SyncRequest($path);
        $packet        = new Packet($requestHeader, $request, SyncResponse::class);

        return $this->writePacket($packet);
    }

    public function removeWatches(string $path, int $watcherType = WatcherType::ANY): Promise
    {
        WatcherType::validate($watcherType);
        PathUtils::validatePath($path);

        $requestHeader = new RequestHeader($this->xid, OpCode::REMOVE_WATCHES);
        $request       = new RemoveWatchesRequest($path, $watcherType);
        $packet        = new Packet($requestHeader, $request);

        return $this->writePacket($packet);
    }

    public function getEphemerals(string $prefixPath = '/'): Promise
    {
        return call(function () use ($prefixPath) {
            PathUtils::validatePath($prefixPath);

            $requestHeader = new RequestHeader($this->xid, OpCode::GET_EPHEMERALS);
            $request       = new GetEphemeralsRequest($prefixPath);
            $packet        = new Packet($requestHeader, $request, GetEphemeralsResponse::class);

            /** @var GetEphemeralsResponse $response */
            $response = yield $this->writePacket($packet);

            return $response->getEphemerals();
        });
    }

    private function writePacket(Packet $packet): Promise
    {
        return call(function () use ($packet) {
            yield $this->socket->write((string) $packet->getBB());
            $this->logger->debug('write: ' . $packet->getBB()->toHex());

            $this->updateLastSend();

            if (!$packet->requestHeader || $packet->requestHeader->getXid() < 0) {
                return null;
            }

            $packet->deferred          = new Deferred;
            $this->queue[$this->xid++] = $packet;
            return $packet->deferred->promise();
        });
    }

    private function setupPing(): void
    {
        $this->maxIdleTime = (int) ($this->sessionTimeout * 2 / 3);

        $this->pingWatcherId = Loop::repeat($this->sessionTimeout / 4, function () {
            $idle = Loop::now() - $this->lastSend;

            if ($idle > $this->maxIdleTime || $idle > self::MAX_SEND_PING_INTERVAL) {
                $this->sendPing();
            }
        });

        Loop::unreference($this->pingWatcherId);
    }

    private function sendPing(): Promise
    {
        $requestHeader = new RequestHeader(self::PING_XID, OpCode::PING);
        $packet        = new Packet($requestHeader);

        return $this->writePacket($packet);
    }

    private function sendConnectRequest(ZookeeperConfig $config): Promise
    {
        $request = new ConnectRequest(0, 0, $config->getSessionTimeout(), 0, \str_repeat("\0", 16));
        $packet  = new Packet(null, $request);

        return $this->writePacket($packet);
    }

    private function listenForPackets(): Promise
    {
        return call(function () {
            while (($data = yield $this->socket->read()) !== null) {
                if (!$data) {
                    continue;
                }

                $bb = new ByteBuffer($data);

                $this->logger->debug('read: ' . $bb->toHex());

                while ($bb->valid()) {
                    $bb->readInt(); // len

                    $this->readPacket($bb);
                }
            }
        });
    }

    private function readPacket(ByteBuffer $bb): void
    {
        if ($this->waitForRecordClass) {
            $response = \is_subclass_of($this->waitForRecordClass, Record::class) ?
                ($this->waitForRecordClass)::deserialize($bb) :
                null;
            $this->waitForRecordDeferred->resolve($response);
            $this->waitForRecordClass = '';
            return;
        }

        $replyHeader = ReplyHeader::deserialize($bb);

        if ($replyHeader->getXid() === self::PING_XID) {
            $this->logger->debug('Got ping response');
            return;
        }

        if ($replyHeader->getXid() === self::NOTIFICATION_XID) {
            $event = WatcherEvent::deserialize($bb);
            $this->logger->debug(\sprintf('Got notification %d, %d, %s', $event->getType(), $event->getState(), $event->getPath()));
            if ($this->watcher) {
                ($this->watcher)($event);
            }
            return;
        }

        if (empty($this->queue[$replyHeader->getXid()])) {
            $this->logger->error(\sprintf('Could not find packet with xid %d', $replyHeader->getXid()));
            return;
        }

        $packet = $this->queue[$replyHeader->getXid()];
        unset($this->queue[$replyHeader->getXid()]);

        if ($replyHeader->getErr()) {
            $this->logger->error(\sprintf('Got reply header error %d', $replyHeader->getErr()));
            $packet->deferred->fail(KeeperException::create($replyHeader->getErr()));
            return;
        }

        $response = $packet->responseClass && \is_subclass_of($packet->responseClass, Record::class) ?
            ($packet->responseClass)::deserialize($bb) :
            null;

        $packet->deferred->resolve($response);
    }

    private function waitForRecord(string $recordClass): Promise
    {
        $this->waitForRecordClass    = $recordClass;
        $this->waitForRecordDeferred = new Deferred;

        return $this->waitForRecordDeferred->promise();
    }

    private function updateLastSend(): void
    {
        $this->lastSend = Loop::now();
    }

    public function getSessionTimeout(): int
    {
        return $this->sessionTimeout;
    }
}
