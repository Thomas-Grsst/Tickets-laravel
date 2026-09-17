<x-mail::message>
# {{ __('tickets::notifications.ticket_needs_handling.heading') }}

{{ __('tickets::notifications.ticket_needs_handling.intro', ['title' => $ticket->title]) }}

- {{ __('tickets::notifications.ticket_needs_handling.priority', ['priority' => $ticket->priority->value]) }}
- {{ __('tickets::notifications.ticket_needs_handling.sla', ['hours' => $ticket->priority->slaHours()]) }}

{{ __('tickets::notifications.ticket_needs_handling.outro') }}
</x-mail::message>
