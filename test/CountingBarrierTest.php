<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\CountingBarrier;

class CountingBarrierTest extends AsyncTestCase
{
    /** @var CountingBarrier */
    private $countingBarrier;

    public function testDecreaseUntilResolved(): void
    {
        $resolved = false;

        $this->countingBarrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->countingBarrier->decrease();
        $this->assertSame(1, $this->countingBarrier->getCount());

        $this->assertFalse($resolved);

        $this->countingBarrier->decrease();

        $this->assertTrue($resolved);
        $this->assertSame(0, $this->countingBarrier->getCount());
    }

    public function testDecreaseAfterResolved(): void
    {
        $this->countingBarrier->decrease();
        $this->countingBarrier->decrease();

        $this->expectException(\Error::class);
        $this->countingBarrier->decrease();
    }

    public function testDecreaseWithCount(): void
    {
        $resolved = false;

        $this->countingBarrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->countingBarrier->decrease(2);

        $this->assertTrue($resolved);
    }

    public function testDecreaseWithInvalidCount(): void
    {
        $this->expectException(\Error::class);

        $this->countingBarrier->decrease(0);
    }

    public function testDecreaseTooHighCount(): void
    {
        $this->expectException(\Error::class);

        $this->countingBarrier->decrease(3);
    }

    public function testGetCurrentCount(): void
    {
        $this->countingBarrier->decrease();
        $this->assertEquals(1, $this->countingBarrier->getCount());
    }

    public function testInvalidSignalCountInConstructor(): void
    {
        $this->expectException(\Error::class);
        new CountingBarrier(0);
    }

    public function testIncreaseCount(): void
    {
        $resolved = false;

        $this->countingBarrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->countingBarrier->decrease();
        $this->countingBarrier->increase();
        $this->countingBarrier->decrease();

        $this->assertFalse($resolved);

        $this->countingBarrier->decrease();

        $this->assertTrue($resolved);
    }

    public function testIncreaseCountWithCustomCount(): void
    {
        $resolved = false;

        $this->countingBarrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->countingBarrier->decrease();
        $this->countingBarrier->increase(2);
        $this->countingBarrier->decrease();

        $this->assertFalse($resolved);

        $this->countingBarrier->decrease();

        $this->assertFalse($resolved);

        $this->countingBarrier->decrease();

        $this->assertTrue($resolved);
    }

    public function testIncreaseCountWithInvalidCount(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Count must be at least 1, got 0');

        $this->countingBarrier->increase(0);
    }

    public function testIncreaseCountWithResolvedBarrier(): void
    {
        $this->countingBarrier->decrease();
        $this->countingBarrier->decrease();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Can\'t increase count, because the barrier already broke');

        $this->countingBarrier->increase(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->countingBarrier = new CountingBarrier(2);
    }
}
