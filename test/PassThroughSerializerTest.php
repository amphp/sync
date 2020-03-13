<?php

namespace Amp\Sync\Test;

use Amp\Sync\PassThroughSerializer;
use Amp\Sync\SerializationException;
use PHPUnit\Framework\TestCase;

class PassThroughSerializerTest extends TestCase
{
    public function testUnserializeSerializedData(): void
    {
        $serializer = new PassThroughSerializer;
        $data = 'test';
        $this->assertSame($data, $serializer->unserialize($serializer->serialize($data)));
    }

    public function testNonString(): void
    {
        $this->expectException(SerializationException::class);

        (new PassThroughSerializer)->serialize(1);
    }
}
