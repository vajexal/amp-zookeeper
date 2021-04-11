<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use InvalidArgumentException;

final class CreateMode
{
    public const PERSISTENT            = 0;
    public const EPHEMERAL             = 1;
    public const PERSISTENT_SEQUENTIAL = 2;
    public const EPHEMERAL_SEQUENTIAL  = 3;

    private function __construct()
    {
    }

    public static function validate(int $mode): void
    {
        static $modes = [
            self::PERSISTENT,
            self::EPHEMERAL,
            self::PERSISTENT_SEQUENTIAL,
            self::EPHEMERAL_SEQUENTIAL,
        ];

        if (!\in_array($mode, $modes, true)) {
            throw new InvalidArgumentException('Invalid create mode');
        }
    }

    public static function isSequential(int $mode): bool
    {
        return \in_array($mode, [self::PERSISTENT_SEQUENTIAL, self::EPHEMERAL_SEQUENTIAL], true);
    }
}
