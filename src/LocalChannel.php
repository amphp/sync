<?php

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\Pipeline\Queue;

/**
 * Creates a channel where data sent is immediately receivable on the same channel.
 *
 * @template TValue
 * @template-implements Channel<TValue, TValue>
 */
final class LocalChannel implements Channel
{
    /** @var ConcurrentIteratorChannel<TValue, TValue> */
    private ConcurrentIteratorChannel $channel;

    public function __construct(int $capacity = 0)
    {
        $queue = new Queue($capacity);
        $this->channel = new ConcurrentIteratorChannel($queue->iterate(), $queue);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function isClosed(): bool
    {
        return $this->channel->isClosed();
    }

    public function close(): void
    {
        $this->channel->close();
    }

    public function receive(?Cancellation $cancellation = null): mixed
    {
        return $this->channel->receive($cancellation);
    }

    public function send(mixed $data): void
    {
        $this->channel->send($data);
    }
}
