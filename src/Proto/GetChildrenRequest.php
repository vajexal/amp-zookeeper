<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class GetChildrenRequest implements Record
{
    private string $path;
    private bool   $watch;

    public function __construct(string $path, bool $watch)
    {
        $this->watch = $watch;
        $this->path  = $path;
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function isWatch(): bool
    {
        return $this->watch;
    }
}
