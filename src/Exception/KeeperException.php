<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Exception;

use Throwable;

class KeeperException extends ZookeeperException
{
    public const OK                               = 0;
    public const SYSTEM_ERROR                     = -1;
    public const RUNTIME_INCONSISTENCY            = -2;
    public const DATA_INCONSISTENCY               = -3;
    public const CONNECTION_LOSS                  = -4;
    public const MARSHALLING_ERROR                = -5;
    public const UNIMPLEMENTED                    = -6;
    public const OPERATION_TIMEOUT                = -7;
    public const BAD_ARGUMENTS                    = -8;
    public const NEW_CONFIG_NO_QUORUM             = -13;
    public const RECONFIG_IN_PROGRESS             = -14;
    public const UNKNOWN_SESSION                  = -12;
    public const API_ERROR                        = -100;
    public const NO_NODE                          = -101;
    public const NO_AUTH                          = -102;
    public const BAD_VERSION                      = -103;
    public const NO_CHILDREN_FOR_EPHEMERALS       = -108;
    public const NODE_EXISTS                      = -110;
    public const NOT_EMPTY                        = -111;
    public const SESSION_EXPIRED                  = -112;
    public const INVALID_CALLBACK                 = -113;
    public const INVALID_ACL                      = -114;
    public const AUTH_FAILED                      = -115;
    public const SESSION_MOVED                    = -118;
    public const NOT_READONLY                     = -119;
    public const EPHEMERAL_ON_LOCAL_SESSION       = -120;
    public const NO_WATCHER                       = -121;
    public const REQUEST_TIMEOUT                  = -122;
    public const RECONFIG_DISABLED                = -123;
    public const SESSION_CLOSED_REQUIRE_SASL_AUTH = -124;
    public const QUOTA_EXCEEDED                   = -125;
    public const THROTTLED_OP                     = -127;

    public const MESSAGE = [
        self::OK                               => 'ok',
        self::SYSTEM_ERROR                     => 'SystemError',
        self::RUNTIME_INCONSISTENCY            => 'RuntimeInconsistency',
        self::DATA_INCONSISTENCY               => 'DataInconsistency',
        self::CONNECTION_LOSS                  => 'ConnectionLoss',
        self::MARSHALLING_ERROR                => 'MarshallingError',
        self::NEW_CONFIG_NO_QUORUM             => 'NewConfigNoQuorum',
        self::RECONFIG_IN_PROGRESS             => 'ReconfigInProgress',
        self::UNIMPLEMENTED                    => 'Unimplemented',
        self::OPERATION_TIMEOUT                => 'OperationTimeout',
        self::BAD_ARGUMENTS                    => 'BadArguments',
        self::API_ERROR                        => 'APIError',
        self::NO_NODE                          => 'NoNode',
        self::NO_AUTH                          => 'NoAuth',
        self::BAD_VERSION                      => 'BadVersion',
        self::NO_CHILDREN_FOR_EPHEMERALS       => 'NoChildrenForEphemerals',
        self::NODE_EXISTS                      => 'NodeExists',
        self::INVALID_ACL                      => 'InvalidACL',
        self::AUTH_FAILED                      => 'AuthFailed',
        self::NOT_EMPTY                        => 'Directory not empty',
        self::SESSION_EXPIRED                  => 'Session expired',
        self::INVALID_CALLBACK                 => 'Invalid callback',
        self::SESSION_MOVED                    => 'Session moved',
        self::NOT_READONLY                     => 'Not a read-only call',
        self::EPHEMERAL_ON_LOCAL_SESSION       => 'Ephemeral node on local session',
        self::NO_WATCHER                       => 'No such watcher',
        self::RECONFIG_DISABLED                => 'Reconfig is disabled',
        self::SESSION_CLOSED_REQUIRE_SASL_AUTH => 'Session closed because client failed to authenticate',
        self::QUOTA_EXCEEDED                   => 'Quota has exceeded',
        self::THROTTLED_OP                     => 'Op throttled due to high load',
    ];

    private string $path;

    public function __construct(string $message = '', int $code = 0, string $path = '', Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->path = $path;
    }

    public static function create(int $code, string $path = ''): self
    {
        $message = self::MESSAGE[$code] ?? \sprintf('Unknown error %d', $code);

        return new self($message, $code, $path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }
}
