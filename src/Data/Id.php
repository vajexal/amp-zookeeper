<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Data;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class Id implements Record
{
    private string $scheme;
    private string $id;

    public function __construct(string $scheme, string $id)
    {
        $this->id     = $id;
        $this->scheme = $scheme;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->scheme);
        $bb->writeString($this->id);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readString(),
        );
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
