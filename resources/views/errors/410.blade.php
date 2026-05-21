<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('ui.errors.410.title') }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-surface-50 font-sans text-surface-900 antialiased dark:bg-surface-950 dark:text-surface-0">
        <main class="flex min-h-screen items-center justify-center px-6 py-12">
            <section class="w-full max-w-xl rounded-md border border-surface-200 bg-surface-0 p-8 shadow-sm dark:border-surface-800 dark:bg-surface-900">
                <p class="text-sm font-semibold text-amber-500">410</p>
                <h1 class="mt-3 text-3xl font-semibold">{{ __('ui.errors.410.title') }}</h1>
                <p class="mt-4 text-base leading-7 text-surface-600 dark:text-surface-300">
                    {{ __('ui.errors.410.body') }}
                </p>
                <a
                    href="{{ route('home') }}"
                    class="mt-8 inline-flex items-center justify-center rounded-md bg-surface-950 px-4 py-2 text-sm font-medium text-surface-0 transition hover:bg-surface-800 dark:bg-surface-0 dark:text-surface-950 dark:hover:bg-surface-200"
                >
                    {{ __('ui.errors.return_home') }}
                </a>
            </section>
        </main>
    </body>
</html>
