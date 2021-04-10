<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface WatcherType
{
    public const CHILDREN = 1;
    public const DATA     = 2;
    public const ANY      = 3;

    public const TYPES = [
        self::CHILDREN,
        self::DATA,
        self::ANY,
    ];
}
