<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Amp\Promise;
use Psr\Log\LoggerInterface;

class ZookeeperConnector
{
    private Config $config;

    public function __construct()
    {
        $this->config = new Config;
    }

    public function connectString(string $connectString): self
    {
        $this->config->connectString($connectString);

        return $this;
    }

    public function sessionTimeout(int $sessionTimeout): self
    {
        $this->config->sessionTimeout($sessionTimeout);

        return $this;
    }

    public function logger(LoggerInterface $logger): self
    {
        $this->config->logger($logger);

        return $this;
    }

    public function watcher(callable $watcher): self
    {
        $this->config->watcher($watcher);

        return $this;
    }

    public function connect(): Promise
    {
        return Zookeeper::connect($this->config);
    }
}
