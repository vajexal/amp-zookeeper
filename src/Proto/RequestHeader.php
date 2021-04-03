<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class RequestHeader implements Record
{
    private int $xid;
    private int $type;

    public function __construct(int $xid, int $type)
    {
        $this->type = $type;
        $this->xid  = $xid;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeInt($this->xid);
        $bb->writeInt($this->type);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readInt(),
            $bb->readInt(),
        );
    }

    public function getXid(): int
    {
        return $this->xid;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
