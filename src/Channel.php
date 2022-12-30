<?php declare(strict_types=1);

namespace Amp\Sync;

use Amp\Cancellation;
use Amp\Closable;
use Amp\Serialization\SerializationException;

/**
 * Interface for sending messages between execution contexts, such as two coroutines or two processes.
 *
 * @template-covariant TReceive
 * @template TSend
 *
 * @extends \Traversable<int, TReceive>
 */
interface Channel extends Closable, \Traversable
{
    /**
     * @param Cancellation|null $cancellation Cancels waiting for the next value. Note the next value is not discarded
     * if the operation is cancelled, rather it will be returned from the next call to this method.
     *
     * @return TReceive|null Data received or {@code null} if the channel has closed.
     *
     * @throws ChannelException If receiving from the channel fails.
     * @throws SerializationException If the underlying transport mechanism uses serialization and fails.
     */
    public function receive(?Cancellation $cancellation = null): mixed;

    /**
     * @param TSend $data Any value other than {@code null}.
     *
     * @throws ChannelException If sending on the channel fails or the channel is already closed.
     * @throws SerializationException If the underlying transport mechanism uses serialization and fails.
     */
    public function send(mixed $data): void;
}
