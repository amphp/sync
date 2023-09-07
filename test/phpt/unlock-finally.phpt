--TEST--
unlock in force closed finally
--FILE--
<?php

require __DIR__ . '/../../vendor/autoload.php';

$lock = new \Amp\Sync\Lock(function () {
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

$fiber->resume();

$fiber = null;
echo 'fiber=null ';

?>
--EXPECT--
lock=null fiber.start unlock fiber=null