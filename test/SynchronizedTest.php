<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalMutex;
use function Amp\Future\all;
use function Amp\Future\spawn;
use function Amp\Sync\synchronized;
use function Revolt\EventLoop\delay;

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
            $futures[] = spawn(fn () => synchronized($mutex, $callback, $value));
        }

        $result = all($futures);
        self::assertSame(\range(0, 2), $result);
    }
}
