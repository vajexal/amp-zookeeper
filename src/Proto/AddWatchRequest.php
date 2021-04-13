<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class AddWatchRequest implements Record
{
    private string $path;
    private int    $mode;

    public function __construct(string $path, int $mode)
    {
        $this->path = $path;
        $this->mode = $mode;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeInt($this->mode);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readInt(),
        );
    }
}
