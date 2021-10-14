<?php

use Amp\Sync\LocalMutex;
use function Amp\coroutine;
use function Amp\delay;
use function Amp\Future\all;

require __DIR__ . '/../vendor/autoload.php';

$mutex = new LocalMutex;

$task = function (string $identifier) use ($mutex) {
    print "[$identifier] Starting" . \PHP_EOL;

    for ($i = 0; $i < 3; $i++) {
        print "[$identifier][$i] Acquiring lock" . \PHP_EOL;

        $lock = $mutex->acquire();

        try {
            print "[$identifier][$i] Acquired lock" . \PHP_EOL;

            // do anything exclusively
            delay(\random_int(0, 1000) / 1000);
        } finally {
            print "[$identifier][$i] Releasing lock" . \PHP_EOL;

            $lock->release();
        }
    }

    print "[$identifier] Finished" . \PHP_EOL;
};

$promiseA = coroutine(fn () => $task('A'));
$promiseB = coroutine(fn () => $task('B'));
$promiseC = coroutine(fn () => $task('C'));

all([$promiseA, $promiseB, $promiseC]);
