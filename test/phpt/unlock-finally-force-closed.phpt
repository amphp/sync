--TEST--
unlock in force closed finally
--SKIPIF--
<?php
if (\PHP_VERSION_ID >= 80400) {
    echo 'skip behavior differs from unlock-finally.phpt only before PHP 8.4';
}
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
