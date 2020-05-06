<?php

namespace Amp\Sync\Test;

use Amp\Iterator;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalSemaphore;
use function Amp\delay;
use function Amp\Iterator\toArray;
use function Amp\Sync\Concurrent\filter;

class ConcurrentFilterTest extends AsyncTestCase
{
    public function test(): \Generator
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return $job === 2;
        };

        $this->assertSame(
            [2],
            yield toArray(filter(Iterator\fromIterable([1, 2, 3]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrder(): \Generator
    {
        $processor = static function ($job) {
            yield delay($job * 100);

            return true;
        };

        $this->assertSame(
            [1, 2, 3],
            yield toArray(filter(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrderWithoutConcurrency(): \Generator
    {
        $processor = static function ($job) {
            yield delay($job * 100);

            return true;
        };

        $this->assertSame(
            [3, 2, 1],
            yield toArray(filter(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(1), $processor))
        );
    }

    public function testBackpressure(): void
    {
        $this->expectOutputString('12');

        $processor = static function ($job) {
            print $job;

            return true;
        };

        filter(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);
    }

    public function testBackpressurePartialConsume1(): \Generator
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return true;
        };

        $iterator = filter(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        yield $iterator->advance();
    }

    public function testBackpressurePartialConsume2(): \Generator
    {
        $this->expectOutputString('1234');

        $processor = static function ($job) {
            print $job;

            return true;
        };

        $iterator = filter(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        yield $iterator->advance();
        yield $iterator->advance();
    }

    public function testErrorHandling(): \Generator
    {
        $processor = static function ($job) {
            print $job;

            yield delay(0);

            if ($job === 2) {
                throw new \Exception('Failure');
            }

            return true;
        };

        $iterator = filter(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Job 2 errors, so only job 3 and 4 should be executed
        $this->expectOutputString('1234');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        yield $iterator->advance();
        yield $iterator->advance();
        yield $iterator->advance();
        yield $iterator->advance();
    }

    protected function tearDownAsync()
    {
        // Required to make testBackpressure fail instead of the following test
        \gc_collect_cycles();
    }
}
