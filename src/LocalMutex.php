<?php

namespace Amp\Sync;

use Amp\Deferred;
use function Amp\await;

class LocalMutex implements Mutex
{
    /** @var bool */
    private bool $locked = false;

    /** @var Deferred[] */
    private array $queue = [];

    /** {@inheritdoc} */
    public function acquire(): Lock
    {
        if (!$this->locked) {
            $this->locked = true;
            return new Lock(0, \Closure::fromCallable([$this, 'release']));
        }

        $this->queue[] = $deferred = new Deferred;
        return await($deferred->promise());
    }

    private function release(): void
    {
        if (!empty($this->queue)) {
            $deferred = \array_shift($this->queue);
            $deferred->resolve(new Lock(0, \Closure::fromCallable([$this, 'release'])));
            return;
        }

        $this->locked = false;
    }
}
