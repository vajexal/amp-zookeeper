<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class RemoveWatchesRequest implements Record
{
    private string $path;
    private int    $type;

    public function __construct(string $path, int $type)
    {
        $this->path = $path;
        $this->type = $type;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeInt($this->type);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readInt(),
        );
    }
}
