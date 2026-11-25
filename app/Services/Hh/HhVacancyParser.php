<?php

namespace App\Services\Hh;

use App\Models\HhVacancy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Сервис парсинга и сохранения вакансий HeadHunter.
 *
 * Отвечает за полный цикл получения вакансий через HeadHunter API:
 * поиск вакансий, постраничную загрузку, получение подробной информации,
 * обработку полученных данных и сохранение в PostgreSQL.
 *
 * Поддерживает настройку поискового запроса, региона, количества страниц,
 * количества вакансий на странице и загрузки подробной информации.
 *
 * Обеспечивает обработку ошибок на уровне отдельных вакансий,
 * логирование проблем и сбор статистики выполнения парсинга.
 *
 * Для взаимодействия с HeadHunter API использует HhApiClient.
 *
 * @package App\Services\Hh
 */
class HhVacancyParser
{
    /**
     * Создаёт экземпляр сервиса парсинга вакансий.
     *
     * @param HhApiClient $api HTTP-клиент для взаимодействия с HeadHunter API.
     */
    public function __construct(private readonly HhApiClient $api)
    {
    }

    /**
     * Выполняет поиск, получение и сохранение вакансий HeadHunter.
     *
     * Последовательно обрабатывает страницы результатов поиска
     * в соответствии с заданными параметрами.
     *
     * Для каждой найденной вакансии выполняет следующие действия:
     * - получает основную информацию из результатов поиска;
     * - при включённом withDetails загружает подробную карточку вакансии;
     * - объединяет краткую и подробную информацию;
     * - сохраняет или обновляет вакансию в базе данных;
     * - обновляет статистику обработки.
     *
     * При ошибке получения подробной информации использует доступные
     * данные поиска и записывает предупреждение в лог.
     *
     * При ошибке обработки отдельной вакансии увеличивает счётчик ошибок,
     * записывает информацию об ошибке и продолжает обработку остальных вакансий.
     *
     * Ограничения параметров:
     * - pages: от 1 до 20 страниц;
     * - perPage: от 1 до 100 вакансий на странице.
     *
     * Если API возвращает пустой список вакансий, выполнение
     * постраничной загрузки прекращается.
     *
     * @param string $query Поисковый запрос (по умолчанию php developer).
     * @param string $area Идентификатор региона HeadHunter (по умолчанию 113).
     * @param int $pages Максимальное количество загружаемых страниц (от 1 до 20).
     * @param int $perPage Количество вакансий на странице (от 1 до 100).
     * @param bool $withDetails Флаг загрузки подробной информации о вакансиях.
     *
     * @return array{
     *     found: int,
     *     processed: int,
     *     created: int,
     *     updated: int,
     *     errors: int
     * } Статистика выполнения парсинга:
     *   - found — количество полученных из API вакансий;
     *   - processed — количество успешно обработанных вакансий;
     *   - created — количество созданных записей;
     *   - updated — количество обновлённых записей;
     *   - errors — количество вакансий, обработка которых завершилась ошибкой.
     *
     * @throws \Illuminate\Http\Client\RequestException Если произошла ошибка
     *         при получении списка вакансий из HeadHunter API.
     */
    public function parse(
        string $query = 'php developer',
        string $area = '113',
        int $pages = 1,
        int $perPage = 100,
        bool $withDetails = true
    ): array {
        $pages = min(max($pages, 1), 20);
        $perPage = min(max($perPage, 1), 100);

        $stats = [
            'found' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        for ($page = 0; $page < $pages; $page++) {
            $result = $this->api->searchVacancies($query, $area, $page, $perPage);

            $items = $result['items'] ?? [];

            if (empty($items)) {
                break;
            }

            $stats['found'] += count($items);

            foreach ($items as $item) {
                try {
                    $vacancy = $item;

                    if ($withDetails) {
                        try {
                            $details = $this->api->getVacancy((string) $item['id']);

                            $vacancy = array_replace($item, $details);

                            if (empty($vacancy['salary']) && !empty($item['salary'])) {
                                $vacancy['salary'] = $item['salary'];
                            }

                            usleep(200000);
                        } catch (\Throwable $e) {
                            Log::warning('HH vacancy details unavailable', [
                                'hh_id' => $item['id'],
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $action = $this->saveVacancy($vacancy, $query);

                    $stats['processed']++;
                    $stats[$action]++;
                } catch (\Throwable $e) {
                    $stats['errors']++;

                    Log::error('HH vacancy processing failed', [
                        'hh_id' => $item['id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $stats;
    }

    /**
     * Сохраняет или обновляет вакансию в PostgreSQL.
     *
     * Извлекает необходимые данные из ответа HeadHunter API,
     * подготавливает атрибуты модели HhVacancy и сохраняет их
     * с использованием updateOrCreate().
     *
     * Для определения существующей записи использует идентификатор
     * вакансии на HeadHunter (hh_id).
     *
     * Обрабатывает информацию о работодателе, регионе, зарплате,
     * опыте работы, типе занятости, графике работы и описании вакансии.
     *
     * При отсутствии зарплаты в новых данных сохраняет ранее
     * полученное значение из существующей записи.
     *
     * Также сохраняет:
     * - поисковый запрос, по которому найдена вакансия;
     * - исходные данные из HeadHunter API;
     * - время последнего получения данных.
     *
     * Если идентификатор вакансии отсутствует, выбрасывает исключение.
     *
     * Возвращает результат операции в зависимости от наличия
     * записи до выполнения сохранения.
     *
     * @param array<string, mixed> $data Данные вакансии из HeadHunter API.
     * @param string $query Поисковый запрос, по которому найдена вакансия.
     *
     * @return string Результат сохранения: created или updated.
     *
     * @throws \RuntimeException Если в данных отсутствует идентификатор вакансии.
     */
    private function saveVacancy(array $data, string $query): string
    {
        $hhId = $data['id'] ?? null;

        if (!$hhId) {
            throw new \RuntimeException('Vacancy ID is missing.');
        }

        $existing = HhVacancy::where('hh_id', (string) $hhId)->first();

        $employer = $data['employer'] ?? [];
        $area = $data['area'] ?? [];

        // HH API может возвращать зарплату в salary или salary_range.
        $salary = $data['salary'] ?? $data['salary_range'] ?? $existing?->salary;

        HhVacancy::updateOrCreate(
            ['hh_id' => (string) $hhId],
            [
                'name' => $data['name'] ?? 'Без названия',
                'employer_id' => $employer['id'] ?? null,
                'employer_name' => $employer['name'] ?? null,
                'area_id' => $area['id'] ?? null,
                'area_name' => $area['name'] ?? null,
                'salary' => $salary,
                'experience' => $data['experience'] ?? null,
                'employment' => $data['employment'] ?? null,
                'schedule' => $data['schedule'] ?? null,
                'published_at' => !empty($data['published_at']) ? Carbon::parse($data['published_at']) : null,
                'alternate_url' => $data['alternate_url'] ?? null,
                'description' => $data['description'] ?? null,
                'search_query' => $query,
                'raw_data' => $data,
                'fetched_at' => now(),
            ]
        );

        return $existing ? 'updated' : 'created';
    }
}
