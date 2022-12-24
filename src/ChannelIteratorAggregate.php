<?php declare(strict_types=1);

namespace Amp\Sync;

/**
 * @template TReceive
 */
trait ChannelIteratorAggregate
{
    /**
     * @see Channel::receive()
     * @return TReceive|null
     */
    abstract public function receive(): mixed;

    /**
     * @return \Traversable<int, TReceive>
     */
    public function getIterator(): \Traversable
    {
        while (($received = $this->receive()) !== null) {
            yield $received;
        }
    }
}
