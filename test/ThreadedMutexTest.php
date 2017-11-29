<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\Sync\Mutex;
use Amp\Sync\ThreadedMutex;

/**
 * @requires extension pthreads
 */
class ThreadedMutexTest extends AbstractMutexTest {
    public function createMutex(): Mutex {
        return new ThreadedMutex;
    }

    public function testWithinThread() {
        $mutex = $this->createMutex();

        $thread = new class($mutex) extends \Thread {
            private $mutex;

            public function __construct(Mutex $mutex) {
                $this->mutex = $mutex;
            }

            public function run() {
                Loop::set((new Loop\DriverFactory)->create());
                Loop::run(function () {
                    $this->mutex->acquire()->onResolve(function ($exception, $lock) {
                        if ($exception) {
                            throw $exception;
                        }

                        Loop::delay(100, [$lock, "release"]);
                    });
                });
            }
        };

        $this->assertRunTimeGreaterThan(function () use ($mutex, $thread) {
            $thread->start();

            Loop::run(function () use ($mutex) {
                $lock = yield $mutex->acquire();
                Loop::delay(100, [$lock, "release"]);
            });
        }, 200);
    }
}
