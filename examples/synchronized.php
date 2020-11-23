<?php

use Amp\Sync\LocalMutex;
use function Amp\async;
use function Amp\await;
use function Amp\delay;
use function Amp\Promise\all;
use function Amp\Sync\synchronized;

require __DIR__ . '/../vendor/autoload.php';

$mutex = new LocalMutex;

$task = function (string $identifier) use ($mutex) {
    print "[$identifier] Starting" . \PHP_EOL;

    for ($i = 0; $i < 3; $i++) {
        print "[$identifier][$i] Acquiring lock" . \PHP_EOL;

        synchronized($mutex, function () use ($identifier, $i) {
            print "[$identifier][$i] Acquired lock" . \PHP_EOL;

            // do anything exclusively
            delay(\random_int(0, 1000));
        });
    }

    print "[$identifier] Finished" . \PHP_EOL;
};

$promiseA = async($task, 'A');
$promiseB = async($task, 'B');
$promiseC = async($task, 'C');

await(all([$promiseA, $promiseB, $promiseC]));