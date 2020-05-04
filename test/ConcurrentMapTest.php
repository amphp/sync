<?php

namespace Amp\Sync\Test;

use Amp\Iterator;
use Amp\PHPUnit\AsyncTestCase;
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

    public function testBackpressure(): \Generator
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $iterator = concurrentMap(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Advancing once will process $concurrency more jobs, jobs 4 and 5 are expected to be not executed
        yield $iterator->advance();
    }
}
