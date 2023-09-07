--TEST--
unlock in force closed finally
--FILE--
<?php

require __DIR__ . '/../../vendor/autoload.php';

$testFiber = new \Fiber(function () {
    while (true) {
        \Fiber::suspend();
    }
});

$testFiber->start();

$lock = new \Amp\Sync\Lock(function () use ($testFiber) {
    $testFiber->resume();

    echo 'unlock ';
});

$fiber = new Fiber(function () use ($lock) {
    try {
        \Fiber::suspend();
    } finally {
        $lock->release();
    }
});

$lock = null;
echo 'lock=null ';

$fiber->start();
echo 'fiber.start ';

$fiber = null;
echo 'fiber=null ';

?>
--EXPECT--
lock=null fiber.start fiber=null unlock