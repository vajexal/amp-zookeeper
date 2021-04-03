<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class ReplyHeader implements Record
{
    private int $xid;
    private int $zxid;
    private int $err;

    public function __construct(int $xid, int $zxid, int $err)
    {
        $this->err  = $err;
        $this->zxid = $zxid;
        $this->xid  = $xid;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeInt($this->xid);
        $bb->writeLong($this->zxid);
        $bb->writeInt($this->err);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readInt(),
            $bb->readLong(),
            $bb->readInt(),
        );
    }

    public function getXid(): int
    {
        return $this->xid;
    }

    public function getZxid(): int
    {
        return $this->zxid;
    }

    public function getErr(): int
    {
        return $this->err;
    }
}
