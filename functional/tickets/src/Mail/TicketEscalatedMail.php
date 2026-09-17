<?php

namespace Functional\Tickets\Mail;

use Functional\Tickets\Enums\TicketPriority;
use Functional\Tickets\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketEscalatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketPriority $breachedPriority,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('tickets::notifications.ticket_escalated.subject', ['title' => $this->ticket->title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'tickets::mail.ticket-escalated',
            with: [
                'ticket' => $this->ticket,
                'breachedPriority' => $this->breachedPriority,
            ],
        );
    }
}
