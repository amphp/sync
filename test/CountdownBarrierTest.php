<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\CountdownBarrier;
use Error;

class CountdownBarrierTest extends AsyncTestCase
{
    /** @var CountdownBarrier */
    private $countdownBarrier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->countdownBarrier = new CountdownBarrier(2);
    }

    public function testSignalUntilResolved()
    {
        $resolved = false;

        $this->countdownBarrier->promise()->onResolve(function ($exception, $value) use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->countdownBarrier->signal();
        $this->assertSame(1, $this->countdownBarrier->getCurrentCount());

        $this->assertFalse($resolved);

        $this->countdownBarrier->signal();

        $this->assertTrue($resolved);
        $this->assertSame(0, $this->countdownBarrier->getCurrentCount());
    }

    public function testSignalAfterResolved(): void
    {
        $this->countdownBarrier->signal();
        $this->countdownBarrier->signal();

        $this->expectException(Error::class);
        $this->countdownBarrier->signal();
    }

    public function testSignalWithCount(): void
    {
        $resolved = false;

        $this->countdownBarrier->promise()->onResolve(function ($exception, $value) use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->countdownBarrier->signal(2);

        $this->assertTrue($resolved);
    }

    public function testSignalWithInvalidCount(): void
    {
        $this->expectException(Error::class);

        $this->countdownBarrier->signal(0);
    }

    public function testSignalTooHighCount(): void
    {
        $this->expectException(Error::class);

        $this->countdownBarrier->signal(3);
    }

    public function testGetCurrentCount(): void
    {
        $this->countdownBarrier->signal();
        $this->assertEquals(1, $this->countdownBarrier->getCurrentCount());
    }

    public function testGetInitialCount(): void
    {
        $this->countdownBarrier->signal();
        $this->assertEquals(2, $this->countdownBarrier->getInitialCount());
    }

    public function testInvalidSignalCountInConstructor(): void
    {
        $this->expectException(Error::class);
        new CountdownBarrier(0);
    }

    public function testAddCount(): void
    {
        $resolved = false;

        $this->countdownBarrier->promise()->onResolve(function ($exception, $value) use (&$resolved) {
            $resolved = true;
        });

        $this->countdownBarrier->signal();
        $this->countdownBarrier->addCount();
        $this->countdownBarrier->signal();

        $this->assertFalse($resolved);

        $this->countdownBarrier->signal();

        $this->assertTrue($resolved);
    }

    public function testAddCountWithSignalCount(): void
    {
        $resolved = false;

        $this->countdownBarrier->promise()->onResolve(function ($exception, $value) use (&$resolved) {
            $resolved = true;
        });

        $this->countdownBarrier->signal();
        $this->countdownBarrier->addCount(2);
        $this->countdownBarrier->signal();

        $this->assertFalse($resolved);

        $this->countdownBarrier->signal();

        $this->assertFalse($resolved);

        $this->countdownBarrier->signal();

        $this->assertTrue($resolved);
    }

    public function testAddCountWithInvalidSignalCount(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Signal count must be greater or equals 1');

        $this->countdownBarrier->addCount(0);
    }

    public function testAddCountWithResolvedPromise(): void
    {
        $this->countdownBarrier->signal();
        $this->countdownBarrier->signal();

        $this->expectException(Error::class);
        $this->expectExceptionMessage('CountdownBarrier already resolved');

        $this->countdownBarrier->addCount(1);
    }
}
