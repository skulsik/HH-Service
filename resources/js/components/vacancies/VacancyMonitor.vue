<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { vacancyApi } from '../../api/vacancyApi';

const monitor = ref(null);
const error = ref('');

let interval = null;

async function loadMonitor() {
    try {
        monitor.value = await vacancyApi.getMonitor();
        error.value = '';
    } catch (exception) {
        console.error('Ошибка загрузки мониторинга:', exception);
        error.value = 'Не удалось получить состояние парсера.';
    }
}

function formatDate(value) {
    if (!value) {
        return 'Нет данных';
    }

    return new Date(value).toLocaleString('ru-RU');
}

onMounted(() => {
    loadMonitor();
    interval = setInterval(loadMonitor, 15000);
});

onUnmounted(() => {
    clearInterval(interval);
});
</script>

<template>
    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">
                    Мониторинг парсера HH
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Состояние автоматического сбора вакансий
                </p>
            </div>

            <span
                v-if="monitor"
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="monitor.scheduler.online
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-red-100 text-red-700'"
            >
                {{ monitor.scheduler.online ? 'Планировщик онлайн' : 'Планировщик офлайн' }}
            </span>
        </div>

        <div
            v-if="error"
            class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div v-if="monitor" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4">
                <div class="text-xs text-slate-500">Последняя активность планировщика</div>
                <div class="mt-2 text-sm font-semibold text-slate-800">
                    {{ formatDate(monitor.scheduler.last_seen_at) }}
                </div>
            </div>

            <div class="rounded-lg bg-slate-50 p-4">
                <div class="text-xs text-slate-500">Статус последнего запуска</div>
                <div class="mt-2 text-sm font-semibold">
                    <span
                        :class="{
                            'text-emerald-700': monitor.sync.status === 'success',
                            'text-blue-700': monitor.sync.status === 'running',
                            'text-red-700': monitor.sync.status === 'failed',
                            'text-slate-600': monitor.sync.status === 'pending',
                        }"
                    >
                        {{ monitor.sync.status }}
                    </span>

                    <span
                        v-if="monitor.sync.is_stuck"
                        class="ml-2 text-red-600"
                    >
                        (возможно завис)
                    </span>
                </div>
            </div>

            <div class="rounded-lg bg-slate-50 p-4">
                <div class="text-xs text-slate-500">Последний успешный запуск</div>
                <div class="mt-2 text-sm font-semibold text-slate-800">
                    {{ formatDate(monitor.sync.last_success_at) }}
                </div>
            </div>

            <div class="rounded-lg bg-slate-50 p-4">
                <div class="text-xs text-slate-500">Последнее завершение</div>
                <div class="mt-2 text-sm font-semibold text-slate-800">
                    {{ formatDate(monitor.sync.finished_at) }}
                </div>
            </div>
        </div>

        <div
            v-if="monitor"
            class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5"
        >
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-xs text-slate-500">Найдено</div>
                <div class="mt-1 text-xl font-bold">{{ monitor.sync.found }}</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-xs text-slate-500">Обработано</div>
                <div class="mt-1 text-xl font-bold">{{ monitor.sync.processed }}</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-xs text-slate-500">Создано</div>
                <div class="mt-1 text-xl font-bold text-emerald-600">{{ monitor.sync.created }}</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-xs text-slate-500">Обновлено</div>
                <div class="mt-1 text-xl font-bold text-blue-600">{{ monitor.sync.updated }}</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-xs text-slate-500">Ошибок</div>
                <div class="mt-1 text-xl font-bold text-red-600">{{ monitor.sync.errors }}</div>
            </div>
        </div>

        <div
            v-if="monitor?.sync.error_message"
            class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3"
        >
            <div class="text-sm font-semibold text-red-800">
                Последняя ошибка
            </div>

            <p class="mt-1 break-words text-sm text-red-700">
                {{ monitor.sync.error_message }}
            </p>
        </div>
    </section>
</template>
