<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { vacancyApi } from '../../api/vacancyApi';
import VacancyCard from './VacancyCard.vue';

const vacancies = ref([]);
const stats = ref({
    total: 0,
    today: 0,
    week: 0,
});

const loading = ref(true);
const refreshing = ref(false);
const error = ref('');

const search = ref('');
const period = ref('');
const page = ref(1);

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const expandedVacancyId = ref(null);

let searchTimeout = null;
let changesInterval = null;
let lastChangesAt = new Date(Date.now() - 10000).toISOString();

const hasVacancies = computed(() => vacancies.value.length > 0);

const pages = computed(() => {
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from(
        { length: end - start + 1 },
        (_, index) => start + index
    );
});

function getParams() {
    return {
        q: search.value.trim(),
        period: period.value,
        page: page.value,
    };
}

async function loadVacancies(showLoader = true) {
    if (showLoader) {
        loading.value = true;
    } else {
        refreshing.value = true;
    }

    error.value = '';

    try {
        const response = await vacancyApi.getVacancies(getParams());

        vacancies.value = response.data;
        pagination.value = response.meta;
        stats.value = response.stats;

        if (
            expandedVacancyId.value !== null &&
            !vacancies.value.some(
                vacancy => vacancy.id === expandedVacancyId.value
            )
        ) {
            expandedVacancyId.value = null;
        }
    } catch (exception) {
        console.error('Ошибка загрузки вакансий:', exception);
        error.value = 'Не удалось загрузить вакансии. Попробуйте обновить страницу.';
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
}

function changePage(newPage) {
    if (
        newPage < 1 ||
        newPage > pagination.value.last_page ||
        newPage === page.value
    ) {
        return;
    }

    page.value = newPage;
    expandedVacancyId.value = null;

    loadVacancies();
}

function toggleVacancy(id) {
    expandedVacancyId.value =
        expandedVacancyId.value === id ? null : id;
}

function updateViewed({ id, viewed_at }) {
    const vacancy = vacancies.value.find(item => item.id === id);

    if (vacancy) {
        vacancy.viewed_at = viewed_at;
    }
}

function mergeChanges(changedVacancies) {
    let hasNewVacancies = false;

    for (const changed of changedVacancies) {
        const index = vacancies.value.findIndex(
            vacancy => vacancy.id === changed.id
        );

        if (index !== -1) {
            vacancies.value[index] = {
                ...vacancies.value[index],
                ...changed,
            };
        } else if (page.value === 1) {
            hasNewVacancies = true;
        }
    }

    return hasNewVacancies;
}

async function checkChanges() {
    try {
        const response = await vacancyApi.getChanges({
            since: lastChangesAt,
            q: search.value.trim(),
            period: period.value,
        });

        lastChangesAt = response.server_time;

        const hasNewVacancies = mergeChanges(response.data);

        if (hasNewVacancies) {
            await loadVacancies(false);
        }
    } catch (exception) {
        console.error('Ошибка проверки обновлений:', exception);
    }
}

function resetSearch() {
    search.value = '';
    period.value = '';
    page.value = 1;
    expandedVacancyId.value = null;

    loadVacancies();
}

watch([search, period], () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        page.value = 1;
        expandedVacancyId.value = null;
        loadVacancies();
    }, 400);
});

onMounted(async () => {
    await loadVacancies();

    changesInterval = setInterval(checkChanges, 30000);
});

onUnmounted(() => {
    clearTimeout(searchTimeout);
    clearInterval(changesInterval);
});
</script>

<template>
    <section class="space-y-6">
        <!-- Статистика -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Всего вакансий</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">
                    {{ stats.total }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">Опубликовано сегодня</div>
                <div class="mt-2 text-3xl font-bold text-emerald-600">
                    {{ stats.today }}
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm text-slate-500">За последние 7 дней</div>
                <div class="mt-2 text-3xl font-bold text-blue-600">
                    {{ stats.week }}
                </div>
            </div>
        </div>

        <!-- Поиск и фильтры -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Поиск по вакансии, компании или городу..."
                    class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="period"
                        type="checkbox"
                        true-value="today"
                        false-value=""
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >
                    Только за сегодня
                </label>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="resetSearch"
                >
                    Сбросить
                </button>
            </div>
        </div>

        <!-- Заголовок списка -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Найденные вакансии
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Найдено: {{ pagination.total }}
                </p>
            </div>

            <span
                v-if="refreshing"
                class="text-xs text-slate-400"
            >
                Обновление...
            </span>
        </div>

        <!-- Ошибка -->
        <div
            v-if="error"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            {{ error }}

            <button
                type="button"
                class="ml-2 font-semibold underline"
                @click="loadVacancies()"
            >
                Повторить
            </button>
        </div>

        <!-- Загрузка -->
        <div
            v-else-if="loading"
            class="rounded-xl border border-slate-200 bg-white p-10 text-center text-slate-500"
        >
            Загружаем вакансии...
        </div>

        <!-- Пустой список -->
        <div
            v-else-if="!hasVacancies"
            class="rounded-xl border border-slate-200 bg-white p-10 text-center"
        >
            <div class="text-lg font-semibold text-slate-700">
                Вакансии не найдены
            </div>

            <p class="mt-2 text-sm text-slate-500">
                Попробуйте изменить поисковый запрос или фильтры.
            </p>
        </div>

        <!-- Карточки -->
        <div v-else class="space-y-4">
            <VacancyCard
                v-for="vacancy in vacancies"
                :key="vacancy.id"
                :vacancy="vacancy"
                :expanded="expandedVacancyId === vacancy.id"
                @toggle="toggleVacancy"
                @viewed="updateViewed"
            >
            </VacancyCard>
        </div>

        <!-- Пагинация -->
        <div
            v-if="pagination.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <button
                type="button"
                :disabled="page <= 1"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                @click="changePage(page - 1)"
            >
                Назад
            </button>

            <button
                v-for="pageNumber in pages"
                :key="pageNumber"
                type="button"
                class="rounded-lg border px-3 py-2 text-sm"
                :class="pageNumber === page
                    ? 'border-blue-600 bg-blue-600 text-white'
                    : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
                @click="changePage(pageNumber)"
            >
                {{ pageNumber }}
            </button>

            <button
                type="button"
                :disabled="page >= pagination.last_page"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40"
                @click="changePage(page + 1)"
            >
                Вперёд
            </button>
        </div>
    </section>
</template>
