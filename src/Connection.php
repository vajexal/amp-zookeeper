<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\EncryptableSocket;
use Amp\Socket\Socket;
use Closure;
use LogicException;
use Psr\Log\LoggerInterface;
use Vajexal\AmpZookeeper\Exception\KeeperException;
use Vajexal\AmpZookeeper\Proto\ConnectRequest;
use Vajexal\AmpZookeeper\Proto\ConnectResponse;
use Vajexal\AmpZookeeper\Proto\ReplyHeader;
use Vajexal\AmpZookeeper\Proto\RequestHeader;
use Vajexal\AmpZookeeper\Proto\WatcherEvent;
use function Amp\call;
use function Amp\Socket\connect;

class Connection
{
    private const NOTIFICATION_XID = -1;
    private const PING_XID         = -2;

    private const MAX_SEND_PING_INTERVAL = 10000;

    /** @var Packet[] */
    private array           $queue = [];
    private int             $xid   = 0;
    private Socket          $socket;
    private LoggerInterface $logger;

    private int    $sessionTimeout = 0;
    private string $pingWatcherId  = '';
    private int    $maxIdleTime    = 0;
    private int    $lastSend       = 0;

    private string    $waitForRecordClass    = '';
    private ?Deferred $waitForRecordDeferred = null;

    private ?Closure $watcher;

    private function __construct()
    {
    }

    public static function connect(ConnectStringParser $connectStringParser, ZookeeperConfig $config): Promise
    {
        return call(function () use ($connectStringParser, $config) {
            $connection = new self;

            $connection->logger  = $config->getLogger();
            $connection->watcher = $config->getWatcher();

            /** @var EncryptableSocket $socket */
            $connection->socket = yield connect($connectStringParser->getRandomServer()->getAuthority());

            yield $connection->sendConnectRequest($config);

            Promise\rethrow($connection->listenForPackets());

            /** @var ConnectResponse $response */
            $response = yield $connection->waitForRecord(ConnectResponse::class);

            $connection->sessionTimeout = $response->getTimeOut();
            $connection->setupPing();

            return $connection;
        });
    }

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

            $requestHeader = new RequestHeader(OpCode::CLOSE_SESSION);
            $packet        = new Packet($requestHeader);

            yield $this->writePacket($packet);
        });
    }

    public function writePacket(Packet $packet): Promise
    {
        return call(function () use ($packet) {
            if ($packet->requestHeader) {
                $packet->requestHeader->setXid($this->xid++);
            }

            yield $this->socket->write((string) $packet->getBB());
            $this->logger->debug('write: ' . $packet->getBB()->toHex());

            $this->updateLastSend();

            if (!$packet->requestHeader || $packet->requestHeader->getXid() < 0) {
                return null;
            }

            $packet->deferred                              = new Deferred;
            $this->queue[$packet->requestHeader->getXid()] = $packet;
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
        $requestHeader = new RequestHeader(OpCode::PING, self::PING_XID);
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
            $response = $this->deserializeRecord($this->waitForRecordClass, $bb);
            $this->waitForRecordDeferred->resolve($response);
            $this->waitForRecordClass    = '';
            $this->waitForRecordDeferred = null;
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

        $response = $this->deserializeRecord($packet->responseClass, $bb);

        $packet->deferred->resolve($response);
    }

    private function deserializeRecord(?string $recordClass, ByteBuffer $bb): ?Record
    {
        if ($recordClass === null) {
            return null;
        }

        if (!\is_subclass_of($recordClass, Record::class)) {
            throw new LogicException(\sprintf('%s does not implement Record interface', $recordClass));
        }

        return $recordClass::deserialize($bb);
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
