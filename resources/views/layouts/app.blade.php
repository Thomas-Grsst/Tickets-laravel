<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('tickets::messages.nav.tickets') }} · XEFI Academy</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-neutral-100 font-sans text-brand-black antialiased">
    <header class="bg-brand-black text-brand-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('tickets.index') }}" class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-white text-lg font-black text-brand-red">X</span>
                <span class="text-lg font-semibold tracking-tight">XEFI <span class="text-brand-red">Academy</span></span>
            </a>

            @auth
                <nav class="flex items-center gap-4 text-sm">
                    <a href="{{ route('tickets.index') }}" class="hover:text-brand-red">{{ __('tickets::messages.nav.tickets') }}</a>
                    <a href="{{ route('tickets.create') }}" class="rounded-md bg-brand-red px-3 py-1.5 font-medium hover:bg-brand-red-dark">{{ __('tickets::messages.nav.new_ticket') }}</a>
                    <span class="text-neutral-400">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-neutral-300 hover:text-brand-red">{{ __('tickets::messages.nav.logout') }}</button>
                    </form>
                </nav>
            @endauth
        </div>
        <div class="h-1 w-full bg-brand-red"></div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-8">
        {{ $slot }}
    </main>
</body>
</html>
