<x-mail::message>
# {{ __('tickets::notifications.ticket_escalated.heading') }}

{{ __('tickets::notifications.ticket_escalated.intro', ['title' => $ticket->title]) }}

- {{ __('tickets::notifications.ticket_escalated.breached', ['priority' => __("tickets::messages.priority.{$breachedPriority->value}"), 'hours' => $breachedPriority->slaHours()]) }}
- {{ __('tickets::notifications.ticket_escalated.raised', ['priority' => __("tickets::messages.priority.{$ticket->priority->value}"), 'hours' => $ticket->priority->slaHours()]) }}

{{ __('tickets::notifications.ticket_escalated.outro') }}
</x-mail::message>
