<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Exception;

class ByteBufferException extends ZookeeperException
{
    public const INVALID_OPERATION_CODE = 0;
    public const INVALID_LENGTH_CODE    = 1;

    public static function invalidOperation(): self
    {
        return new self('Invalid operation', self::INVALID_OPERATION_CODE);
    }

    public static function unreasonableLength(int $len): self
    {
        return new self(\sprintf('Unreasonable length = %d', $len), self::INVALID_LENGTH_CODE);
    }
}
