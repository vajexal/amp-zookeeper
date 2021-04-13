<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class ErrorResponse implements Record
{
    private int $err;

    public function __construct(int $err)
    {
        $this->err = $err;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeInt($this->err);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readInt(),
        );
    }
}
