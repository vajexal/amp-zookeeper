<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Data;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class Stat implements Record
{
    private int $czxid;
    private int $mzxid;
    private int $ctime;
    private int $mtime;
    private int $version;
    private int $cversion;
    private int $aversion;
    private int $ephemeralOwner;
    private int $dataLength;
    private int $numChildren;
    private int $pzxid;

    public function __construct(
        int $czxid,
        int $mzxid,
        int $ctime,
        int $mtime,
        int $version,
        int $cversion,
        int $aversion,
        int $ephemeralOwner,
        int $dataLength,
        int $numChildren,
        int $pzxid,
    ) {
        $this->czxid          = $czxid;
        $this->mzxid          = $mzxid;
        $this->ctime          = $ctime;
        $this->mtime          = $mtime;
        $this->version        = $version;
        $this->cversion       = $cversion;
        $this->aversion       = $aversion;
        $this->ephemeralOwner = $ephemeralOwner;
        $this->dataLength     = $dataLength;
        $this->numChildren    = $numChildren;
        $this->pzxid          = $pzxid;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeLong($this->czxid);
        $bb->writeLong($this->mzxid);
        $bb->writeLong($this->ctime);
        $bb->writeLong($this->mtime);
        $bb->writeInt($this->version);
        $bb->writeInt($this->cversion);
        $bb->writeInt($this->aversion);
        $bb->writeLong($this->ephemeralOwner);
        $bb->writeInt($this->dataLength);
        $bb->writeInt($this->numChildren);
        $bb->writeLong($this->pzxid);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readLong(),
            $bb->readLong(),
            $bb->readLong(),
            $bb->readLong(),
            $bb->readInt(),
            $bb->readInt(),
            $bb->readInt(),
            $bb->readLong(),
            $bb->readInt(),
            $bb->readInt(),
            $bb->readLong(),
        );
    }

    public function getCzxid(): int
    {
        return $this->czxid;
    }

    public function getMzxid(): int
    {
        return $this->mzxid;
    }

    public function getCtime(): int
    {
        return $this->ctime;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCversion(): int
    {
        return $this->cversion;
    }

    public function getAversion(): int
    {
        return $this->aversion;
    }

    public function getEphemeralOwner(): int
    {
        return $this->ephemeralOwner;
    }

    public function getDataLength(): int
    {
        return $this->dataLength;
    }

    public function getNumChildren(): int
    {
        return $this->numChildren;
    }

    public function getPzxid(): int
    {
        return $this->pzxid;
    }
}
