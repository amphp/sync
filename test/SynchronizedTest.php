<?php

namespace Amp\Sync\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Sync\LocalMutex;
use function Amp\async;
use function Amp\await;
use function Amp\Sync\synchronized;
use function Revolt\EventLoop\delay;

class SynchronizedTest extends AsyncTestCase
{
    public function testSynchronized(): void
    {
        $this->setMinimumRuntime(300);

        $mutex = new LocalMutex;
        $callback = function (int $value): int {
            delay(100);
            return $value;
        };

        $promises = [];
        foreach (\range(0, 2) as $value) {
            $promises[] = async(fn () => synchronized($mutex, $callback, $value));
        }

        $result = await($promises);
        self::assertSame(\range(0, 2), $result);
    }
}
