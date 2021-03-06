<?php

declare(strict_types=1);

namespace Vajexal\AmpZookeeper\Tests;

use PHPUnit\Framework\TestCase;
use Vajexal\AmpZookeeper\ByteBuffer;
use Vajexal\AmpZookeeper\Exception\ByteBufferException;
use Vajexal\AmpZookeeper\OpCode;
use Vajexal\AmpZookeeper\Proto\RequestHeader;

class ByteBufferTest extends TestCase
{
    public function testByteBuffer()
    {
        $bb = new ByteBuffer;

        $bb->writeByte(123);
        $bb->writeBool(true);
        $bb->writeInt(5000);
        $bb->writeLong(5000);
        $bb->writeFloat(1.23);
        $bb->writeDouble(1.23);
        $bb->writeString('hello');
        $bb->writeBuffer('world');

        $this->assertEquals(
            '7B 01 00 00 13 88 00 00 00 00 00 00 13 88 3F 9D 70 A4 3F F3 AE 14 7A E1 47 AE 00 00 00 05 68 65 6C 6C 6F 00 00 00 05 77 6F 72 6C 64',
            $bb->toHex()
        );

        $bb->rewind();

        $this->assertEquals(123, $bb->readByte());
        $this->assertTrue($bb->readBool());
        $this->assertEquals(5000, $bb->readInt());
        $this->assertEquals(5000, $bb->readLong());
        $this->assertEqualsWithDelta(1.23, $bb->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(1.23, $bb->readDouble(), 0.0001);
        $this->assertEquals('hello', $bb->readString());
        $this->assertEquals('world', $bb->readBuffer());
    }

    public function testRecord()
    {
        $bb = new ByteBuffer;

        $requestHeader = new RequestHeader(OpCode::GET_DATA, 1);

        $bb->writeInt(-1);
        $bb->writeRecord($requestHeader);
        $bb->rewind();
        $bb->writeInt(\count($bb) - 4);

        $this->assertEquals('00 00 00 08 00 00 00 01 00 00 00 04', $bb->toHex());

        $requestHeader = RequestHeader::deserialize($bb);

        $this->assertEquals(1, $requestHeader->getXid());
        $this->assertEquals(OpCode::GET_DATA, $requestHeader->getType());
    }

    public function testNegativeNumbers()
    {
        $bb = new ByteBuffer;

        $bb->writeInt(-123);
        $bb->writeLong(-123);
        $bb->writeFloat(-1.23);
        $bb->writeDouble(-1.23);

        $this->assertEquals('FF FF FF 85 FF FF FF FF FF FF FF 85 BF 9D 70 A4 BF F3 AE 14 7A E1 47 AE', $bb->toHex());

        $bb->rewind();

        $this->assertEquals(-123, $bb->readInt());
        $this->assertEquals(-123, $bb->readLong());
        $this->assertEqualsWithDelta(-1.23, $bb->readFloat(), 0.0001);
        $this->assertEqualsWithDelta(-1.23, $bb->readDouble(), 0.0001);
    }

    public function testReadFromEmptyBuffer()
    {
        $this->expectExceptionObject(ByteBufferException::invalidOperation());

        $bb = new ByteBuffer;

        $bb->readInt();
    }

    public function testBrokenString()
    {
        $this->expectExceptionObject(ByteBufferException::invalidOperation());

        $bb = new ByteBuffer;
        $bb->writeString('foo');
        $bb->rewind();
        $bb->writeInt(10);

        $bb->readString();
    }
}
