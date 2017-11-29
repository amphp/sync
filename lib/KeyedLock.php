<?php

namespace Amp\Sync;

class KeyedLock extends Lock {
    /** @var int Lock key identifier. */
    private $key;

    /**
     * Creates a new lock permit object.
     *
     * @param int $key Lock key identifier.
     * @param callable<Lock> $releaser A function to be called upon release.
     */
    public function __construct(int $key, callable $releaser) {
        $this->key = $key;
        parent::__construct($releaser);
    }

    /**
     * @return int
     */
    public function getKey(): int {
        return $this->key;
    }
}
