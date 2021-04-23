<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Exception;

use Exception;

class ZookeeperException extends Exception
{
    public const CONNECTION_CLOSED_CODE = 0;

    public static function connectionClosed(): self
    {
        return new self('Connection closed', self::CONNECTION_CLOSED_CODE);
    }
}
