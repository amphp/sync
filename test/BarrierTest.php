<?php

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;

class BarrierTest extends AsyncTestCase
{
    /** @var Barrier */
    private Barrier $barrier;

    public function setUp(): void
    {
        parent::setUp();

        $this->barrier = new Barrier(2);
    }

    public function testArriveUntilResolved(): void
    {
        $this->setTimeout(0.01);

        $this->barrier->arrive();
        self::assertSame(1, $this->barrier->getCount());

        $this->barrier->arrive();

        $this->barrier->await();

        self::assertSame(0, $this->barrier->getCount());
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
        $this->setTimeout(0.01);

        $this->barrier->arrive(2);

        self::assertSame(0, $this->barrier->getCount());
        $this->barrier->await();
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
        self::assertEquals(1, $this->barrier->getCount());
    }

    public function testInvalidSignalCountInConstructor(): void
    {
        $this->expectException(\Error::class);
        new Barrier(0);
    }

    public function testRegisterCount(): void
    {
        $this->setTimeout(0.01);

        $this->barrier->arrive();
        $this->barrier->register();
        self::assertSame(2, $this->barrier->getCount());

        $this->barrier->arrive();
        $this->barrier->arrive();

        $this->barrier->await();
    }

    public function testRegisterCountWithCustomCount(): void
    {
        $this->setTimeout(0.01);

        $this->barrier->arrive();
        $this->barrier->register(2);
        self::assertSame(3, $this->barrier->getCount());

        $this->barrier->arrive();
        $this->barrier->arrive();
        $this->barrier->arrive();

        $this->barrier->await();
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
}
