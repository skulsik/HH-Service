<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель состояния синхронизации вакансий HeadHunter.
 *
 * Хранит информацию о текущем и последнем выполнении парсера,
 * статистику обработки вакансий, сведения об ошибках
 * и время последней активности Laravel Scheduler.
 *
 * Используется для мониторинга работоспособности парсера
 * и планировщика задач.
 *
 * @property int $id Идентификатор записи.
 * @property string $status Текущий статус синхронизации (pending, running, successful, failed).
 * @property \Carbon\CarbonImmutable|null $started_at Время начала синхронизации.
 * @property \Carbon\CarbonImmutable|null $finished_at Время завершения синхронизации.
 * @property \Carbon\CarbonImmutable|null $last_success_at Время последней успешной синхронизации.
 * @property int $found Количество найденных вакансий.
 * @property int $processed Количество обработанных вакансий.
 * @property int $created Количество созданных вакансий.
 * @property int $updated Количество обновлённых вакансий.
 * @property int $errors Количество ошибок при обработке.
 * @property string|null $error_message Описание последней ошибки.
 * @property \Carbon\CarbonImmutable|null $scheduler_last_seen_at Время последней активности планировщика.
 * @property \Carbon\CarbonImmutable|null $created_at Время создания записи.
 * @property \Carbon\CarbonImmutable|null $updated_at Время последнего обновления записи.
 */
class HhSyncStatus extends Model
{
    /**
     * Атрибуты, доступные для массового заполнения.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'started_at',
        'finished_at',
        'last_success_at',
        'found',
        'processed',
        'created',
        'updated',
        'errors',
        'error_message',
        'scheduler_last_seen_at',
    ];

    /**
     * Преобразования типов атрибутов модели.
     *
     * Даты преобразуются в объекты CarbonImmutable,
     * а числовые показатели статистики — в целые числа.
     *
     * Использование immutable_datetime предотвращает
     * непреднамеренное изменение объектов дат.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'last_success_at' => 'immutable_datetime',
            'found' => 'integer',
            'processed' => 'integer',
            'created' => 'integer',
            'updated' => 'integer',
            'errors' => 'integer',
            'scheduler_last_seen_at' => 'immutable_datetime',
        ];
    }
}
