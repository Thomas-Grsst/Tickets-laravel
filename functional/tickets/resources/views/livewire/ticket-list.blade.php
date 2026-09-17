<div>
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-brand-black">{{ __('tickets::messages.nav.tickets') }}</h1>
        <a href="{{ route('tickets.create') }}" class="rounded-md bg-brand-red px-4 py-2 text-sm font-medium text-brand-white hover:bg-brand-red-dark">
            {{ __('tickets::messages.nav.new_ticket') }}
        </a>
    </div>

    <div class="mb-4 flex flex-wrap gap-4 rounded-lg border border-neutral-200 bg-brand-white p-4">
        <label class="flex items-center gap-2 text-sm">
            <span class="font-medium text-neutral-600">{{ __('tickets::messages.list.filters.status') }}</span>
            <select wire:model.live="status" class="rounded-md border-neutral-300 text-sm focus:border-brand-red focus:ring-brand-red">
                <option value="">{{ __('tickets::messages.list.filters.all') }}</option>
                @foreach ($statuses as $option)
                    <option value="{{ $option->value }}">{{ __('tickets::messages.status.' . $option->value) }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex items-center gap-2 text-sm">
            <span class="font-medium text-neutral-600">{{ __('tickets::messages.list.filters.priority') }}</span>
            <select wire:model.live="priority" class="rounded-md border-neutral-300 text-sm focus:border-brand-red focus:ring-brand-red">
                <option value="">{{ __('tickets::messages.list.filters.all') }}</option>
                @foreach ($priorities as $option)
                    <option value="{{ $option->value }}">{{ __('tickets::messages.priority.' . $option->value) }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 bg-brand-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 text-xs uppercase tracking-wide text-neutral-500">
                <tr>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="sortBy('title')" class="flex items-center gap-1 hover:text-brand-red">
                            {{ __('tickets::messages.list.columns.title') }}
                            @if ($sortColumn === 'title')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif
                        </button>
                    </th>
                    <th class="px-4 py-3">{{ __('tickets::messages.list.columns.requester') }}</th>
                    <th class="px-4 py-3">{{ __('tickets::messages.list.columns.assigned_technician') }}</th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="sortBy('status')" class="flex items-center gap-1 hover:text-brand-red">
                            {{ __('tickets::messages.list.columns.status') }}
                            @if ($sortColumn === 'status')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif
                        </button>
                    </th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="sortBy('priority')" class="flex items-center gap-1 hover:text-brand-red">
                            {{ __('tickets::messages.list.columns.priority') }}
                            @if ($sortColumn === 'priority')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif
                        </button>
                    </th>
                    <th class="px-4 py-3">{{ __('tickets::messages.list.columns.comments') }}</th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="sortBy('created_at')" class="flex items-center gap-1 hover:text-brand-red">
                            {{ __('tickets::messages.list.columns.created_at') }}
                            @if ($sortColumn === 'created_at')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($tickets as $ticket)
                    <tr class="hover:bg-neutral-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('tickets.edit', $ticket) }}" class="font-medium text-brand-black hover:text-brand-red">
                                {{ $ticket->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ $ticket->requester?->name }}</td>
                        <td class="px-4 py-3 text-neutral-600">{{ $ticket->assignedTechnician?->name ?? __('tickets::messages.list.unassigned') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full bg-neutral-200 px-2.5 py-0.5 text-xs font-medium text-brand-black">
                                {{ __('tickets::messages.status.' . $ticket->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-brand-red text-brand-white' => $ticket->priority->value === 'critical',
                                'bg-red-100 text-brand-red' => $ticket->priority->value === 'high',
                                'bg-neutral-200 text-brand-black' => in_array($ticket->priority->value, ['low', 'normal'], true),
                            ])>
                                {{ __('tickets::messages.priority.' . $ticket->priority->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-neutral-600">{{ $ticket->comments_count }}</td>
                        <td class="px-4 py-3 text-neutral-500">{{ $ticket->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-neutral-500">
                            {{ __('tickets::messages.list.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
