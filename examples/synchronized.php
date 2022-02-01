<?php

use Amp\Sync\LocalMutex;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;
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
            delay(\random_int(0, 1000) / 1000);
        });
    }

    print "[$identifier] Finished" . \PHP_EOL;
};

$futureA = async(fn () => $task('A'));
$futureB = async(fn () => $task('B'));
$futureC = async(fn () => $task('C'));

await([$futureA, $futureB, $futureC]);
