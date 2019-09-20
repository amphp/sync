<?php

namespace Amp\Sync\Test;

use Amp\Delayed;
use Amp\Loop;
use Amp\PHPUnit\TestCase;
use Amp\Sync\LocalMutex;
use function Amp\Sync\synchronized;

class SynchronizedTest extends TestCase
{
    public function testSynchronized()
    {
        $this->assertRunTimeGreaterThan(function () {
            Loop::run(function () {
                $mutex = new LocalMutex;
                $callback = function (int $value) {
                    return yield new Delayed(100, $value);
                };

                $promises = [];
                foreach (\range(0, 2) as $value) {
                    $promises[] = synchronized($mutex, $callback, $value);
                }
                $result = yield $promises;
                $this->assertSame(\range(0, 2), $result);
            });
        }, 300);
    }
}
