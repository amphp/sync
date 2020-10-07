<?php

namespace Amp\Sync\Test;

use Amp\Iterator;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalSemaphore;
use function Amp\delay;
use function Amp\Iterator\toArray;
use function Amp\Sync\ConcurrentIterator\map;

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
            yield toArray(map(Iterator\fromIterable([1, 2, 3]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrder(): \Generator
    {
        $processor = static function ($job) {
            delay($job * 100);

            return $job;
        };

        $this->assertSame(
            [1, 2, 3],
            yield toArray(map(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrderWithoutConcurrency(): \Generator
    {
        $processor = static function ($job) {
            delay($job * 100);

            return $job;
        };

        $this->assertSame(
            [3, 2, 1],
            yield toArray(map(Iterator\fromIterable([3, 2, 1]), new LocalSemaphore(1), $processor))
        );
    }

    public function testBackpressure(): \Generator
    {
        $this->setMinimumRuntime(300);
        $this->setTimeout(350);
        $this->ignoreLoopWatchers();

        $processor = static function ($job) {
            delay(100);
            return true;
        };

        $iterator = map(Iterator\fromIterable([1, 2, 3, 4, 5, 6]), new LocalSemaphore(2), $processor);

        while (yield $iterator->advance());
    }

    public function testBackpressurePartialConsume1(): \Generator
    {
        $this->ignoreLoopWatchers();

        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $iterator = map(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        yield $iterator->advance();

        yield $iterator->advance();
    }

    public function testBackpressurePartialConsume2(): \Generator
    {
        $this->ignoreLoopWatchers();

        $this->expectOutputString('1234');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $iterator = map(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        yield $iterator->advance();
        yield $iterator->advance();
        yield $iterator->advance();
    }

    public function testErrorHandling(): \Generator
    {
        $processor = static function ($job) {
            print $job;

            delay(0);

            if ($job === 2) {
                throw new \Exception('Failure');
            }

            return $job;
        };

        $iterator = map(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Job 2 errors, so only jobs 1, 2, and 3 should be executed
        $this->expectOutputString('123');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        yield $iterator->advance();
        yield $iterator->advance();
        yield $iterator->advance();
        yield $iterator->advance();
    }

    public function testErrorHandlingCompletesPending(): \Generator
    {
        $this->ignoreLoopWatchers();

        $processor = static function ($job) {
            print $job;

            if ($job === 2) {
                throw new \Exception('Failure');
            }

            delay(0);

            return $job;
        };

        $iterator = map(Iterator\fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Job 2 errors, so only jobs 1, 2, and 3 should be executed
        $this->expectOutputString('123');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

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
