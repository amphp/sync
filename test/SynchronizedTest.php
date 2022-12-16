<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\PHPUnit\AsyncTestCase;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

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

        self::assertEquals([0, 1, 2], await($futures));
    }
}
