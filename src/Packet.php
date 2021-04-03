<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Amp\Deferred;
use Vajexal\AmpZookeeper\Proto\ConnectRequest;
use Vajexal\AmpZookeeper\Proto\RequestHeader;

class Packet
{
    public ?RequestHeader $requestHeader;
    public ?Record        $request;
    public ?string        $responseClass;
    public Deferred       $deferred;
    public string         $path     = '';
    public bool           $readOnly = false;
    private ?ByteBuffer   $bb       = null;

    public function __construct(
        ?RequestHeader $requestHeader = null,
        ?Record $request = null,
        ?string $responseClass = null,
        string $path = '',
        bool $readOnly = false,
    ) {
        $this->requestHeader = $requestHeader;
        $this->request       = $request;
        $this->responseClass = $responseClass;
        $this->path          = $path;
        $this->readOnly      = $readOnly;
    }

    public function getBB(): ByteBuffer
    {
        if (!$this->bb) {
            $this->createBB();
        }

        return $this->bb;
    }

    private function createBB(): void
    {
        $this->bb = new ByteBuffer;

        // book packet length
        $this->bb->writeInt(-1);

        if ($this->requestHeader !== null) {
            $this->requestHeader->serialize($this->bb);
        }

        if ($this->request instanceof ConnectRequest) {
            $this->request->serialize($this->bb);
            $this->bb->writeBool($this->readOnly);
        } elseif ($this->request !== null) {
            $this->request->serialize($this->bb);
        }

        // write packet length
        $this->bb->rewind();
        $this->bb->writeInt(\count($this->bb) - 4);
        $this->bb->rewind();
    }
}
