<?php declare(strict_types=1);

use Amp\Future;
use Amp\Sync\LocalMutex;
use Amp\Sync\LocalParcel;
use function Amp\async;
use function Amp\delay;

require __DIR__ . '/../vendor/autoload.php';

/** @var LocalParcel<int> $parcel */
$parcel = new LocalParcel(new LocalMutex(), 42);

$future1 = async(function () use ($parcel): void {
    echo "Coroutine 1 started\n";

    $value = $parcel->synchronized(function (int $value): int {
        delay(1); // Delay for 1s to simulate I/O.
        return $value * 2;
    });

    echo "Value after access in coroutine 1: ", $value, "\n";
});

$future2 = async(function () use ($parcel): void {
    echo "Coroutine 2 started\n";

    $value = $parcel->synchronized(function (int $value): int {
        delay(1); // Delay again in this coroutine.
        return $value + 8;
    });

    echo "Value after access in coroutine 2: ", $value, "\n";
});

Future\await([$future1, $future2]);
