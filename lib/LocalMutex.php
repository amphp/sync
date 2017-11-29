<?php

namespace Amp\Sync;

use Amp\CallableMaker;
use Amp\Deferred;
use Amp\Promise;
use Amp\Success;

class LocalMutex implements Mutex {
    use CallableMaker;

    /** @var bool */
    private $lock = true;

    /** @var \Amp\Deferred[] */
    private $queue = [];

    /** @var callable */
    private $release;

    public function __construct() {
        $this->release = $this->callableFromInstanceMethod("release");
    }

    /** {@inheritdoc} */
    public function acquire(): Promise {
        if ($this->lock) {
            $this->lock = false;
            return new Success(new Lock($this->release));
        }

        $this->queue[] = $deferred = new Deferred;
        return $deferred->promise();
    }

    private function release() {
        if (!empty($this->queue)) {
            $deferred = \array_shift($this->queue);
            $deferred->resolve(new Lock($this->release));
            return;
        }

        $this->lock = true;
    }
}
