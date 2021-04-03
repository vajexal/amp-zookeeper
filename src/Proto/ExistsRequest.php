<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class ExistsRequest implements Record
{
    private bool   $watch;
    private string $path;

    public function __construct(string $path, bool $watch)
    {
        $this->path  = $path;
        $this->watch = $watch;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeBool($this->watch);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readBool(),
        );
    }

    public function isWatch(): bool
    {
        return $this->watch;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
