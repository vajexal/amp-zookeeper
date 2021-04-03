<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface Perms
{
    public const READ   = 1 << 0;
    public const WRITE  = 1 << 1;
    public const CREATE = 1 << 2;
    public const DELETE = 1 << 3;
    public const ADMIN  = 1 << 4;
    public const ALL    = self::READ | self::WRITE | self::CREATE | self::DELETE | self::ADMIN;
}
