<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class RequestHeader implements Record
{
    private int $type;
    private int $xid;

    public function __construct(int $type, int $xid = 0)
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
        $xid  = $bb->readInt();
        $type = $bb->readInt();

        return new self(
            $type,
            $xid,
        );
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getXid(): int
    {
        return $this->xid;
    }

    public function setXid(int $xid): void
    {
        $this->xid = $xid;
    }
}
