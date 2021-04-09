<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Exception;

class ConnectStringParserException extends ZookeeperException
{
    public const EMPTY_CONNECT_STRING_CODE = 0;
    public const EMPTY_SERVERS_LIST_CODE   = 1;

    public static function emptyConnectString(): self
    {
        return new self('Connect string must not be empty', self::EMPTY_CONNECT_STRING_CODE);
    }

    public static function emptyServersList(): self
    {
        return new self('Servers list must not be empty', self::EMPTY_SERVERS_LIST_CODE);
    }
}
