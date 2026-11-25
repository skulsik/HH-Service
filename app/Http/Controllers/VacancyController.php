<?php

namespace App\Http\Controllers;

use App\Models\HhVacancy;
use App\Services\Hh\HhSyncStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Контроллер для отображения вакансий HeadHunter.
 *
 * Обеспечивает вывод списка вакансий с поддержкой поиска,
 * фильтрации и пагинации, а также отображение подробной
 * информации об отдельной вакансии.
 *
 * Дополнительно предоставляет статистику по вакансиям
 * и информацию о состоянии парсера и планировщика Laravel Scheduler.
 *
 * @package App\Http\Controllers
 */
class VacancyController extends Controller
{
    /**
     * Отображает список вакансий.
     *
     * Поддерживает поиск по названию вакансии, работодателю
     * и региону, а также фильтрацию по дате публикации.
     *
     * Результаты сортируются по дате публикации в порядке убывания.
     * Вакансии без даты публикации располагаются в конце списка.
     *
     * Использует пагинацию по 10 записей на страницу
     * с сохранением параметров поиска и фильтрации в URL.
     *
     * Дополнительно формирует статистику:
     * - общее количество вакансий;
     * - количество вакансий, опубликованных сегодня;
     * - количество вакансий за последние 7 дней.
     *
     * Получает актуальный статус синхронизации и определяет:
     * - доступность Laravel Scheduler (heartbeat не старше 3 минут);
     * - наличие зависшего процесса парсинга (выполняется более 15 минут).
     *
     * Передаёт подготовленные данные в представление vacancies.index.
     *
     * @param Request $request HTTP-запрос с параметрами поиска и фильтрации.
     * @param HhSyncStatusService $syncStatusService Сервис получения статуса синхронизации.
     *
     * @return View Представление со списком вакансий, статистикой
     *              и информацией о состоянии системы.
     */
    public function index(Request $request, HhSyncStatusService $syncStatusService): View
    {
        $vacancies = HhVacancy::query()->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->trim()->toString();

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('employer_name', 'ilike', "%{$search}%")
                        ->orWhere('area_name', 'ilike', "%{$search}%");
                });
            })
            ->when($request->input('period') === 'today', function ($query) {
                $query->where('published_at', '>=', now()->startOfDay());
            })
            ->orderByRaw('published_at IS NULL ASC')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => HhVacancy::count(),
            'today' => HhVacancy::where('published_at', '>=', now()->startOfDay())->count(),
            'week' => HhVacancy::where('published_at', '>=', now()->subDays(7))->count(),
        ];

        $syncStatus = $syncStatusService->getStatus();

        // Heartbeat считается актуальным, если обновлялся не более 3 минут назад.
        $schedulerIsOnline = $syncStatus->scheduler_last_seen_at !== null
            && $syncStatus->scheduler_last_seen_at->greaterThan(now()->subMinutes(3));

        // Если парсер выполняется более 15 минут, считаем запуск зависшим.
        $syncIsStuck = $syncStatus->status === 'running'
            && $syncStatus->started_at !== null
            && $syncStatus->started_at->lessThan(now()->subMinutes(15));

        return view('vacancies.index', compact(
            'vacancies',
            'stats',
            'syncStatus',
            'schedulerIsOnline',
            'syncIsStuck'
        ));
    }

    /**
     * Отображает подробную информацию о вакансии.
     *
     * При открытии страницы отмечает вакансию как просмотренную,
     * устанавливая текущее время в поле viewed_at.
     *
     * Если вакансия уже была просмотрена, первоначальное время
     * просмотра сохраняется без изменений.
     *
     * После обновления записи перечитывает модель из базы данных,
     * чтобы получить актуальное состояние вакансии.
     *
     * Передаёт данные вакансии в представление vacancies.show.
     *
     * @param HhVacancy $vacancy Вакансия, полученная через Route Model Binding.
     *
     * @return View Представление с подробной информацией о вакансии.
     */
    public function show(HhVacancy $vacancy): View
    {
        HhVacancy::query()
            ->whereKey($vacancy->id)
            ->whereNull('viewed_at')
            ->update([
                'viewed_at' => now(),
            ]);

        $vacancy->refresh();

        return view('vacancies.show', compact('vacancy'));
    }
}
