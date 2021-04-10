<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class GetEphemeralsRequest implements Record
{
    private string $prefixPath;

    public function __construct(string $prefixPath)
    {
        $this->prefixPath = $prefixPath;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->prefixPath);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
        );
    }
}
