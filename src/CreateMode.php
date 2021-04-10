<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface CreateMode
{
    public const PERSISTENT = 0;
    public const EPHEMERAL  = 1;

    public const MODES = [
        self::PERSISTENT,
        self::EPHEMERAL,
    ];
}
