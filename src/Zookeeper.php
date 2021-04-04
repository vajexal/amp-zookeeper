<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Amp\Deferred;
use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use Amp\Socket\Socket;
use LogicException;
use Psr\Log\LoggerInterface;
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
use Vajexal\AmpZookeeper\Proto\ReplyHeader;
use Vajexal\AmpZookeeper\Proto\RequestHeader;
use Vajexal\AmpZookeeper\Proto\SetDataRequest;
use Vajexal\AmpZookeeper\Proto\SetDataResponse;
use function Amp\call;
use function Amp\Socket\connect;

class Zookeeper
{
    /** @var Packet[] */
    private array           $queue;
    private int             $xid = 0;
    private Socket          $socket;
    private LoggerInterface $logger;

    private function __construct()
    {
    }

    /**
     * @param ZookeeperConfig $config
     * @return Promise<self>
     */
    public static function connect(ZookeeperConfig $config): Promise
    {
        return call(function () use ($config) {
            $zk = new self;

            $zk->logger = $config->getLogger();

            // todo parse connection string
            // todo ping

            /** @var EncryptableSocket $socket */
            $zk->socket = yield connect($config->getConnectString());

            $request = new ConnectRequest(0, 0, $config->getSessionTimeout(), 0, \str_repeat("\0", 16));
            $packet  = new Packet(null, $request);

            $zk->writePacket($packet);
            yield $zk->waitForRecord(ConnectResponse::class);
            $zk->queue = [];

            $zk->listenForPackets();

            return $zk;
        });
    }

    /**
     * @return Promise<void>
     */
    public function close(): Promise
    {
        return call(function () {
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
     * @return Promise<void>
     */
    public function create(string $path, string $data): Promise
    {
        return call(function () use ($path, $data) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::CREATE);
            $request       = new CreateRequest($path, $data, Ids::openACLUnsafe(), 0); // persistent mode
            $packet        = new Packet($requestHeader, $request, CreateResponse::class);

            yield $this->writePacket($packet);
        });
    }

    /**
     * @param string $path
     * @return Promise<void>
     */
    public function delete(string $path): Promise
    {
        return call(function () use ($path) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::DELETE);
            $request       = new DeleteRequest($path, -1);
            $packet        = new Packet($requestHeader, $request);

            yield $this->writePacket($packet);
        });
    }

    /**
     * @param string $path
     * @return Promise<bool>
     */
    public function exists(string $path): Promise
    {
        return call(function () use ($path) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::EXISTS);
            $request       = new ExistsRequest($path, false);
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
        return call(function () use ($path) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::GET_DATA);
            $request       = new GetDataRequest($path, false);
            $packet        = new Packet($requestHeader, $request, GetDataResponse::class);

            /** @var GetDataResponse $response */
            $response = yield $this->writePacket($packet);

            return $response->getData();
        });
    }

    /**
     * @param string $path
     * @param string $data
     * @return Promise<void>
     */
    public function set(string $path, string $data): Promise
    {
        return call(function () use ($path, $data) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::SET_DATA);
            $request       = new SetDataRequest($path, $data, -1);
            $packet        = new Packet($requestHeader, $request, SetDataResponse::class);

            yield $this->writePacket($packet);
        });
    }

    public function getChildren(string $path): Promise
    {
        return call(function () use ($path) {
            PathUtils::validatePath($path);

            $requestHeader = new RequestHeader($this->xid, OpCode::GET_CHILDREN);
            $request       = new GetChildrenRequest($path, false);
            $packet        = new Packet($requestHeader, $request, GetChildrenResponse::class);

            /** @var GetChildrenResponse $response */
            $response = yield $this->writePacket($packet);

            return $response->getChildren();
        });
    }

    private function writePacket(Packet $packet): Promise
    {
        return call(function () use ($packet) {
            yield $this->socket->write((string) $packet->getBB());
            $this->logger->debug('write: ' . $packet->getBB()->toHex());

            $packet->deferred = new Deferred;

            $this->queue[$this->xid++] = $packet;

            return $packet->deferred->promise();
        });
    }

    /**
     * @return Promise<void>
     */
    private function listenForPackets(): Promise
    {
        return call(function () {
            while (($data = yield $this->socket->read()) !== null) {
                if (!$data) {
                    continue;
                }

                $this->readPacket($data);
            }
        });
    }

    private function readPacket(string $data): void
    {
        $bb = new ByteBuffer($data);

        $this->logger->debug('read: ' . $bb->toHex());

        $bb->readInt(); // todo read exact bytes from $data
        $replyHeader = ReplyHeader::deserialize($bb);

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
        if (!\is_subclass_of($recordClass, Record::class)) {
            throw new LogicException(\sprintf('Expected instance of %s, got %s', Record::class, $recordClass));
        }

        return call(function () use ($recordClass) {
            while (($data = yield $this->socket->read()) !== null) {
                if (!$data) {
                    continue;
                }

                $bb = new ByteBuffer($data);

                $this->logger->debug('read: ' . $bb->toHex());

                $bb->readInt(); // todo read exact bytes from $data
                return $recordClass::deserialize($bb);
            }

            throw new KeeperException(\sprintf('Did not wait for %s', $recordClass));
        });
    }
}
