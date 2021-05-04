<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Data\ACL;
use Vajexal\AmpZookeeper\Record;

class CreateTTLRequest implements Record
{
    private string $path;
    private string $data;
    /**
     * @var ACL[]
     */
    private array $acl;
    private int   $flags;
    private int   $ttl;

    public function __construct(string $path, string $data, array $acl, int $flags, int $ttl)
    {
        $this->flags = $flags;
        $this->acl   = $acl;
        $this->data  = $data;
        $this->path  = $path;
        $this->ttl   = $ttl;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->path);
        $bb->writeBuffer($this->data);
        $bb->writeVector($this->acl, fn (ACL $acl) => $bb->writeRecord($acl));
        $bb->writeInt($this->flags);
        $bb->writeLong($this->ttl);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readBuffer(),
            $bb->readVector(fn () => ACL::deserialize($bb)),
            $bb->readInt(),
            $bb->readLong(),
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return ACL[]
     */
    public function getAcl(): array
    {
        return $this->acl;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}
