<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class WatcherEvent implements Record
{
    private int    $type;
    private int    $state;
    private string $path;

    public function __construct(int $type, int $state, string $path)
    {
        $this->type  = $type;
        $this->state = $state;
        $this->path  = $path;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeInt($this->type);
        $bb->writeInt($this->state);
        $bb->writeString($this->path);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readInt(),
            $bb->readInt(),
            $bb->readString(),
        );
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
