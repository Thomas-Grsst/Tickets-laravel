<div class="mt-6 rounded-lg border border-neutral-200 bg-brand-white p-6">
    <h2 class="mb-4 text-lg font-semibold text-brand-black">{{ __('tickets::messages.attachments.title') }}</h2>

    @if ($attachments->isEmpty())
        <p class="text-sm text-neutral-500">{{ __('tickets::messages.attachments.empty') }}</p>
    @else
        <ul class="mb-4 divide-y divide-neutral-100">
            @foreach ($attachments as $attachment)
                <li class="flex items-center justify-between py-2 text-sm">
                    <div>
                        <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}" class="font-medium text-brand-black hover:text-brand-red">
                            {{ $attachment->original_name }}
                        </a>
                        <p class="text-xs text-neutral-500">
                            {{ __('tickets::messages.attachments.uploaded_by', ['name' => $attachment->uploader?->name]) }}
                        </p>
                    </div>
                    @can('delete', $attachment)
                        <button type="button" wire:click="delete({{ $attachment->id }})" wire:confirm
                                class="text-xs font-medium text-brand-red hover:underline">
                            {{ __('tickets::messages.attachments.delete') }}
                        </button>
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif

    <form wire:submit="upload" class="flex items-center gap-3">
        <input type="file" wire:model="file" class="text-sm">
        <button type="submit" class="rounded-md bg-brand-black px-3 py-1.5 text-sm font-medium text-brand-white hover:bg-neutral-800">
            {{ __('tickets::messages.attachments.upload') }}
        </button>
    </form>
    @error('file') <p class="mt-1 text-sm text-brand-red">{{ $message }}</p> @enderror
</div>
