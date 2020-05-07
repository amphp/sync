<?php

namespace Amp\Sync\Test;

use Amp\Iterator;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalSemaphore;
use function Amp\delay;
use function Amp\Sync\ConcurrentIterator\each;

class ConcurrentEachTest extends AsyncTestCase
{
    public function test(): \Generator
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;
        };

        $this->assertSame(
            3,
            yield each(Iterator\fromIterable([1, 2, 3]), new LocalSemaphore(3), $processor)
        );
    }

    public function testOutputOrder(): \Generator
    {
        $processor = static function ($job) {
            yield delay($job * 100);
        };

        $this->assertSame(
            3,
            yield each(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(3), $processor)
        );
    }

    public function testOutputOrderWithoutConcurrency(): \Generator
    {
        $processor = static function ($job) {
            yield delay($job * 100);
        };

        $this->assertSame(
            3,
            yield each(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(1), $processor)
        );
    }

    public function testErrorHandling(): \Generator
    {
        $processor = static function ($job) {
            print $job;

            yield delay(0);

            if ($job === 2) {
                throw new \Exception('Failure');
            }

            return $job;
        };

        // Job 2 errors, so only job 3 and 4 should be executed
        $this->expectOutputString('1234');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        yield each(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);
    }

    protected function tearDownAsync()
    {
        // Required to make testBackpressure fail instead of the following test
        \gc_collect_cycles();
    }
}
