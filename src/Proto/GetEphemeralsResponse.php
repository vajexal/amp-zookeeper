<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class GetEphemeralsResponse implements Record
{
    private array $ephemerals;

    public function __construct(array $ephemerals)
    {
        $this->ephemerals = $ephemerals;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeVector($this->ephemerals, fn (string $ephemeral) => $bb->writeString($ephemeral));
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readVector(fn () => $bb->readString()),
        );
    }

    public function getEphemerals(): array
    {
        return $this->ephemerals;
    }
}
