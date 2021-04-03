<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class DeleteRequest implements Record
{
    private string $path;
    private int    $version;

    public function __construct(string $path, int $version)
    {
        $this->version = $version;
        $this->path    = $path;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeInt($this->version);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readInt(),
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
