<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface EventType
{
    public const NONE                     = -1;
    public const NODE_CREATED             = 1;
    public const NODE_DELETED             = 2;
    public const NODE_DATA_CHANGER        = 3;
    public const NODE_CHILDREN_CHANGED    = 4;
    public const DATA_WATCH_REMOVED       = 5;
    public const CHILD_WATCH_REMOVED      = 6;
    public const PERSISTENT_WATCH_REMOVED = 7;
}
