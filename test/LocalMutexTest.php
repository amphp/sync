<?php

namespace Amp\Sync;

class LocalMutexTest extends AbstractMutexTest
{
    public function createMutex(): Mutex
    {
        return new LocalMutex;
    }
}
