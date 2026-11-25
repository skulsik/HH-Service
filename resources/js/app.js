import { createApp, h } from 'vue';

import VacancyList from './components/vacancies/VacancyList.vue';
import VacancyMonitor from './components/vacancies/VacancyMonitor.vue';

const root = document.getElementById('vacancy-app');

if (root) {
    createApp({
        render() {
            return h('div', { class: 'space-y-6' }, [
                h(VacancyMonitor),
                h(VacancyList),
            ]);
        },
    }).mount(root);
}
