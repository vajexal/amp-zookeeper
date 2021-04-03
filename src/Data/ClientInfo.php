<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Data;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class ClientInfo implements Record
{
    private string $authScheme;
    private string $user;

    public function __construct(string $authScheme, string $user)
    {
        $this->user       = $user;
        $this->authScheme = $authScheme;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeString($this->authScheme);
        $bb->writeString($this->user);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readString(),
            $bb->readString(),
        );
    }

    public function getAuthScheme(): string
    {
        return $this->authScheme;
    }

    public function getUser(): string
    {
        return $this->user;
    }
}
