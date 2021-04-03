<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Data\Stat;
use Vajexal\AmpZookeeper\Record;

class GetDataResponse implements Record
{
    private string $data;
    private Stat   $stat;

    public function __construct(string $data, Stat $stat)
    {
        $this->stat = $stat;
        $this->data = $data;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeBuffer($this->data);
        $bb->writeRecord($this->stat);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readBuffer(),
            Stat::deserialize($bb),
        );
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }
}
