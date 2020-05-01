<?php

namespace Amp\Sync\Tests;

use Amp\Sync\FileMutex;
use Amp\Sync\Mutex;
use Amp\Sync\Test\AbstractMutexTest;

class FileMutexTest extends AbstractMutexTest
{
    public function createMutex(): Mutex
    {
        return new FileMutex(\tempnam(\sys_get_temp_dir(), 'mutex-') . '.lock');
    }
}
