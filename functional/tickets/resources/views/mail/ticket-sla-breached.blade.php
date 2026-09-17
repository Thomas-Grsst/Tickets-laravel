<x-mail::message>
# {{ __('tickets::notifications.ticket_sla_breached.heading') }}

@if ($wasEscalated)
{{ __('tickets::notifications.ticket_sla_breached.escalated', ['title' => $ticket->title, 'priority' => $ticket->priority->value]) }}
@else
{{ __('tickets::notifications.ticket_sla_breached.still_critical', ['title' => $ticket->title]) }}
@endif

- {{ __('tickets::notifications.ticket_sla_breached.priority', ['priority' => $ticket->priority->value]) }}
- {{ __('tickets::notifications.ticket_sla_breached.age_hours', ['hours' => $ticket->created_at->diffInHours(now())]) }}

{{ __('tickets::notifications.ticket_sla_breached.outro') }}
</x-mail::message>
