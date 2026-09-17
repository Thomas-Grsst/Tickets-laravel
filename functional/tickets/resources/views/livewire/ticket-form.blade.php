<div class="mx-auto max-w-2xl">
    <a href="{{ route('tickets.index') }}" class="mb-4 inline-block text-sm text-neutral-500 hover:text-brand-red">
        &larr; {{ __('tickets::messages.form.back') }}
    </a>

    <h1 class="mb-6 text-2xl font-semibold text-brand-black">
        {{ $ticket ? __('tickets::messages.form.edit_title') : __('tickets::messages.form.create_title') }}
    </h1>

    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-brand-red">
            {{ session('error') }}
        </div>
    @endif

    @if ($ticket)
        <div class="mb-6 flex items-center justify-between rounded-lg border border-neutral-200 bg-brand-white p-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-neutral-500">{{ __('tickets::messages.form.current_status') }}</p>
                <p class="text-lg font-semibold text-brand-black">{{ __('tickets::messages.status.' . $ticket->status->value) }}</p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                @foreach ($availableActions as $action)
                    @can($action['method'] === 'assign' || $action['method'] === 'unassign' ? 'assign' : ($action['method'] === 'close' ? 'close' : 'update'), $ticket)
                        <button type="button" wire:click="{{ $action['method'] }}"
                                class="rounded-md border border-brand-black px-3 py-1.5 text-sm font-medium text-brand-black hover:bg-brand-black hover:text-brand-white">
                            {{ $action['label'] }}
                        </button>
                    @endcan
                @endforeach
            </div>
        </div>
    @endif

    <form wire:submit="save" class="space-y-4 rounded-lg border border-neutral-200 bg-brand-white p-6">
        <div>
            <label for="title" class="mb-1 block text-sm font-medium text-neutral-700">{{ __('tickets::messages.form.title') }}</label>
            <input id="title" type="text" wire:model="title" @disabled(! $isEditable)
                   class="w-full rounded-md border border-neutral-300 px-3 py-2 focus:border-brand-red focus:outline-none focus:ring-1 focus:ring-brand-red disabled:bg-neutral-100 disabled:text-neutral-500">
            @error('title') <p class="mt-1 text-sm text-brand-red">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-medium text-neutral-700">{{ __('tickets::messages.form.description') }}</label>
            <textarea id="description" rows="5" wire:model="description" @disabled(! $isEditable)
                      class="w-full rounded-md border border-neutral-300 px-3 py-2 focus:border-brand-red focus:outline-none focus:ring-1 focus:ring-brand-red disabled:bg-neutral-100 disabled:text-neutral-500"></textarea>
            @error('description') <p class="mt-1 text-sm text-brand-red">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="priority" class="mb-1 block text-sm font-medium text-neutral-700">{{ __('tickets::messages.form.priority') }}</label>
            <select id="priority" wire:model="priority" @disabled(! $isEditable)
                    class="w-full rounded-md border border-neutral-300 px-3 py-2 focus:border-brand-red focus:outline-none focus:ring-1 focus:ring-brand-red disabled:bg-neutral-100 disabled:text-neutral-500">
                @foreach ($priorities as $option)
                    <option value="{{ $option->value }}">{{ __('tickets::messages.priority.' . $option->value) }}</option>
                @endforeach
            </select>
            @error('priority') <p class="mt-1 text-sm text-brand-red">{{ $message }}</p> @enderror
        </div>

        @if ($isEditable)
            <button type="submit" class="rounded-md bg-brand-red px-4 py-2 font-medium text-brand-white hover:bg-brand-red-dark">
                {{ $ticket ? __('tickets::messages.form.submit_update') : __('tickets::messages.form.submit_create') }}
            </button>
        @endif
    </form>
</div>
