<?php

namespace App\Http\Controllers;

use App\Models\HhVacancy;
use App\Services\Hh\HhSyncStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * API-контроллер для работы с вакансиями HeadHunter.
 *
 * Предоставляет HTTP-методы для получения списка вакансий,
 * отслеживания изменений, отметки вакансий как просмотренных
 * и мониторинга состояния парсера и планировщика Laravel Scheduler.
 *
 * Все ответы возвращаются в формате JSON.
 *
 * @package App\Http\Controllers
 */
class VacancyApiController extends Controller
{
    /**
     * Получает список вакансий с поддержкой поиска, фильтрации и пагинации.
     *
     * Поддерживает поиск по названию вакансии, работодателю и региону.
     * Позволяет ограничить результаты вакансиями, опубликованными сегодня.
     *
     * Результат сортируется по дате публикации в порядке убывания.
     * Вакансии без даты публикации располагаются в конце списка.
     *
     * Дополнительно возвращает общую статистику по вакансиям:
     * общее количество, количество за сегодня и за последние 7 дней.
     *
     * Query-параметры:
     * - q: поисковая строка (необязательно, до 255 символов).
     * - period: период публикации (необязательно, today).
     * - page: номер страницы (необязательно, от 1).
     *
     * @param Request $request HTTP-запрос с параметрами поиска и фильтрации.
     *
     * @return JsonResponse JSON-ответ со списком вакансий, метаданными пагинации
     *                      и статистикой.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'in:today'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = HhVacancy::query()->when(!empty($validated['q']), function ($query) use ($validated) {
                $search = trim($validated['q']);

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('employer_name', 'ilike', "%{$search}%")
                        ->orWhere('area_name', 'ilike', "%{$search}%");
                });
            })
            ->when(($validated['period'] ?? null) === 'today', function ($query) {
                $query->where('published_at', '>=', now()->startOfDay());
            })
            ->orderByRaw('published_at IS NULL ASC')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $vacancies = $query->paginate(10);

        $data = $vacancies->getCollection()->map(fn (HhVacancy $vacancy) => $this->formatVacancy($vacancy));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $vacancies->currentPage(),
                'last_page' => $vacancies->lastPage(),
                'per_page' => $vacancies->perPage(),
                'total' => $vacancies->total(),
            ],
            'stats' => [
                'total' => HhVacancy::count(),
                'today' => HhVacancy::where(
                    'published_at',
                    '>=',
                    now()->startOfDay()
                )->count(),
                'week' => HhVacancy::where(
                    'published_at',
                    '>=',
                    now()->subDays(7)
                )->count(),
            ],
        ]);
    }

    /**
     * Получает список вакансий, изменённых после указанного времени.
     *
     * Используется для периодического обновления данных на клиенте
     * без повторной загрузки полного списка вакансий.
     *
     * Учитывает изменения поля updated_at с дополнительным временным
     * интервалом в 5 секунд для компенсации возможных расхождений
     * времени между запросами.
     *
     * Поддерживает фильтрацию по поисковой строке и периоду публикации.
     * Возвращает не более 100 последних изменённых вакансий.
     *
     * Query-параметры:
     * - since: обязательная дата и время, начиная с которых необходимо
     *   получить изменения.
     * - q: поисковая строка (необязательно, до 255 символов).
     * - period: период публикации (необязательно, today).
     *
     * @param Request $request HTTP-запрос с параметрами получения изменений.
     *
     * @return JsonResponse JSON-ответ со списком изменённых вакансий
     *                      и текущим временем сервера.
     */
    public function changes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => ['required', 'date'],
            'q' => ['nullable', 'string', 'max:255'],
            'period' => ['nullable', 'in:today'],
        ]);

        $query = HhVacancy::query()
            ->where('updated_at', '>=', Carbon::parse($validated['since'])->subSeconds(5));

        if (!empty($validated['q'])) {
            $search = trim($validated['q']);

            $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('employer_name', 'ilike', "%{$search}%")
                    ->orWhere('area_name', 'ilike', "%{$search}%");
            });
        }

        if (($validated['period'] ?? null) === 'today') {
            $query->where('published_at', '>=', now()->startOfDay());
        }

        $vacancies = $query
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $vacancies->map(
                fn (HhVacancy $vacancy) => $this->formatVacancy($vacancy)
            ),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Отмечает вакансию как просмотренную.
     *
     * Устанавливает текущее время в поле viewed_at,
     * если вакансия ещё не была просмотрена.
     *
     * Повторный вызов метода не изменяет первоначальное время просмотра.
     * После обновления получает актуальное состояние модели.
     *
     * @param HhVacancy $vacancy Вакансия, полученная через Route Model Binding.
     *
     * @return JsonResponse JSON-ответ с результатом операции
     *                      и временем просмотра вакансии.
     */
    public function markAsViewed(HhVacancy $vacancy): JsonResponse
    {
        HhVacancy::query()
            ->whereKey($vacancy->id)
            ->whereNull('viewed_at')
            ->update([
                'viewed_at' => now(),
            ]);

        $vacancy->refresh();

        return response()->json([
            'success' => true,
            'viewed_at' => $vacancy->viewed_at?->toIso8601String(),
        ]);
    }

    /**
     * Возвращает информацию о состоянии парсера и планировщика задач.
     *
     * Получает актуальный статус синхронизации через HhSyncStatusService.
     *
     * Определяет доступность Laravel Scheduler:
     * планировщик считается активным, если последнее обращение
     * было зафиксировано менее 3 минут назад.
     *
     * Дополнительно определяет зависшие процессы синхронизации:
     * синхронизация считается зависшей, если её статус running
     * сохраняется более 15 минут с момента запуска.
     *
     * Возвращает информацию о:
     * - состоянии планировщика и времени последней активности;
     * - статусе синхронизации;
     * - времени запуска и завершения последней синхронизации;
     * - времени последней успешной синхронизации;
     * - статистике обработки вакансий;
     * - последней зарегистрированной ошибке.
     *
     * @param HhSyncStatusService $service Сервис получения состояния синхронизации.
     *
     * @return JsonResponse JSON-ответ с информацией о состоянии системы
     *                      и текущим временем сервера.
     */
    public function monitor(HhSyncStatusService $service): JsonResponse
    {
        $status = $service->getStatus();

        $schedulerIsOnline = $status->scheduler_last_seen_at !== null
            && $status->scheduler_last_seen_at->greaterThan(now()->subMinutes(3));

        $syncIsStuck = $status->status === 'running'
            && $status->started_at !== null
            && $status->started_at->lessThan(now()->subMinutes(15));

        return response()->json([
            'scheduler' => [
                'online' => $schedulerIsOnline,
                'last_seen_at' => $status->scheduler_last_seen_at?->toIso8601String(),
            ],
            'sync' => [
                'status' => $status->status,
                'is_stuck' => $syncIsStuck,
                'started_at' => $status->started_at?->toIso8601String(),
                'finished_at' => $status->finished_at?->toIso8601String(),
                'last_success_at' => $status->last_success_at?->toIso8601String(),
                'found' => $status->found,
                'processed' => $status->processed,
                'created' => $status->created,
                'updated' => $status->updated,
                'errors' => $status->errors,
                'error_message' => $status->error_message,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Преобразует модель вакансии в массив для JSON-ответа.
     *
     * Подготавливает структурированные данные вакансии,
     * включая информацию о работодателе, регионе, зарплате,
     * условиях работы, описании и ссылке на HeadHunter.
     *
     * Дополнительно форматирует даты публикации, просмотра,
     * создания, обновления и последнего получения данных
     * в формате ISO 8601.
     *
     * Формирует человекочитаемое представление даты публикации
     * и передаёт клиенту признаки новой и просмотренной вакансии.
     *
     * @param HhVacancy $vacancy Модель вакансии для преобразования.
     *
     * @return array<string, mixed> Подготовленные данные вакансии.
     */
    private function formatVacancy(HhVacancy $vacancy): array
    {
        return [
            'id' => $vacancy->id,
            'hh_id' => $vacancy->hh_id,
            'name' => $vacancy->name,
            'employer_name' => $vacancy->employer_name,
            'area_name' => $vacancy->area_name,
            'salary' => $vacancy->salary,
            'salary_text' => $vacancy->salary_text,
            'experience' => $vacancy->experience,
            'employment' => $vacancy->employment,
            'schedule' => $vacancy->schedule,
            'attributes_list' => $vacancy->attributes_list,
            'description' => $vacancy->description_text,
            'published_at' => $vacancy->published_at?->toIso8601String(),
            'published_human' => $vacancy->published_at?->diffForHumans(),
            'alternate_url' => $vacancy->alternate_url,
            'is_new' => $vacancy->is_new,
            'viewed_at' => $vacancy->viewed_at?->toIso8601String(),
            'created_at' => $vacancy->created_at?->toIso8601String(),
            'updated_at' => $vacancy->updated_at?->toIso8601String(),
            'fetched_at' => $vacancy->fetched_at?->toIso8601String(),
        ];
    }
}
