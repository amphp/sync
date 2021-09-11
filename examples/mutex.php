<?php

use Amp\Sync\LocalMutex;
use function Amp\Future\all;
use function Amp\Future\spawn;
use function Revolt\EventLoop\delay;

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

$promiseA = spawn(fn () => $task('A'));
$promiseB = spawn(fn () => $task('B'));
$promiseC = spawn(fn () => $task('C'));

all([$promiseA, $promiseB, $promiseC]);
