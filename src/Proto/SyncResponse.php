<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class SyncResponse implements Record
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
    }

    public static function deserialize(ByteBuffer $bb): Record
    {
        return new self(
            $bb->readString()
        );
    }
}
