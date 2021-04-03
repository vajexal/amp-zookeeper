<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Proto;

use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Record;

class ConnectResponse implements Record
{
    private int    $protocolVersion;
    private int    $timeOut;
    private int    $sessionId;
    private string $passwd;

    public function __construct(int $protocolVersion, int $timeOut, int $sessionId, string $passwd)
    {
        $this->passwd          = $passwd;
        $this->sessionId       = $sessionId;
        $this->timeOut         = $timeOut;
        $this->protocolVersion = $protocolVersion;
    }

    public function serialize(ByteBuffer $bb): void
    {
        $bb->writeInt($this->protocolVersion);
        $bb->writeInt($this->timeOut);
        $bb->writeLong($this->sessionId);
        $bb->writeBuffer($this->passwd);
    }

    public static function deserialize(ByteBuffer $bb): self
    {
        return new self(
            $bb->readInt(),
            $bb->readInt(),
            $bb->readLong(),
            $bb->readBuffer(),
        );
    }

    public function getProtocolVersion(): int
    {
        return $this->protocolVersion;
    }

    public function getTimeOut(): int
    {
        return $this->timeOut;
    }

    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    public function getPasswd(): string
    {
        return $this->passwd;
    }
}
