<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\Barrier;

class BarrierTest extends AsyncTestCase
{
    /** @var Barrier */
    private $barrier;

    public function testArriveUntilResolved(): void
    {
        $resolved = false;

        $this->barrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->barrier->arrive();
        $this->assertSame(1, $this->barrier->getCount());

        $this->assertFalse($resolved);

        $this->barrier->arrive();

        $this->assertTrue($resolved);
        $this->assertSame(0, $this->barrier->getCount());
    }

    public function testArriveAfterResolved(): void
    {
        $this->barrier->arrive();
        $this->barrier->arrive();

        $this->expectException(\Error::class);
        $this->barrier->arrive();
    }

    public function testArriveWithCount(): void
    {
        $resolved = false;

        $this->barrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->barrier->arrive(2);

        $this->assertTrue($resolved);
    }

    public function testArriveWithInvalidCount(): void
    {
        $this->expectException(\Error::class);

        $this->barrier->arrive(0);
    }

    public function testArriveTooHighCount(): void
    {
        $this->expectException(\Error::class);

        $this->barrier->arrive(3);
    }

    public function testGetCurrentCount(): void
    {
        $this->barrier->arrive();
        $this->assertEquals(1, $this->barrier->getCount());
    }

    public function testInvalidSignalCountInConstructor(): void
    {
        $this->expectException(\Error::class);
        new Barrier(0);
    }

    public function testRegisterCount(): void
    {
        $resolved = false;

        $this->barrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->barrier->arrive();
        $this->barrier->register();
        $this->barrier->arrive();

        $this->assertFalse($resolved);

        $this->barrier->arrive();

        $this->assertTrue($resolved);
    }

    public function testRegisterCountWithCustomCount(): void
    {
        $resolved = false;

        $this->barrier->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->barrier->arrive();
        $this->barrier->register(2);
        $this->barrier->arrive();

        $this->assertFalse($resolved);

        $this->barrier->arrive();

        $this->assertFalse($resolved);

        $this->barrier->arrive();

        $this->assertTrue($resolved);
    }

    public function testRegisterCountWithInvalidCount(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Count must be at least 1, got 0');

        $this->barrier->register(0);
    }

    public function testRegisterCountWithResolvedBarrier(): void
    {
        $this->barrier->arrive();
        $this->barrier->arrive();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Can\'t increase count, because the barrier already broke');

        $this->barrier->register(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->barrier = new Barrier(2);
    }
}
