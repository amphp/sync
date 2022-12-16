<?php declare(strict_types=1);

namespace Amp\Sync;

class LocalMutexTest extends AbstractMutexTest
{
    public function createMutex(): Mutex
    {
        return new LocalMutex;
    }
}
