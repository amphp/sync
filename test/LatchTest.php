<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\Latch;

class LatchTest extends AsyncTestCase
{
    /** @var Latch */
    private $latch;

    public function testArriveUntilResolved(): void
    {
        $resolved = false;

        $this->latch->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->latch->arrive();
        $this->assertSame(1, $this->latch->getCount());

        $this->assertFalse($resolved);

        $this->latch->arrive();

        $this->assertTrue($resolved);
        $this->assertSame(0, $this->latch->getCount());
    }

    public function testArriveAfterResolved(): void
    {
        $this->latch->arrive();
        $this->latch->arrive();

        $this->expectException(\Error::class);
        $this->latch->arrive();
    }

    public function testArriveWithCount(): void
    {
        $resolved = false;

        $this->latch->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->assertFalse($resolved);

        $this->latch->arrive(2);

        $this->assertTrue($resolved);
    }

    public function testArriveWithInvalidCount(): void
    {
        $this->expectException(\Error::class);

        $this->latch->arrive(0);
    }

    public function testArriveTooHighCount(): void
    {
        $this->expectException(\Error::class);

        $this->latch->arrive(3);
    }

    public function testGetCurrentCount(): void
    {
        $this->latch->arrive();
        $this->assertEquals(1, $this->latch->getCount());
    }

    public function testInvalidSignalCountInConstructor(): void
    {
        $this->expectException(\Error::class);
        new Latch(0);
    }

    public function testRegisterCount(): void
    {
        $resolved = false;

        $this->latch->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->latch->arrive();
        $this->latch->register();
        $this->latch->arrive();

        $this->assertFalse($resolved);

        $this->latch->arrive();

        $this->assertTrue($resolved);
    }

    public function testRegisterCountWithCustomCount(): void
    {
        $resolved = false;

        $this->latch->await()->onResolve(static function () use (&$resolved) {
            $resolved = true;
        });

        $this->latch->arrive();
        $this->latch->register(2);
        $this->latch->arrive();

        $this->assertFalse($resolved);

        $this->latch->arrive();

        $this->assertFalse($resolved);

        $this->latch->arrive();

        $this->assertTrue($resolved);
    }

    public function testRegisterCountWithInvalidCount(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Count must be at least 1, got 0');

        $this->latch->register(0);
    }

    public function testRegisterCountWithResolvedBarrier(): void
    {
        $this->latch->arrive();
        $this->latch->arrive();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Can\'t increase count, because the barrier already broke');

        $this->latch->register(1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->latch = new Latch(2);
    }
}
