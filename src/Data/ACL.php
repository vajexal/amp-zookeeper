<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Data;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class ACL implements Record
{
    private int $perms;
    private Id  $id;

    public function __construct(int $perms, Id $id)
    {
        $this->id    = $id;
        $this->perms = $perms;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeInt($this->perms);
        $bb->writeRecord($this->id);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readInt(),
            Id::deserialize($bb),
        );
    }

    public function getPerms(): int
    {
        return $this->perms;
    }

    public function getId(): Id
    {
        return $this->id;
    }
}
