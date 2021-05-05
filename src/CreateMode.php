<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use InvalidArgumentException;

final class CreateMode
{
    public const PERSISTENT                     = 0;
    public const EPHEMERAL                      = 1;
    public const PERSISTENT_SEQUENTIAL          = 2;
    public const EPHEMERAL_SEQUENTIAL           = 3;
    public const CONTAINER                      = 4;
    public const PERSISTENT_WITH_TTL            = 5;
    public const PERSISTENT_SEQUENTIAL_WITH_TTL = 6;

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
            self::CONTAINER,
            self::PERSISTENT_WITH_TTL,
            self::PERSISTENT_SEQUENTIAL_WITH_TTL,
        ];

        if (!\in_array($mode, $modes, true)) {
            throw new InvalidArgumentException('Invalid create mode');
        }
    }

    public static function isSequential(int $mode): bool
    {
        static $sequentialModes = [
            self::PERSISTENT_SEQUENTIAL,
            self::EPHEMERAL_SEQUENTIAL,
            self::PERSISTENT_SEQUENTIAL_WITH_TTL,
        ];

        return \in_array($mode, $sequentialModes, true);
    }

    public static function isTtl(int $mode): bool
    {
        static $ttlModes = [
            self::PERSISTENT_WITH_TTL,
            self::PERSISTENT_SEQUENTIAL_WITH_TTL,
        ];

        return \in_array($mode, $ttlModes, true);
    }

    public static function isContainer(int $mode): bool
    {
        static $containerModes = [
            self::CONTAINER,
        ];

        return \in_array($mode, $containerModes, true);
    }
}
