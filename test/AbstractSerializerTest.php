<?php

namespace Amp\Sync\Test;

use Amp\Sync\SerializationException;
use Amp\Sync\Serializer;
use PHPUnit\Framework\TestCase;

abstract class AbstractSerializerTest extends TestCase
{
    abstract public function createSerializer(): Serializer;

    public function provideSerializeData(): iterable
    {
        return [
            ['test'],
            [new \stdClass],
            [3.14],
            [['test', 1, new \stdClass]],
        ];
    }

    /**
     * @dataProvider provideSerializeData
     */
    public function testUnserializeSerializedData($data): void
    {
        $serializer = $this->createSerializer();

        $serialized = $serializer->serialize($data);

        $this->assertEquals($data, $serializer->unserialize($serialized));
    }

    public function provideLargeSerializeData(): iterable
    {
        return [
            [\str_repeat('a', 1 << 20)],
            [[\str_repeat('a', 1024), \str_repeat('b', 1024), \str_repeat('c', 1024)]],
        ];
    }

    /**
     * @depends      testUnserializeSerializedData
     * @dataProvider provideLargeSerializeData
     */
    public function testUnserializeSerializedLargeData($data): void
    {
        $data = [
            \str_repeat('a', 1024),
            \str_repeat('b', 1024),
            \str_repeat('c', 1024),
        ];

        $serializer = $this->createSerializer();

        $serialized = $serializer->serialize($data);

        $this->assertEquals($data, $serializer->unserialize($serialized));
    }

    public function provideUnserializableData(): iterable
    {
        return [
            [function () {
                // Empty function
            }],
            [new class() {
                // Empty class
            }],
        ];
    }

    /**
     * @dataProvider provideUnserializableData
     */
    public function testUnserializableData($data): void
    {
        $this->expectException(SerializationException::class);

        $this->createSerializer()->serialize($data);
    }

    public function provideInvalidData(): iterable
    {
        return [
            ['invalid data'],
        ];
    }

    /**
     * @dataProvider provideInvalidData
     */
    public function testUnserializeInvalidData($data): void
    {
        $this->expectException(SerializationException::class);

        $this->createSerializer()->unserialize($data);
    }
}
