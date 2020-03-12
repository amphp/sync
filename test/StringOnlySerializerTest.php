<?php

namespace Amp\Sync\Test;

use Amp\Sync\SerializationException;
use Amp\Sync\StringOnlySerializer;
use PHPUnit\Framework\TestCase;

class StringOnlySerializerTest extends TestCase
{
    public function testUnserializeSerializedData(): void
    {
        $serializer = new StringOnlySerializer;
        $data = 'test';
        $this->assertSame($data, $serializer->unserialize($serializer->serialize($data)));
    }

    public function testNonString(): void
    {
        $this->expectException(SerializationException::class);

        (new StringOnlySerializer)->serialize(1);
    }
}
