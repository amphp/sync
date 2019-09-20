<?php

namespace Amp\Sync\Test;

use Amp\Delayed;
use Amp\Loop;
use Amp\Sync\Semaphore;
use Amp\Sync\ThreadedSemaphore;

/**
 * @requires extension pthreads
 */
class ThreadedSemaphoreTest extends AbstractSemaphoreTest
{
    public function createSemaphore(int $locks): Semaphore
    {
        return new ThreadedSemaphore($locks);
    }

    public function testWithinThread()
    {
        $semaphore = $this->createSemaphore(1);

        $thread = new class($semaphore) extends \Thread {
            private $semaphore;

            public function __construct(Semaphore $semaphore)
            {
                $this->semaphore = $semaphore;
            }

            public function run()
            {
                // Protect scope by using an unbound closure (protects static access as well).
                (static function () {
                    $paths = [
                        \dirname(__DIR__) . \DIRECTORY_SEPARATOR . "vendor" . \DIRECTORY_SEPARATOR . "autoload.php",
                        \dirname(__DIR__, 3) . \DIRECTORY_SEPARATOR . "autoload.php",
                    ];

                    foreach ($paths as $path) {
                        if (\file_exists($path)) {
                            $autoloadPath = $path;
                            break;
                        }
                    }

                    if (!isset($autoloadPath)) {
                        throw new \Error("Could not locate autoload.php");
                    }

                    require $autoloadPath;
                })->bindTo(null, null)();

                Loop::run(function () {
                    $lock = yield $this->semaphore->acquire();
                    Loop::delay(1000, [$lock, "release"]);
                });
            }
        };

        $this->assertRunTimeGreaterThan(function () use ($semaphore, $thread) {
            $thread->start(\PTHREADS_INHERIT_INI);

            Loop::run(function () use ($semaphore) {
                yield new Delayed(500); // Wait for thread to start and obtain lock.
                $lock = yield $semaphore->acquire();
                Loop::delay(100, [$lock, "release"]);
            });
        }, 1100);
    }
}
