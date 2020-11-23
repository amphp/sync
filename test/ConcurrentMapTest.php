<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalSemaphore;
use function Amp\await;
use function Amp\delay;
use function Amp\Pipeline\discard;
use function Amp\Pipeline\fromIterable;
use function Amp\Pipeline\toArray;
use function Amp\Sync\ConcurrentPipeline\map;

class ConcurrentMapTest extends AsyncTestCase
{
    public function test(): void
    {
        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        self::assertSame(
            [1, 2, 3],
            toArray(map(fromIterable([1, 2, 3]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrder(): void
    {
        $processor = static function ($job) {
            delay($job * 100);

            return $job;
        };

        self::assertSame(
            [1, 2, 3],
            toArray(map(fromIterable([3, 2, 1]), new LocalSemaphore(3), $processor))
        );
    }

    public function testOutputOrderWithoutConcurrency(): void
    {
        $processor = static function ($job) {
            delay($job * 100);

            return $job;
        };

        self::assertSame(
            [3, 2, 1],
            toArray(map(fromIterable([3, 2, 1]), new LocalSemaphore(1), $processor))
        );
    }

    public function testBackpressure(): void
    {
        $this->setMinimumRuntime(300);
        $this->setTimeout(350);
        $this->ignoreLoopWatchers();

        $processor = static function () {
            delay(100);
            return true;
        };

        $pipeline = map(fromIterable([1, 2, 3, 4, 5, 6]), new LocalSemaphore(2), $processor);

        await(discard($pipeline));
    }

    public function testBackpressurePartialConsume1(): void
    {
        $this->ignoreLoopWatchers();

        $this->expectOutputString('123');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $pipeline = map(fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        $pipeline->continue();
        $pipeline->continue();
    }

    public function testBackpressurePartialConsume2(): void
    {
        $this->ignoreLoopWatchers();

        $this->expectOutputString('1234');

        $processor = static function ($job) {
            print $job;

            return $job;
        };

        $iterator = map(fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        $iterator->continue();
        $iterator->continue();
        $iterator->continue();
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

        $pipeline = map(fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Job 2 errors, so only jobs 1, 2, and 3 should be executed
        $this->expectOutputString('123');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        $pipeline->continue();
        $pipeline->continue();
        $pipeline->continue();
        $pipeline->continue();
    }

    public function testErrorHandlingCompletesPending(): void
    {
        $this->ignoreLoopWatchers();

        $processor = static function ($job) {
            print $job;

            if ($job === 2) {
                throw new \Exception('Failure');
            }

            delay(0);
            delay(0);

            print $job;

            return $job;
        };

        $pipeline = map(fromIterable([1, 2, 3, 4, 5]), new LocalSemaphore(2), $processor);

        // Job 2 errors, so only jobs 1, 2, and 3 should be executed
        $this->expectOutputString('121');
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failure');

        $pipeline->continue();
        $pipeline->continue();
        $pipeline->continue();
    }

    protected function tearDownAsync(): void
    {
        // Required to make testBackpressure fail instead of the following test
        \gc_collect_cycles();
    }
}
