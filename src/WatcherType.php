<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use InvalidArgumentException;

class WatcherType
{
    public const CHILDREN = 1;
    public const DATA     = 2;
    public const ANY      = 3;

    private function __construct()
    {
    }

    public static function validate(int $type): void
    {
        static $types = [
            self::CHILDREN,
            self::DATA,
            self::ANY,
        ];

        if (!\in_array($type, $types, true)) {
            throw new InvalidArgumentException('Invalid watcher type');
        }
    }
}
