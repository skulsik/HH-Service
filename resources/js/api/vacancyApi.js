const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            ...(options.method === 'POST' && csrfToken
                ? { 'X-CSRF-TOKEN': csrfToken }
                : {}),
            ...options.headers,
        },
    });

    if (!response.ok) {
        const error = new Error(`HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}

export const vacancyApi = {
    getVacancies(params = {}) {
        const query = new URLSearchParams(params);

        return request(`/vacancies/data?${query}`);
    },

    getChanges(params = {}) {
        const query = new URLSearchParams(params);

        return request(`/vacancies/changes?${query}`);
    },

    getMonitor() {
        return request('/vacancies/monitor');
    },

    markAsViewed(id) {
        return request(`/vacancies/${id}/viewed`, {
            method: 'POST',
        });
    },
};
