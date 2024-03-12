<?php declare(strict_types=1);

use Amp\Future;
use Amp\Sync\Channel;
use function Amp\async;
use function Amp\delay;
use function Amp\Sync\createChannelPair;

require __DIR__ . '/../vendor/autoload.php';

/**
 * @var Channel<int, int> $left
 * @var Channel<int, int> $right
 */
[$left, $right] = createChannelPair();

$future1 = async(function () use ($left): void {
    echo "Coroutine 1 started\n";
    delay(1); // Delay to simulate I/O.
    $left->send(42);
    $received = $left->receive();
    echo "Received ", $received, " in coroutine 1\n";
});

$future2 = async(function () use ($right): void {
    echo "Coroutine 2 started\n";
    $received = $right->receive();
    echo "Received ", $received, " in coroutine 2\n";
    delay(1); // Delay to simulate I/O.
    $right->send($received * 2);
});

Future\await([$future1, $future2]);
