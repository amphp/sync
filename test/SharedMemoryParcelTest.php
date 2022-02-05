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
        $mutex = new SemaphoreMutex(PosixSemaphore::create(1));
        $object = SharedMemoryParcel::create($mutex, 'hi', 2);
        $object->synchronized(function () {
            return 'hello world';
        });

        self::assertEquals('hello world', $object->unwrap());
    }

    public function testInvalidSize(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('size must be greater than 0');

        $mutex = new SemaphoreMutex(PosixSemaphore::create(1));
        SharedMemoryParcel::create($mutex, 42, -1);
    }

    public function testInvalidPermissions(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Invalid permissions');

        $mutex = new SemaphoreMutex(PosixSemaphore::create(1));
        SharedMemoryParcel::create($mutex, 42, 8192, 0);
    }

    public function testTooBig(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('memory size');

        $mutex = new SemaphoreMutex(PosixSemaphore::create(1));
        SharedMemoryParcel::create($mutex, 42, 1 << 30);
    }

    protected function createParcel(mixed $value): Parcel
    {
        $mutex = new SemaphoreMutex(PosixSemaphore::create(1));
        $this->parcel = SharedMemoryParcel::create($mutex, $value);
        return $this->parcel;
    }
}
