<?php

use Amp\Emitter;
use Amp\Sync\LocalSemaphore;
use function Amp\delay;
use function Amp\Promise\wait;
use function Amp\Sync\Concurrent\each;

require __DIR__ . '/../vendor/autoload.php';

$emitter = new Emitter;

$jobId = 0;

for ($i = 0; $i < 10; $i++) {
    print 'enqueued ' . $jobId . \PHP_EOL;
    $emitter->emit($jobId++);
}

wait(each(
    $emitter->iterate(),
    new LocalSemaphore(3),
    static function ($job) use ($emitter, &$jobId) {
        print 'starting ' . $job . \PHP_EOL;

        yield delay(1000);

        if ($job < 10) {
            if (\random_int(0, 1)) {
                print 'enqueued ' . $jobId . \PHP_EOL;
                $emitter->emit($jobId++);
            }
        } elseif ($job === 10) {
            $emitter->complete();
        }

        print 'finished ' . $job . \PHP_EOL;
    }
));