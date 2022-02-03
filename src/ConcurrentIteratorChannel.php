<?php

namespace Amp\Sync;

use Amp\ByteStream\ClosableStream;
use Amp\Cancellation;
use Amp\Pipeline\ConcurrentIterator;
use Amp\Pipeline\Queue;

/**
 * Creates a Channel from a ConcurrentIterator and Queue. The ConcurrentIterator emits data to be received on the
 * channel (data emitted on the ConcurrentIterator will be returned from calls to {@see Channel::receive()}).
 * The Queue will receive data that sent on the channel (data passed to {@see Channel::send()} will be passed to
 * {@see Queue::push()}).
 *
 * @template TReceive
 * @template TSend
 * @template-implements Channel<TReceive, TSend>
 */
final class ConcurrentIteratorChannel implements Channel, ClosableStream
{
    /**
     * @param ConcurrentIterator<TReceive> $receive
     * @param Queue<TSend> $send
     */
    public function __construct(
        private ConcurrentIterator $receive,
        private Queue $send,
    ) {
    }

    public function __destruct()
    {
        $this->close();
    }

    public function isClosed(): bool
    {
        return $this->send->isComplete();
    }

    public function close(): void
    {
        if (!$this->send->isComplete()) {
            $this->send->complete();
        }

        $this->receive->dispose();
    }

    public function receive(?Cancellation $cancellation = null): mixed
    {
        if (!$this->receive->continue($cancellation)) {
            return null;
        }

        return $this->receive->getValue();
    }

    public function send(mixed $data): void
    {
        if ($data === null) {
            throw new ChannelException("Cannot send null on a channel");
        }

        if ($this->send->isComplete()) {
            throw new ChannelException("Cannot send on a closed channel");
        }

        $this->send->push($data);
    }
}
