<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Amp\Promise;
use Vajexal\AmpZookeeper\Data\Stat;
use Vajexal\AmpZookeeper\Exception\KeeperException;
use Vajexal\AmpZookeeper\Proto\AddWatchRequest;
use Vajexal\AmpZookeeper\Proto\CreateRequest;
use Vajexal\AmpZookeeper\Proto\CreateResponse;
use Vajexal\AmpZookeeper\Proto\DeleteRequest;
use Vajexal\AmpZookeeper\Proto\ErrorResponse;
use Vajexal\AmpZookeeper\Proto\ExistsRequest;
use Vajexal\AmpZookeeper\Proto\ExistsResponse;
use Vajexal\AmpZookeeper\Proto\GetChildrenRequest;
use Vajexal\AmpZookeeper\Proto\GetChildrenResponse;
use Vajexal\AmpZookeeper\Proto\GetDataRequest;
use Vajexal\AmpZookeeper\Proto\GetDataResponse;
use Vajexal\AmpZookeeper\Proto\GetEphemeralsRequest;
use Vajexal\AmpZookeeper\Proto\GetEphemeralsResponse;
use Vajexal\AmpZookeeper\Proto\RemoveWatchesRequest;
use Vajexal\AmpZookeeper\Proto\RequestHeader;
use Vajexal\AmpZookeeper\Proto\SetDataRequest;
use Vajexal\AmpZookeeper\Proto\SetDataResponse;
use Vajexal\AmpZookeeper\Proto\SyncRequest;
use Vajexal\AmpZookeeper\Proto\SyncResponse;
use function Amp\call;

class Zookeeper
{
    private Connection $connection;
    private string     $chrootPath = '';

    private function __construct()
    {
    }

    public function __destruct()
    {
        Promise\rethrow($this->close());
    }

    /**
     * @param Config $config
     * @return Promise<self>
     */
    public static function connect(Config $config): Promise
    {
        return call(function () use ($config) {
            $zk = new self;

            $connectStringParser = new ConnectStringParser($config->getConnectString());
            $zk->chrootPath      = $connectStringParser->getChrootPath();

            $zk->connection = yield Connection::connect($connectStringParser, $config);

            return $zk;
        });
    }

    /**
     * @return Promise<void>
     */
    public function close(): Promise
    {
        return $this->connection->close();
    }

    /**
     * @param string $path
     * @param string $data
     * @param int $createMode
     * @return Promise<string>
     */
    public function create(string $path, string $data, int $createMode = CreateMode::PERSISTENT): Promise
    {
        return call(function () use ($path, $data, $createMode) {
            CreateMode::validate($createMode);
            PathUtils::validatePath($path, CreateMode::isSequential($createMode));

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::CREATE);
            $request       = new CreateRequest($serverPath, $data, Ids::openACLUnsafe(), $createMode);
            $packet        = new Packet($requestHeader, $request, CreateResponse::class);

            try {
                /** @var CreateResponse $response */
                $response = yield $this->connection->writePacket($packet);

                return $this->chrootPath ? \mb_substr($response->getPath(), \mb_strlen($this->chrootPath)) : $response->getPath();
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
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

            $serverPath = $path === '/' ? $path : $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::DELETE);
            $request       = new DeleteRequest($serverPath, -1);
            $packet        = new Packet($requestHeader, $request);

            try {
                yield $this->connection->writePacket($packet);
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
        });
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

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::EXISTS);
            $request       = new ExistsRequest($serverPath, $watch);
            $packet        = new Packet($requestHeader, $request, ExistsResponse::class);

            try {
                /** @var ExistsResponse $response */
                $response = yield $this->connection->writePacket($packet);

                return $response->getStat()->getCzxid() !== -1;
            } catch (KeeperException $e) {
                if ($e->getCode() === KeeperException::NO_NODE) {
                    return false;
                }

                throw $e->withPath($path);
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

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::GET_DATA);
            $request       = new GetDataRequest($serverPath, $watch);
            $packet        = new Packet($requestHeader, $request, GetDataResponse::class);

            try {
                /** @var GetDataResponse $response */
                $response = yield $this->connection->writePacket($packet);

                return $response->getData();
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
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

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::GET_DATA);
            $request       = new GetDataRequest($serverPath, $watch);
            $packet        = new Packet($requestHeader, $request, GetDataResponse::class);

            try {
                /** @var GetDataResponse $response */
                $response = yield $this->connection->writePacket($packet);

                return $response->getStat();
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
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

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::SET_DATA);
            $request       = new SetDataRequest($serverPath, $data, -1);
            $packet        = new Packet($requestHeader, $request, SetDataResponse::class);

            try {
                yield $this->connection->writePacket($packet);
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
        });
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

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::GET_CHILDREN);
            $request       = new GetChildrenRequest($serverPath, $watch);
            $packet        = new Packet($requestHeader, $request, GetChildrenResponse::class);

            try {
                /** @var GetChildrenResponse $response */
                $response = yield $this->connection->writePacket($packet);

                return $response->getChildren();
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
        });
    }

    /**
     * @param string $path
     * @return Promise<void>
     */
    public function sync(string $path): Promise
    {
        return call(function () use ($path) {
            PathUtils::validatePath($path);

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::SYNC);
            $request       = new SyncRequest($serverPath);
            $packet        = new Packet($requestHeader, $request, SyncResponse::class);

            try {
                yield $this->connection->writePacket($packet);
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
        });
    }

    /**
     * @param string $path
     * @param int $mode
     * @return Promise<void>
     */
    public function addWatch(string $path, int $mode = AddWatchMode::PERSISTENT): Promise
    {
        return call(function () use ($path, $mode) {
            AddWatchMode::validate($mode);
            PathUtils::validatePath($path);

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::ADD_WATCH);
            $request       = new AddWatchRequest($serverPath, $mode);
            $packet        = new Packet($requestHeader, $request, ErrorResponse::class);

            try {
                yield $this->connection->writePacket($packet);
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
        });
    }

    /**
     * @param string $path
     * @param int $watcherType
     * @return Promise<void>
     */
    public function removeWatches(string $path, int $watcherType = WatcherType::ANY): Promise
    {
        return call(function () use ($path, $watcherType) {
            WatcherType::validate($watcherType);
            PathUtils::validatePath($path);

            $serverPath = $this->prependChroot($path);

            $requestHeader = new RequestHeader(OpCode::REMOVE_WATCHES);
            $request       = new RemoveWatchesRequest($serverPath, $watcherType);
            $packet        = new Packet($requestHeader, $request);

            try {
                yield $this->connection->writePacket($packet);
            } catch (KeeperException $e) {
                throw $e->withPath($path);
            }
        });
    }

    /**
     * @param string $prefixPath
     * @return Promise<array>
     */
    public function getEphemerals(string $prefixPath = '/'): Promise
    {
        return call(function () use ($prefixPath) {
            PathUtils::validatePath($prefixPath);

            $serverPath = $this->prependChroot($prefixPath);

            $requestHeader = new RequestHeader(OpCode::GET_EPHEMERALS);
            $request       = new GetEphemeralsRequest($serverPath);
            $packet        = new Packet($requestHeader, $request, GetEphemeralsResponse::class);

            try {
                /** @var GetEphemeralsResponse $response */
                $response = yield $this->connection->writePacket($packet);

                return $response->getEphemerals();
            } catch (KeeperException $e) {
                throw $e->withPath($prefixPath);
            }
        });
    }

    private function prependChroot(string $path): string
    {
        if (!$this->chrootPath) {
            return $path;
        }

        if (\mb_strlen($path) === 1) {
            return $this->chrootPath;
        }

        return $this->chrootPath . $path;
    }

    public function getSessionTimeout(): int
    {
        return $this->connection->getSessionTimeout();
    }
}
