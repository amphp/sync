<?php

use Amp\Emitter;
use Amp\PipelineSource;
use Amp\Sync\LocalSemaphore;
use function Amp\delay;
use function Amp\Promise\wait;
use function Amp\Sync\ConcurrentPipeline\each;

require __DIR__ . '/../vendor/autoload.php';

$source = new PipelineSource;

$jobId = 0;

for ($i = 0; $i < 10; $i++) {
    print 'enqueued ' . $jobId . \PHP_EOL;
    $source->emit($jobId++);
}

each(
    $source->pipe(),
    new LocalSemaphore(3),
    static function ($job) use ($source, &$jobId) {
        print 'starting ' . $job . \PHP_EOL;

        delay(1000);

        if ($job < 10) {
            if (\random_int(0, 1)) {
                print 'enqueued ' . $jobId . \PHP_EOL;
                $source->emit($jobId++);
            }
        } elseif ($job === 10) {
            $source->complete();
        }

        print 'finished ' . $job . \PHP_EOL;
    }
);