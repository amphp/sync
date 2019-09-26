<?php

namespace Amp\Sync\Test;

use Amp\Sync\KeyedMutex;
use Amp\Sync\LocalKeyedMutex;

class LocalKeyedMutexTest extends AbstractKeyedMutexTest
{
    public function createMutex(): KeyedMutex
    {
        return new LocalKeyedMutex();
    }
}
