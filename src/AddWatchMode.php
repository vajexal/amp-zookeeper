<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use InvalidArgumentException;

final class AddWatchMode
{
    public const PERSISTENT           = 0;
    public const PERSISTENT_RECURSIVE = 1;

    private function __construct()
    {
    }

    public static function validate(int $mode): void
    {
        static $modes = [
            self::PERSISTENT,
            self::PERSISTENT_RECURSIVE,
        ];

        if (!\in_array($mode, $modes, true)) {
            throw new InvalidArgumentException('Invalid add watch mode');
        }
    }
}
