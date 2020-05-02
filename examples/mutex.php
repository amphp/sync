<?php

use Amp\Sync\LocalMutex;
use Amp\Sync\Lock;
use function Amp\call;
use function Amp\delay;
use function Amp\Promise\all;
use function Amp\Promise\wait;

require __DIR__ . '/../vendor/autoload.php';

$mutex = new LocalMutex;

$task = function (string $identifier) use ($mutex) {
    print "[$identifier] Starting" . \PHP_EOL;

    for ($i = 0; $i < 3; $i++) {
        print "[$identifier][$i] Acquiring lock" . \PHP_EOL;

        /** @var Lock $lock */
        $lock = yield $mutex->acquire();

        try {
            print "[$identifier][$i] Acquired lock" . \PHP_EOL;

            // do anything exclusively
            yield delay(\random_int(0, 1000));
        } finally {
            print "[$identifier][$i] Releasing lock" . \PHP_EOL;

            yield $lock->release();
        }
    }

    print "[$identifier] Finished" . \PHP_EOL;
};

$promiseA = call($task, 'A');
$promiseB = call($task, 'B');
$promiseC = call($task, 'C');

wait(all([$promiseA, $promiseB, $promiseC]));
