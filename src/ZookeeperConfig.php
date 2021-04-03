<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ZookeeperConfig
{
    private string          $connectString  = '127.0.0.1:2181';
    private int             $sessionTimeout = 5000;
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger;
    }

    public function getConnectString(): string
    {
        return $this->connectString;
    }

    public function connectString(string $connectString): self
    {
        $this->connectString = $connectString;

        return $this;
    }

    public function getSessionTimeout(): int
    {
        return $this->sessionTimeout;
    }

    public function sessionTimeout(int $sessionTimeout): self
    {
        $this->sessionTimeout = $sessionTimeout;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function logger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
