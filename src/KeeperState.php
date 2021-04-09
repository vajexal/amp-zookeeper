<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface KeeperState
{
    public const UNKNOWN             = -1;
    public const DISCONNECTED        = 0;
    public const NO_SYNC_CONNECTED   = 1;
    public const SYNC_CONNECTED      = 3;
    public const AUTH_FAILED         = 4;
    public const CONNECTED_READ_ONLY = 5;
    public const SASL_AUTHENTICATED  = 6;
    public const EXPIRED             = -112;
    public const CLOSED              = 7;
}
