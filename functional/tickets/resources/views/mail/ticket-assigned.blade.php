<x-mail::message>
# {{ __('tickets::notifications.ticket_assigned.heading') }}

{{ __('tickets::notifications.ticket_assigned.intro', ['title' => $ticket->title]) }}

- {{ __('tickets::notifications.ticket_assigned.priority', ['priority' => $ticket->priority->value]) }}
- {{ __('tickets::notifications.ticket_assigned.sla', ['hours' => $ticket->priority->slaHours()]) }}

{{ __('tickets::notifications.ticket_assigned.outro') }}
</x-mail::message>
