<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\CancelledException;
use Amp\PHPUnit\AsyncTestCase;
use Amp\TimeoutCancellation;
use function Amp\async;
use function Amp\delay;

abstract class AbstractParcelTest extends AsyncTestCase
{
    public function testUnwrapIsOfCorrectType(): void
    {
        $parcel = $this->createParcel(new \stdClass);
        self::assertInstanceOf('stdClass', $parcel->unwrap());
    }

    public function testUnwrapIsEqual(): void
    {
        $object = new \stdClass;
        $parcel = $this->createParcel($object);
        self::assertEquals($object, $parcel->unwrap());
    }

    public function testSynchronized(): void
    {
        $parcel = $this->createParcel(0);

        $future1 = async(fn () => $parcel->synchronized(function ($value): int {
            self::assertSame(0, $value);
            delay(0.2);
            return 1;
        }));

        $future2 = async(fn () => $parcel->synchronized(function ($value): int {
            self::assertSame(1, $value);
            delay(0.1);
            return 2;
        }));

        self::assertSame(1, $future1->await());
        self::assertSame(2, $future2->await());
    }

    public function testVoidFunction(): void
    {
        $parcel = $this->createParcel(1);

        $result = $parcel->synchronized(function ($value): void {
            self::assertSame(1, $value);
        });

        self::assertNull($result);
        self::assertNull($parcel->unwrap());
    }

    public function testSynchronizedPassesCancellationToClosure(): void
    {
        $parcel = $this->createParcel(1);

        $cancellation = new TimeoutCancellation(1);

        $result = $parcel->synchronized(function ($value, ?Cancellation $received) use ($cancellation): int {
            self::assertSame(1, $value);
            self::assertSame($cancellation, $received);
            return 2;
        }, $cancellation);

        self::assertSame(2, $result);
        self::assertSame(2, $parcel->unwrap());
    }

    public function testSynchronizedCancellation(): void
    {
        $parcel = $this->createParcel(0);

        // Hold the parcel's lock (and keep the event loop alive) past the cancellation timeout.
        $future = async(fn () => $parcel->synchronized(function ($value): int {
            delay(0.3);
            return 1;
        }));

        delay(0.1); // Wait for the first closure to acquire the lock.

        try {
            $this->expectException(CancelledException::class);
            $parcel->synchronized(fn ($value): int => $value, new TimeoutCancellation(0.1));
        } finally {
            self::assertSame(1, $future->await());
        }
    }

    public function testSynchronizedAfterCancellation(): void
    {
        $parcel = $this->createParcel(0);

        // Hold the parcel's lock (and keep the event loop alive) past the cancellation timeout.
        $future = async(fn () => $parcel->synchronized(function ($value): int {
            delay(0.3);
            return 1;
        }));

        delay(0.1); // Wait for the first closure to acquire the lock.

        try {
            $parcel->synchronized(fn ($value): int => $value + 10, new TimeoutCancellation(0.1));
            self::fail('The pending synchronized() call should have been cancelled');
        } catch (CancelledException) {
            // Expected.
        }

        self::assertSame(1, $future->await());

        // A subsequent synchronized() call must still succeed after the cancelled one.
        $result = $parcel->synchronized(fn ($value): int => $value + 1);
        self::assertSame(2, $result);
        self::assertSame(2, $parcel->unwrap());
    }

    abstract protected function createParcel(mixed $value): Parcel;
}
