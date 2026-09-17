<?php

namespace Functional\Tickets\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * A media type outside the accepted families always means the same thing to a client — the
 * file itself is unprocessable — so the exception carries the 422 instead of being mapped
 * to one at the boundary.
 */
class UnsupportedAttachmentTypeException extends UnprocessableEntityHttpException
{
    public function __construct(?string $mimeType)
    {
        parent::__construct(__('tickets::messages.attachments.error.unsupported_type', [
            'type' => $mimeType ?? 'unknown',
        ]));
    }
}
