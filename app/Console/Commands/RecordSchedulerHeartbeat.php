<?php

namespace App\Console\Commands;

use App\Models\HhSyncStatus;
use Illuminate\Console\Command;

/**
 * Команда для регистрации активности Laravel Scheduler.
 *
 * Обеспечивает обновление времени последнего успешного обращения
 * планировщика задач к системе.
 *
 * Если запись статуса синхронизации ещё не существует,
 * создаёт её со статусом pending.
 *
 * Используется для мониторинга работоспособности планировщика
 * и определения времени его последней активности.
 */
class RecordSchedulerHeartbeat extends Command
{
    protected $signature = 'hh:scheduler-heartbeat';

    protected $description = 'Record Laravel scheduler heartbeat';

    /**
     * Фиксирует текущее время последней активности планировщика.
     *
     * Находит существующую запись статуса синхронизации
     * или создаёт новую с идентификатором 1 и статусом pending.
     *
     * Обновляет поле scheduler_last_seen_at текущей датой и временем.
     *
     * @return int Код завершения команды (SUCCESS).
     */
    public function handle(): int
    {
        $status = HhSyncStatus::query()->firstOrCreate(
            ['id' => 1],
            ['status' => 'pending']
        );

        $status->update([
            'scheduler_last_seen_at' => now(),
        ]);

        return self::SUCCESS;
    }
}
