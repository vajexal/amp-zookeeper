<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

interface Record
{
    public function serialize(ByteBuffer $bb): void;
    public static function deserialize(ByteBuffer $bb): self;
}
