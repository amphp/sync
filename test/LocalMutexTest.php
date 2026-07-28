<?php declare(strict_types=1);

namespace Amp\Sync;

class LocalMutexTest extends AbstractMutexTest
{
    #[\Override]
    protected function createMutex(): Mutex
    {
        return new LocalMutex();
    }
}
