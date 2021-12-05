<?php

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\all;

final class SynchronizedTest extends AsyncTestCase
{
    public function testSynchronized(): void
    {
        $this->setMinimumRuntime(0.3);

        $mutex = new LocalMutex;
        $callback = function (int $value): int {
            delay(0.1);

            return $value;
        };

        $futures = [];
        foreach ([0, 1, 2] as $value) {
            $futures[] = async(fn () => synchronized($mutex, $callback, $value));
        }

        self::assertSame([0, 1, 2], all($futures));
    }
}
