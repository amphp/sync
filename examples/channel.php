<?php declare(strict_types=1);

use Amp\Future;
use function Amp\async;
use function Amp\delay;
use function Amp\Sync\createChannelPair;

require __DIR__ . '/../vendor/autoload.php';

[$left, $right] = createChannelPair();

$future1 = async(function () use ($left): void {
    echo "Coroutine 1 started\n";
    delay(1); // Delay to simulate I/O.
    $left->send(42);
    $value = $left->receive();
    echo "Received ", $value, " in coroutine 1\n";
});

$future2 = async(function () use ($right): void {
    echo "Coroutine 2 started\n";
    $value = $right->receive();
    echo "Received ", $value, " in coroutine 2\n";
    delay(1); // Delay to simulate I/O.
    $right->send($value * 2);
});

Future\await([$future1, $future2]);
