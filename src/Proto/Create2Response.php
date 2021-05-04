<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Data\Stat;
use Vajexal\AmpZookeeper\Record;

class Create2Response implements Record
{
    private string $path;
    private Stat   $stat;

    public function __construct(string $path, Stat $stat)
    {
        $this->path = $path;
        $this->stat = $stat;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeRecord($this->stat);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            Stat::deserialize($bb),
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }
}
