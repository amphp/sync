<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalSemaphore;
use function Amp\Pipeline\fromIterable;
use function Amp\Sync\ConcurrentPipeline\each;
use function Revolt\EventLoop\delay;

class ConcurrentEachTest extends AsyncTestCase
{
    public function testOne(): void
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;
        };

        self::assertSame(
            3,
            each(fromIterable([1, 2, 3]), new LocalSemaphore(3), $processor)
        );
    }

    public function testOutputOrder(): void
    {
        $processor = static function ($job) {
            delay($job * 100);
        };

        self::assertSame(
            3,
            each(fromIterable([3, 2, 1]), new LocalSemaphore(3), $processor)
        );
    }

    public function testOutputOrderWithoutConcurrency(): void
    {
        $processor = static function ($job) {
            delay($job * 100);
        };

        self::assertSame(
            3,
            each(fromIterable([3, 2, 1]), new LocalSemaphore(1), $processor)
        );
    }

    public function testErrorHandling(): void
    {
        $processor = static function ($job) {
            print $job;

            delay(0);

            if ($job === 2) {
                throw new \Exception('Failure');
            }

            return $job;
        };

        // Job 2 errors, so only jobs 1, 2, and 3 should be executed
        $this->expectOutputString('123');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        each(fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);
    }

    protected function tearDown(): void
    {
        // Required to make testBackpressure fail instead of the following test
        \gc_collect_cycles();
    }
}
