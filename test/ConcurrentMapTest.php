<?php

namespace Amp\Sync\Test;

use Amp\Iterator;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use Amp\Sync\LocalSemaphore;
use function Amp\delay;
use function Amp\Iterator\toArray;
use function Amp\Sync\concurrentMap;

class ConcurrentMapTest extends AsyncTestCase
{
    public function test(): \Generator
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;
        };

        $this->assertSame(
            [null, null, null],
            yield toArray(concurrentMap(Iterator\fromIterable([1, 2, 3]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrder(): \Generator
    {
        $processor = static function ($job) {
            yield delay($job * 100);

            return $job;
        };

        $this->assertSame(
            [1, 2, 3],
            yield toArray(concurrentMap(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrderWithoutConcurrency(): \Generator
    {
        $processor = static function ($job) {
            yield delay($job * 100);

            return $job;
        };

        $this->assertSame(
            [3, 2, 1],
            yield toArray(concurrentMap(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(1), $processor))
        );
    }

    public function testBackpressure(): void
    {
        $this->expectOutputString('12');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        concurrentMap(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);
    }

    public function testBackpressurePartialConsume1(): \Generator
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $iterator = concurrentMap(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        yield $iterator->advance();
    }

    public function testBackpressurePartialConsume2(): \Generator
    {
        $this->expectOutputString('1234');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $iterator = concurrentMap(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

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

            return $job;
        };

        $iterator = concurrentMap(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Job 2 errors, so only job 3 and 4 should be executed
        $this->expectOutputString('1234');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        yield Iterator\discard($iterator);
    }

    protected function tearDownAsync()
    {
        // Required to make testBackpressure fail instead of the following test
        \gc_collect_cycles();
    }
}
