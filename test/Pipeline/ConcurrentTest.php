<?php

namespace Amp\Sync\Pipeline;

use Amp\PHPUnit\AsyncTestCase;
use Amp\PHPUnit\TestException;
use Amp\Pipeline;
use Amp\Pipeline\Emitter;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Pipeline as SyncPipeline;
use function Amp\delay;

class ConcurrentTest extends AsyncTestCase
{
    public function testNoValuesEmitted(): void
    {
        $source = new Emitter;

        $pipeline = $source->pipe()->pipe(
            SyncPipeline\concurrent(
                new LocalSemaphore(3),
                Pipeline\map($this->createCallback(0)),
            )
        );

        $source->complete();

        self::assertSame(0, Pipeline\discard($pipeline));
    }

    public function testConcurrency(): void
    {
        $range = \range(0, 100);

        $source = Pipeline\fromIterable($range);

        $pipeline = $source->pipe(
            SyncPipeline\concurrent(
                new LocalSemaphore(10),
                Pipeline\tap(fn (int $value) => delay(\random_int(0, 10) / 1000)),
            )
        );

        $results = \iterator_to_array($pipeline);

        self::assertNotSame($range, $results); // Arrays should not match as values should be randomly ordered.

        foreach ($range as $value) {
            self::assertContains(needle: $value, haystack: $results);
        }
    }

    public function testPipelineFails(): void
    {
        $exception = new TestException;
        $source = new Emitter;

        $pipeline = $source->pipe()->pipe(
            SyncPipeline\concurrent(
                new LocalSemaphore(3),
                Pipeline\tap($this->createCallback(1)),
            )
        );

        $source->emit(1)->ignore();

        $source->error($exception);

        self::assertSame(1, $pipeline->continue());

        $this->expectExceptionObject($exception);

        $pipeline->continue();
    }

    public function testDestinationDisposed(): void
    {
        $range = \range(0, 100);

        $source = Pipeline\fromIterable($range);

        $pipeline = $source->pipe(
            SyncPipeline\concurrent(
                new LocalSemaphore(3),
                Pipeline\tap($this->createCallback(3)),
            )
        );

        self::assertSame(0, $pipeline->continue());
    }
}
