<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Data\Stat;
use Vajexal\AmpZookeeper\Record;

class SetDataResponse implements Record
{
    private Stat $stat;

    public function __construct(Stat $stat)
    {
        $this->stat = $stat;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeRecord($this->stat);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            Stat::deserialize($bb),
        );
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }
}
