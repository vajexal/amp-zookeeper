<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class GetChildrenResponse implements Record
{
    private array $children;

    public function __construct(array $children)
    {
        $this->children = $children;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeVector($this->children, fn (string $child) => $bb->writeString($child));
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readVector(fn () => $bb->readString()),
        );
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}
