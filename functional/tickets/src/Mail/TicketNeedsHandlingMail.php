<?php

namespace Functional\Tickets\Mail;

use Functional\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketNeedsHandlingMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Ticket $ticket)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('tickets::notifications.ticket_needs_handling.subject', ['title' => $this->ticket->title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'tickets::mail.ticket-needs-handling',
            with: ['ticket' => $this->ticket],
        );
    }
}
