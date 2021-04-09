<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Exception;

use Exception;

class ZookeeperException extends Exception
{
    public const TOO_DUMP_TO_READ_EXACT_BYTES_CODE = 0;

    public static function tooDumpToReadExactBytes(): self
    {
        return new self('I am too dump for now to read exact bytes from buffer', self::TOO_DUMP_TO_READ_EXACT_BYTES_CODE);
    }
}
