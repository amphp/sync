<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\Sync\Semaphore;
use Amp\Sync\ThreadedSemaphore;

/**
 * @requires extension pthreads
 */
class ThreadedSemaphoreTest extends AbstractSemaphoreTest {
    public function createSemaphore(int $locks): Semaphore {
        return new ThreadedSemaphore($locks);
    }

    public function testWithinThread() {
        $semaphore = $this->createSemaphore(1);

        $thread = new class($semaphore) extends \Thread {
            private $semaphore;

            public function __construct(Semaphore $semaphore) {
                $this->semaphore = $semaphore;
            }

            public function run() {
                Loop::set((new Loop\DriverFactory)->create());
                Loop::run(function () {
                    $this->semaphore->acquire()->onResolve(function ($exception, $lock) {
                        if ($exception) {
                            throw $exception;
                        }

                        Loop::delay(100, [$lock, "release"]);
                    });
                });
            }
        };

        $this->assertRunTimeGreaterThan(function () use ($semaphore, $thread) {
            $thread->start();

            Loop::run(function () use ($semaphore) {
                $lock = yield $semaphore->acquire();
                Loop::delay(100, [$lock, "release"]);
            });
        }, 200);
    }
}
