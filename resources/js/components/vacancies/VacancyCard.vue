<script setup>
import { computed } from 'vue';
import { vacancyApi } from '../../api/vacancyApi';

const props = defineProps({
    vacancy: {
        type: Object,
        required: true,
    },
    expanded: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle', 'viewed']);

const isViewed = computed(() => Boolean(props.vacancy.viewed_at));

async function toggle() {
    emit('toggle', props.vacancy.id);

    if (!isViewed.value) {
        try {
            const result = await vacancyApi.markAsViewed(props.vacancy.id);

            emit('viewed', {
                id: props.vacancy.id,
                viewed_at: result.viewed_at,
            });
        } catch (error) {
            console.error('Не удалось отметить вакансию просмотренной:', error);
        }
    }
}
</script>

<template>
    <article
        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md"
        :class="{ 'border-blue-200 bg-blue-50/30': !isViewed }"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span
                        v-if="vacancy.is_new"
                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                    >
                        Новая
                    </span>

                    <span
                        v-if="isViewed"
                        class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800"
                    >
                        Просмотрена
                    </span>

                    <span
                        v-else
                        class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"
                    >
                        Не просмотрена
                    </span>
                </div>

                <a
                    :href="`/vacancies/${vacancy.id}`"
                    class="text-lg font-semibold text-slate-900 hover:text-blue-700"
                >
                    {{ vacancy.name }}
                </a>

                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span v-if="vacancy.employer_name">
                        {{ vacancy.employer_name }}
                    </span>

                    <span v-if="vacancy.area_name">
                        {{ vacancy.area_name }}
                    </span>

                    <span v-if="vacancy.published_human">
                        {{ vacancy.published_human }}
                    </span>
                </div>

                <div
                    v-if="vacancy.salary_text"
                    class="mt-3 text-base font-semibold text-emerald-700"
                >
                    {{ vacancy.salary_text }}
                </div>

                <div
                    v-if="vacancy.attributes_list?.length"
                    class="mt-3 flex flex-wrap gap-2"
                >
                    <span
                        v-for="attribute in vacancy.attributes_list"
                        :key="attribute"
                        class="rounded-md bg-slate-100 px-2.5 py-1 text-xs text-slate-600"
                    >
                        {{ attribute }}
                    </span>
                </div>

                <div v-if="vacancy.alternate_url" class="mt-4">
                    <a
                        :href="vacancy.alternate_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700"
                    >
                        Открыть на HH
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-lg px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50"
                :aria-expanded="expanded"
                @click="toggle"
            >
                {{ expanded ? 'Свернуть' : 'Подробнее' }}

                <span
                    class="ml-1 inline-block transition-transform"
                    :class="{ 'rotate-180': expanded }"
                >
                    ▼
                </span>
            </button>
        </div>

        <div
            v-if="expanded"
            class="mt-5 border-t border-slate-200 pt-5"
        >
            <h3 class="mb-3 text-sm font-semibold text-slate-800">
                Описание вакансии
            </h3>

            <div
                v-if="vacancy.description"
                class="whitespace-pre-line break-words text-sm leading-6 text-slate-700"
            >
                {{ vacancy.description }}
            </div>

            <p v-else class="text-sm text-slate-400">
                Описание отсутствует.
            </p>

            <div class="mt-5 flex flex-wrap gap-3">
                <a
                    :href="`/vacancies/${vacancy.id}`"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Открыть вакансию
                </a>
            </div>
        </div>
    </article>
</template>
