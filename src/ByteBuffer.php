<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper;

use Countable;
use Iterator;
use Stringable;
use Vajexal\AmpZookeeper\Exception\KeeperException;

class ByteBuffer implements Countable, Iterator, Stringable
{
    private string $data     = '';
    private int    $position = 0;

    public function __construct(string $data = '')
    {
        $this->data = $data;
    }

    public function writeByte(int $b): void
    {
        $this->data[$this->position] = \pack('C', $b);
        $this->position++;
    }

    public function writeBool(bool $b): void
    {
        $this->writeByte($b ? 1 : 0);
    }

    public function writeInt(int $i): void
    {
        $this->data     = \substr_replace($this->data, \pack('N', $i), $this->position, 4);
        $this->position += 4;
    }

    public function writeLong(int $l): void
    {
        $this->data     = \substr_replace($this->data, \pack('J', $l), $this->position, 8);
        $this->position += 8;
    }

    public function writeFloat(float $f): void
    {
        // hope it's 32 bits
        $this->data     = \substr_replace($this->data, \pack('G', $f), $this->position, 4);
        $this->position += 4;
    }

    public function writeDouble(float $d): void
    {
        // hope it's 64 bits
        $this->data     = \substr_replace($this->data, \pack('E', $d), $this->position, 8);
        $this->position += 8;
    }

    public function writeString(string $s): void
    {
        $this->writeInt(\strlen($s));
        $this->data     = \substr_replace($this->data, $s, $this->position, \strlen($s));
        $this->position += \strlen($s);
    }

    public function writeBuffer(string $buf): void
    {
        $this->writeString($buf);
    }

    public function writeRecord(Record $record): void
    {
        $record->serialize($this);
    }

    public function writeVector(array $array, callable $writer): void
    {
        $this->writeInt(\count($array));

        \array_walk($array, $writer);
    }

    public function readByte(): int
    {
        $b = \unpack('C', $this->data[$this->position])[1];
        $this->position++;
        return $b;
    }

    public function readBool(): bool
    {
        return $this->readByte() === 1;
    }

    public function readInt(): int
    {
        $i              = \unpack('N', \substr($this->data, $this->position, 4))[1];
        $this->position += 4;

        if ($i > 0x7FFFFFFF) {
            $i -= 0x100000000;
        }

        return $i;
    }

    public function readLong(): int
    {
        $l              = \unpack('J', \substr($this->data, $this->position, 8))[1];
        $this->position += 8;
        return $l;
    }

    public function readFloat(): float
    {
        // hope it's 32 bits
        $f              = \unpack('G', \substr($this->data, $this->position, 4))[1];
        $this->position += 4;
        return $f;
    }

    public function readDouble(): float
    {
        // hope it's 64 bits
        $d              = \unpack('E', \substr($this->data, $this->position, 8))[1];
        $this->position += 8;
        return $d;
    }

    public function readString(): string
    {
        $len = $this->readInt();

        if ($len === -1) {
            return '';
        }

        $this->checkLength($len);

        $s              = \substr($this->data, $this->position, $len);
        $this->position += $len;
        return $s;
    }

    public function readBuffer(): string
    {
        return $this->readString();
    }

    public function readVector(callable $reader): array
    {
        $len = $this->readInt();

        return \array_map($reader, \range(1, $len));
    }

    private function checkLength(int $len)
    {
        if ($len < 0 || $len > 0xfffff) {
            throw new KeeperException(\sprintf('Unreasonable length = %d', $len));
        }
    }

    public function count(): int
    {
        return \strlen($this->data);
    }

    public function current(): string
    {
        return $this->data[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->position < \strlen($this->data);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function __toString(): string
    {
        return $this->data;
    }

    public function toHex(): string
    {
        return \wordwrap(\strtoupper(\bin2hex($this->data)), 2, ' ', true);
    }
}
