<?php

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\all;

class SynchronizedTest extends AsyncTestCase
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
        foreach (\range(0, 2) as $value) {
            $futures[] = async(fn () => synchronized($mutex, $callback, $value));
        }

        $result = all($futures);
        self::assertSame(\range(0, 2), $result);
    }
}
