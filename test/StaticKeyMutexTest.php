<?php declare(strict_types=1);

namespace Amp\Sync;

class StaticKeyMutexTest extends AbstractMutexTest
{
    public function createMutex(): Mutex
    {
        return new StaticKeyMutex(new LocalKeyedMutex(), 'key');
    }
}
