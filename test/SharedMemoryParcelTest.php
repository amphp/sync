<?php

namespace Amp\Sync;

/**
 * @requires extension shmop
 * @requires extension sysvmsg
 */
class SharedMemoryParcelTest extends AbstractParcelTest
{
    const ID = __CLASS__ . '3';

    private ?SharedMemoryParcel $parcel;

    public function tearDown(): void
    {
        $this->parcel = null;
    }

    public function testObjectOverflowMoved(): void
    {
        $object = SharedMemoryParcel::create(self::ID, 'hi', 2);
        $object->synchronized(function () {
            return 'hello world';
        });

        self::assertEquals('hello world', $object->unwrap());
    }

    public function testInvalidSize(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('size must be greater than 0');

        SharedMemoryParcel::create(self::ID, 42, -1);
    }

    public function testInvalidPermissions(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Invalid permissions');

        SharedMemoryParcel::create(self::ID, 42, 8192, 0);
    }

    public function testNotFound(): void
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage('No semaphore with that ID found');

        SharedMemoryParcel::use('invalid');
    }

    public function testDoubleCreate(): void
    {
        $this->expectException(SyncException::class);
        $this->expectExceptionMessage('A semaphore with that ID already exists');

        $parcel1 = SharedMemoryParcel::create(self::ID, 42);
        $parcel2 = SharedMemoryParcel::create(self::ID, 42);
    }

    public function testTooBig(): void
    {
        $this->expectException(ParcelException::class);
        $this->expectExceptionMessage('Failed to create shared memory block');

        SharedMemoryParcel::create(self::ID, 42, 1 << 50);
    }

    protected function createParcel(mixed $value): Parcel
    {
        $this->parcel = SharedMemoryParcel::create(self::ID, $value);
        return $this->parcel;
    }
}
