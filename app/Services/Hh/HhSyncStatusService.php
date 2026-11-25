<?php

namespace App\Services\Hh;

use App\Models\HhSyncStatus;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Сервис управления состоянием синхронизации вакансий HeadHunter.
 *
 * Отвечает за хранение и обновление информации о процессе парсинга:
 * текущем статусе, времени запуска и завершения, статистике обработки
 * вакансий и возникших ошибках.
 *
 * Использует модель HhSyncStatus для сохранения состояния синхронизации
 * в базе данных.
 *
 * Работает с единственной записью состояния синхронизации
 * с идентификатором id = 1.
 *
 * @package App\Services\Hh
 */
class HhSyncStatusService
{
    /**
     * Получает текущий статус синхронизации.
     *
     * Ищет запись состояния синхронизации с идентификатором 1.
     * Если запись отсутствует, создаёт её со статусом pending.
     *
     * @return HhSyncStatus Модель с текущим состоянием синхронизации.
     */
    public function getStatus(): HhSyncStatus
    {
        return HhSyncStatus::query()->firstOrCreate(
            ['id' => 1],
            ['status' => 'pending']
        );
    }

    /**
     * Отмечает начало новой синхронизации.
     *
     * Устанавливает статус running и фиксирует время запуска.
     *
     * Перед началом новой синхронизации сбрасывает предыдущую статистику
     * обработки и очищает информацию о последней ошибке.
     *
     * Также сбрасывает время завершения предыдущей синхронизации.
     *
     * После обновления перечитывает запись из базы данных.
     *
     * @return HhSyncStatus Обновлённое состояние синхронизации.
     */
    public function markAsRunning(): HhSyncStatus
    {
        $status = $this->getStatus();

        $status->update([
            'status' => 'running',
            'started_at' => Carbon::now(),
            'finished_at' => null,
            'found' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
            'error_message' => null,
        ]);

        return $status->refresh();
    }

    /**
     * Сохраняет успешное завершение синхронизации.
     *
     * Устанавливает статус success, фиксирует время завершения
     * и обновляет время последней успешной синхронизации.
     *
     * Сохраняет статистику обработки вакансий:
     * - found — количество найденных вакансий;
     * - processed — количество обработанных вакансий;
     * - created — количество созданных вакансий;
     * - updated — количество обновлённых вакансий;
     * - errors — количество ошибок при обработке.
     *
     * Если отдельные показатели отсутствуют в переданном массиве,
     * для них устанавливается значение 0.
     *
     * Очищает сообщение о предыдущей ошибке.
     *
     * @param array{
     *     found?: int,
     *     processed?: int,
     *     created?: int,
     *     updated?: int,
     *     errors?: int
     * } $stats Статистика выполненной синхронизации.
     *
     * @return HhSyncStatus Обновлённое состояние синхронизации.
     */
    public function markAsSuccessful(array $stats): HhSyncStatus
    {
        $now = Carbon::now();

        $status = $this->getStatus();

        $status->update([
            'status' => 'success',
            'finished_at' => $now,
            'last_success_at' => $now,
            'found' => $stats['found'] ?? 0,
            'processed' => $stats['processed'] ?? 0,
            'created' => $stats['created'] ?? 0,
            'updated' => $stats['updated'] ?? 0,
            'errors' => $stats['errors'] ?? 0,
            'error_message' => null,
        ]);

        return $status->refresh();
    }

    /**
     * Сохраняет информацию о неудачном завершении синхронизации.
     *
     * Устанавливает статус failed, фиксирует время завершения
     * и сохраняет сообщение исключения.
     *
     * Существующая статистика обработки не сбрасывается,
     * что позволяет сохранить результаты работы парсера
     * до момента возникновения ошибки.
     *
     * После обновления перечитывает запись из базы данных.
     *
     * @param Throwable $exception Исключение, вызвавшее ошибку синхронизации.
     *
     * @return HhSyncStatus Обновлённое состояние синхронизации.
     */
    public function markAsFailed(Throwable $exception): HhSyncStatus
    {
        $status = $this->getStatus();

        $status->update([
            'status' => 'failed',
            'finished_at' => Carbon::now(),
            'error_message' => $exception->getMessage(),
        ]);

        return $status->refresh();
    }
}
