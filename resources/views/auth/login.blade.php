<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Masuk - {{ config('app.name', 'FNN Studio') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:p-10">
            <header class="mb-8 space-y-2">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">FNN Studio</p>
                <h1 class="text-2xl font-bold tracking-tight">Masuk ke akun Anda</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">Gunakan username atau email yang terdaftar.</p>
            </header>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div class="space-y-2">
                    <label for="identifier" class="block text-sm font-medium">Username atau email</label>
                    <input
                        id="identifier"
                        name="identifier"
                        type="text"
                        value="{{ old('identifier') }}"
                        autocomplete="username"
                        required
                        autofocus
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-700 dark:bg-slate-950"
                        @error('identifier') aria-invalid="true" aria-describedby="identifier-error" @enderror
                    >
                    @error('identifier')
                        <p id="identifier-error" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium">Kata sandi</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-700 dark:bg-slate-950"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')
                        <p id="password-error" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label for="remember" class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                    <input id="remember" name="remember" type="checkbox" value="1" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Ingat saya
                </label>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/30"
                >
                    Masuk
                </button>
            </form>
        </main>
    </body>
</html>
