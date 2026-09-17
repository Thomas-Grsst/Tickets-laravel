<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('tickets::messages.auth.login') }} · XEFI Academy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-brand-black font-sans">
    <div class="w-full max-w-sm rounded-xl bg-brand-white p-8 shadow-xl">
        <div class="mb-6 flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-black text-lg font-black text-brand-red">X</span>
            <span class="text-lg font-semibold tracking-tight text-brand-black">XEFI <span class="text-brand-red">Academy</span></span>
        </div>

        @if ($errors->any())
            <p class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-brand-red">{{ __('tickets::messages.auth.failed') }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-neutral-700">{{ __('tickets::messages.auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-md border border-neutral-300 px-3 py-2 focus:border-brand-red focus:outline-none focus:ring-1 focus:ring-brand-red">
            </div>
            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-neutral-700">{{ __('tickets::messages.auth.password') }}</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-md border border-neutral-300 px-3 py-2 focus:border-brand-red focus:outline-none focus:ring-1 focus:ring-brand-red">
            </div>
            <button type="submit" class="w-full rounded-md bg-brand-red py-2 font-medium text-brand-white hover:bg-brand-red-dark">
                {{ __('tickets::messages.auth.login') }}
            </button>
        </form>
    </div>
</body>
</html>
