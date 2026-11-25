@extends('layouts.app')

@section('title', $vacancy->name . ' — HH-Service')

@section('content')

    {{-- Back link --}}
    <a href="{{ route('vacancies.index', request()->only('q', 'page')) }}"
       class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-600">

        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/>
        </svg>

        Назад к вакансиям
    </a>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">

        {{-- Main content --}}
        <div class="min-w-0 space-y-6">

            {{-- Vacancy header --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">

                <div class="mb-5 flex flex-wrap items-center gap-2">

                    <span class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                        ВАКАНСИЯ
                    </span>

                    @if($vacancy->is_new)
                        <span class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                            НОВАЯ
                        </span>
                    @endif

                </div>

                <h1 class="text-2xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-3xl">
                    {{ $vacancy->name }}
                </h1>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">

                    <span class="font-semibold text-slate-800">
                        {{ $vacancy->employer_name ?? 'Работодатель не указан' }}
                    </span>

                    @if($vacancy->area_name)
                        <span class="text-slate-300">•</span>
                        <span>{{ $vacancy->area_name }}</span>
                    @endif

                </div>

                {{-- Salary and publication date --}}
                <div class="mt-7 flex flex-wrap gap-3">

                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <div class="text-[11px] font-medium text-slate-400">
                            Зарплата
                        </div>

                        <div class="mt-1 text-lg font-extrabold text-slate-900">
                            {{ $vacancy->salary_text }}
                        </div>
                    </div>

                    @if($vacancy->published_at)
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-medium text-slate-400">
                                Опубликована
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-800">
                                {{ $vacancy->published_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Vacancy attributes --}}
                @if($vacancy->attributes_list)
                    <div class="mt-7 flex flex-wrap gap-2">

                        @foreach($vacancy->attributes_list as $label => $value)
                            <div class="rounded-lg border border-slate-200 px-3 py-2">
                                <span class="text-xs text-slate-400">{{ $label }}:</span>
                                <span class="ml-1 text-xs font-semibold text-slate-700">{{ $value }}</span>
                            </div>
                        @endforeach

                    </div>
                @endif

            </section>

            {{-- Vacancy description --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">

                <div class="mb-6 flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 4h10a2 2 0 0 1 2 2v14H5V6a2 2 0 0 1 2-2Z"/>
                            <path stroke-linecap="round" d="M8 9h8M8 13h8M8 17h5"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="font-bold text-slate-900">
                            Описание вакансии
                        </h2>

                        <p class="text-xs text-slate-400">
                            Полная информация от работодателя
                        </p>
                    </div>

                </div>

                @if($vacancy->description)

                    <div class="max-w-none break-words text-sm leading-7 text-slate-700
                        [&_h1]:mb-4 [&_h1]:mt-7 [&_h1]:text-2xl [&_h1]:font-bold
                        [&_h2]:mb-3 [&_h2]:mt-7 [&_h2]:text-xl [&_h2]:font-bold
                        [&_h3]:mb-2 [&_h3]:mt-5 [&_h3]:text-lg [&_h3]:font-bold
                        [&_p]:mb-4 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6
                        [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6
                        [&_li]:mb-1 [&_a]:text-blue-600 [&_a]:underline">

                        {!! $vacancy->description !!}

                    </div>

                @else

                    <div class="rounded-xl bg-slate-50 p-6 text-center">
                        <p class="text-sm text-slate-500">
                            Подробное описание пока не загружено.
                        </p>
                    </div>

                @endif

            </section>

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-5">

            {{-- Apply card --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <h3 class="font-bold text-slate-900">
                    Отклик на вакансию
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Перейди на HeadHunter, чтобы ознакомиться с оригинальной публикацией и отправить отклик.
                </p>

                @if($vacancy->alternate_url)

                    <a href="{{ $vacancy->alternate_url }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-100 transition hover:bg-blue-700">

                        Откликнуться на hh.ru

                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 17 17 7M7 7h10v10"/>
                        </svg>

                    </a>

                @else

                    <div class="mt-5 rounded-xl bg-slate-100 p-3 text-center text-sm text-slate-500">
                        Ссылка на вакансию отсутствует
                    </div>

                @endif

            </section>

            {{-- Vacancy metadata --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <h3 class="font-bold text-slate-900">
                    Информация
                </h3>

                <dl class="mt-4 space-y-4">

                    <div>
                        <dt class="text-xs text-slate-400">
                            ID вакансии HH
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $vacancy->hh_id }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-slate-400">
                            Добавлена в систему
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $vacancy->created_at?->format('d.m.Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-slate-400">
                            Последнее обновление
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $vacancy->fetched_at?->format('d.m.Y H:i') ?? '—' }}
                        </dd>
                    </div>

                </dl>

            </section>

        </aside>

    </div>

@endsection
