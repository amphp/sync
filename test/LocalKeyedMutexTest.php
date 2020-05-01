<?php

namespace Amp\Sync\Tests;

use Amp\Sync\KeyedMutex;
use Amp\Sync\LocalKeyedMutex;
use Amp\Sync\Test\AbstractKeyedMutexTest;

class LocalKeyedMutexTest extends AbstractKeyedMutexTest
{
    public function createMutex(): KeyedMutex
    {
        return new LocalKeyedMutex();
    }
}
