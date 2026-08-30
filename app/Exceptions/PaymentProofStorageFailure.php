<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Raised when a payment proof could not be written to storage.
 *
 * A booking must never be persisted claiming a proof that does not exist, so
 * this aborts the submission rather than letting it through.
 */
class PaymentProofStorageFailure extends RuntimeException
{
    public static function whileWriting(?Throwable $previous = null): self
    {
        return new self(
            'Your payment proof could not be saved. Please try uploading it again.',
            0,
            $previous
        );
    }
}
