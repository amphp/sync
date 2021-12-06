<?php

namespace Amp\Sync;

use function Amp\delay;

/**
 * A non-blocking, inter-process POSIX semaphore.
 *
 * Uses a POSIX message queue to store a queue of permits in a lock-free data structure. This semaphore implementation
 * is preferred over other implementations when available, as it provides the best performance.
 *
 * Not compatible with Windows.
 */
final class PosixSemaphore implements Semaphore
{
    private const LATENCY_TIMEOUT = 0.01;

    /**
     * Creates a new semaphore with a given ID and number of locks.
     *
     * @param string $id The unique name for the new semaphore.
     * @param int $maxLocks The maximum number of locks that can be acquired from the semaphore.
     * @param int $permissions Permissions to access the semaphore. Use file permission format specified as 0xxx.
     *
     * @return PosixSemaphore
     * @throws SyncException If the semaphore could not be created due to an internal error.
     */
    public static function create(string $id, int $maxLocks, int $permissions = 0600): self
    {
        if ($maxLocks < 1) {
            throw new \Error('Number of locks must be greater than 0, got ' . $maxLocks);
        }

        $semaphore = new self($id);
        $semaphore->init($maxLocks, $permissions);

        return $semaphore;
    }

    /**
     * @param string $id The unique name of the semaphore to use.
     *
     * @return PosixSemaphore
     */
    public static function use(string $id): self
    {
        $semaphore = new self($id);
        $semaphore->open();

        return $semaphore;
    }

    private static function makeKey(string $id): int
    {
        return \abs(\unpack("l", \md5($id, true))[1]);
    }

    private string $id;
    /** @var int The semaphore key. */
    private int $key;
    /** @var int PID of the process that created the semaphore. */
    private int $initializer = 0;
    /** @var \SysvMessageQueue A message queue of available locks. */
    private \SysvMessageQueue $queue;

    /**
     * @param string $id
     *
     * @throws \Error If the sysvmsg extension is not loaded.
     */
    private function __construct(string $id)
    {
        if (!\extension_loaded("sysvmsg")) {
            throw new \Error(__CLASS__ . " requires the sysvmsg extension.");
        }

        $this->id = $id;
        $this->key = self::makeKey($this->id);
    }

    /**
     * Throws to prevent serialization.
     */
    public function __sleep(): array
    {
        throw new \Error('Cannot serialize ' . self::class);
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the access permissions of the semaphore.
     *
     * @return int A permissions mode.
     */
    public function getPermissions(): int
    {
        $stat = \msg_stat_queue($this->queue);
        return $stat['msg_perm.mode'];
    }

    /**
     * Sets the access permissions of the semaphore.
     *
     * The current user must have access to the semaphore in order to change the permissions.
     *
     * @param int $mode A permissions mode to set.
     *
     * @throws SyncException If the operation failed.
     */
    public function setPermissions(int $mode): void
    {
        if (!\msg_set_queue($this->queue, ['msg_perm.mode' => $mode])) {
            throw new SyncException('Failed to change the semaphore permissions.');
        }
    }

    public function acquire(): Lock
    {
        do {
            // Attempt to acquire a lock from the semaphore.
            if (@\msg_receive($this->queue, 0, $type, 1, $message, false, \MSG_IPC_NOWAIT, $errno)) {
                // A free lock was found, so resolve with a lock object that can
                // be used to release the lock.
                return new Lock(fn () => $this->release());
            }

            // Check for unusual errors.
            if ($errno !== \MSG_ENOMSG) {
                throw new SyncException(\sprintf('Failed to acquire a lock; errno: %d', $errno));
            }

            delay(self::LATENCY_TIMEOUT);
        } while (true);
    }

    /**
     * Removes the semaphore if it still exists.
     *
     * @throws SyncException If the operation failed.
     */
    public function __destruct()
    {
        if ($this->initializer === 0 || $this->initializer !== \getmypid()) {
            return;
        }

        if (!$this->queue instanceof \SysvMessageQueue) {
            return;
        }

        if (!\msg_queue_exists($this->key)) {
            return;
        }

        \msg_remove_queue($this->queue);
    }

    /**
     * Releases a lock from the semaphore.
     *
     * @throws SyncException If the operation failed.
     */
    protected function release(): void
    {
        if (!$this->queue) {
            return; // Queue already destroyed.
        }

        // Send in non-blocking mode. If the call fails because the queue is full,
        // then the number of locks configured is too large.
        if (!@\msg_send($this->queue, 1, "\0", false, false, $errno)) {
            if ($errno === \MSG_EAGAIN) {
                throw new SyncException('The semaphore size is larger than the system allows.');
            }

            throw new SyncException('Failed to release the lock.');
        }
    }

    /**
     * Clone method throws to prevent clone.
     */
    public function __clone()
    {
        throw new \Error("Cloning is not allowed!");
    }

    private function open(): void
    {
        if (!\msg_queue_exists($this->key)) {
            throw new SyncException('No semaphore with that ID found');
        }

        $queue = \msg_get_queue($this->key);
        if (!$queue) {
            throw new SyncException('Failed to open the semaphore.');
        }

        $this->queue = $queue;
    }

    /**
     * @param int $maxLocks The maximum number of locks that can be acquired from the semaphore.
     * @param int $permissions Permissions to access the semaphore.
     *
     * @throws SyncException If the semaphore could not be created due to an internal error.
     */
    private function init(int $maxLocks, int $permissions): void
    {
        if (\msg_queue_exists($this->key)) {
            throw new SyncException('A semaphore with that ID already exists');
        }

        $queue = \msg_get_queue($this->key, $permissions);
        if (!$queue) {
            throw new SyncException('Failed to create the semaphore.');
        }

        $this->queue = $queue;
        $this->initializer = \getmypid();

        // Fill the semaphore with locks.
        while (--$maxLocks >= 0) {
            $this->release();
        }
    }
}
