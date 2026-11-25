<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HH-Service')</title>
    <meta name="theme-color" content="#f8fafc">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">

<div class="min-h-screen lg:flex">

    {{-- Sidebar --}}
    <aside class="hidden w-64 shrink-0 flex-col border-r border-slate-200 bg-white lg:flex">

        <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-7">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-lg font-black text-white shadow-lg shadow-blue-200">
                HH
            </div>

            <div>
                <div class="text-lg font-extrabold tracking-tight text-slate-900">
                    HH-Service
                </div>
                <div class="text-xs text-slate-400">Vacancy Monitor</div>
            </div>
        </div>

        <nav class="flex-1 space-y-2 px-4 py-7">

            <div class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                Рабочая область
            </div>

            <a href="{{ route('vacancies.index') }}"
               class="flex items-center gap-3 rounded-xl bg-blue-50 px-3 py-3 text-sm font-semibold text-blue-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.75A1.75 1.75 0 0 1 5.75 5h12.5A1.75 1.75 0 0 1 20 6.75v10.5A1.75 1.75 0 0 1 18.25 19H5.75A1.75 1.75 0 0 1 4 17.25V6.75Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 9h16M8 5v4"/>
                </svg>
                Все вакансии
            </a>

            <a href="{{ route('vacancies.index') }}?period=today"
               class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                </svg>
                Новые сегодня
            </a>

        </nav>

        <div class="border-t border-slate-100 p-5">
            <div class="rounded-2xl bg-slate-50 p-4">
                <div class="text-xs font-semibold text-slate-700">HH-Service</div>
                <div class="mt-1 text-xs leading-5 text-slate-400">
                    Мониторинг вакансий HeadHunter
                </div>
                <div class="mt-3 text-[11px] font-medium text-slate-500">
                    Мониторинг HeadHunter
                </div>
            </div>
        </div>

    </aside>

    {{-- Main --}}
    <div class="min-w-0 flex-1">

        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-5 backdrop-blur-xl sm:px-8">

            <div class="flex items-center gap-3 lg:hidden">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-black text-white">
                    HH
                </div>
                <span class="font-bold text-slate-900">HH-Service</span>
            </div>

            <div class="hidden text-sm text-slate-400 lg:block">
                Мониторинг рынка вакансий
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <div class="text-xs font-semibold text-slate-700">Панель мониторинга</div>
                    <div class="text-[11px] text-slate-400">{{ now()->format('d.m.Y') }}</div>
                </div>

                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-600">
                    HH
                </div>
            </div>

        </header>

        <main class="mx-auto w-full max-w-[1440px] p-5 sm:p-8 xl:p-10">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white px-8 py-5 text-center text-xs text-slate-400">
            HH-Service · Vacancy Monitoring System
        </footer>

    </div>
</div>

</body>
</html>
