<div class="mt-6 rounded-lg border border-neutral-200 bg-brand-white p-6">
    <h2 class="mb-4 text-lg font-semibold text-brand-black">{{ __('tickets::messages.attachments.title') }}</h2>

    @if (session('attachment_success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('attachment_success') }}
        </div>
    @endif

    @if ($attachments->isEmpty())
        <p class="text-sm text-neutral-500">{{ __('tickets::messages.attachments.empty') }}</p>
    @else
        <ul class="mb-4 divide-y divide-neutral-200">
            @foreach ($attachments as $attachment)
                <li class="flex items-center justify-between py-2">
                    <div>
                        <a href="{{ route('attachments.download', $attachment) }}"
                           class="text-sm font-medium text-brand-black hover:text-brand-red">
                            {{ $attachment->name }}
                        </a>
                        <p class="text-xs text-neutral-500">
                            {{ $attachment->kind->label() }} &middot; {{ $attachment->uploader->name }}
                        </p>
                    </div>
                    @can('delete', $attachment)
                        <button type="button" wire:click="delete({{ $attachment->id }})"
                                class="text-sm text-brand-red hover:underline">
                            {{ __('tickets::messages.attachments.delete') }}
                        </button>
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif

    @if ($isUploadable)
        <form wire:submit="upload" class="flex items-center gap-3">
            <input type="file" wire:model="file"
                   class="block w-full text-sm text-neutral-700 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-100 file:px-3 file:py-2 file:text-sm file:font-medium">
            <button type="submit" class="shrink-0 rounded-md bg-brand-red px-4 py-2 text-sm font-medium text-brand-white hover:bg-brand-red-dark">
                {{ __('tickets::messages.attachments.upload') }}
            </button>
        </form>
        @error('file') <p class="mt-2 text-sm text-brand-red">{{ $message }}</p> @enderror
    @endif
</div>
