<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use InvalidArgumentException;

class EphemeralType
{
    private const EXTENDED_MASK      = 0xff00000000000000;
    private const RESERVED_BITS_MASK = 0x00ffff0000000000;
    private const MAX_VALUE          = ~(self::EXTENDED_MASK | self::RESERVED_BITS_MASK);

    private function __construct()
    {
    }

    public static function validateTTL(int $mode, int $ttl): void
    {
        if (CreateMode::isTtl($mode)) {
            if ($ttl > self::MAX_VALUE || $ttl <= 0) {
                throw new InvalidArgumentException(\sprintf('ttl must be positive and cannot be larger than: %d', self::MAX_VALUE));
            }
        } elseif ($ttl >= 0) {
            throw new InvalidArgumentException(\sprintf('ttl not valid for mode: %d', $mode));
        }
    }
}
