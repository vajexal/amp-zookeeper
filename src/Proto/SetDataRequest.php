<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class SetDataRequest implements Record
{
    private string $path;
    private string $data;
    private int    $version;

    public function __construct(string $path, string $data, int $version)
    {
        $this->version = $version;
        $this->data    = $data;
        $this->path    = $path;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeBuffer($this->data);
        $bb->writeInt($this->version);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readBuffer(),
            $bb->readInt(),
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
