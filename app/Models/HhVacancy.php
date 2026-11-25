<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель вакансии HeadHunter.
 *
 * Хранит информацию о вакансиях, полученных через HeadHunter API,
 * включая сведения о работодателе, регионе, зарплате, условиях работы,
 * описании вакансии и дате публикации.
 *
 * Содержит дополнительные Accessors для форматирования зарплаты,
 * определения актуальности вакансии, формирования списка характеристик
 * и очистки HTML-разметки описания.
 *
 * @property int $id Идентификатор вакансии в локальной базе данных.
 * @property string $hh_id Идентификатор вакансии на HeadHunter.
 * @property string $name Название вакансии.
 * @property string|null $employer_id Идентификатор работодателя на HeadHunter.
 * @property string|null $employer_name Название работодателя.
 * @property string|null $area_id Идентификатор региона.
 * @property string|null $area_name Название региона.
 * @property array<string, mixed>|null $salary Информация о зарплате.
 * @property array<string, mixed>|null $experience Требования к опыту работы.
 * @property array<string, mixed>|null $employment Тип занятости.
 * @property array<string, mixed>|null $schedule График работы.
 * @property \Carbon\CarbonImmutable|null $published_at Дата и время публикации вакансии.
 * @property string|null $alternate_url Ссылка на вакансию на HeadHunter.
 * @property string|null $description Описание вакансии в HTML.
 * @property string|null $search_query Поисковый запрос, по которому найдена вакансия.
 * @property array<string, mixed>|null $raw_data Исходные данные вакансии из API.
 * @property \Carbon\CarbonImmutable|null $fetched_at Дата и время последнего получения данных.
 * @property \Carbon\CarbonImmutable|null $viewed_at Дата и время первого просмотра вакансии.
 * @property \Carbon\Carbon|null $created_at Дата создания записи.
 * @property \Carbon\Carbon|null $updated_at Дата последнего обновления записи.
 *
 * @property-read string $salary_text Форматированное представление зарплаты.
 * @property-read bool $is_new Признак новой вакансии (опубликована за последние 24 часа).
 * @property-read array<string, string> $attributes_list Характеристики вакансии для отображения.
 * @property-read string $description_text Очищенное от HTML описание вакансии.
 */
class HhVacancy extends Model
{
    /**
     * Атрибуты, доступные для массового заполнения.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hh_id',
        'name',
        'employer_id',
        'employer_name',
        'area_id',
        'area_name',
        'salary',
        'experience',
        'employment',
        'schedule',
        'published_at',
        'alternate_url',
        'description',
        'search_query',
        'raw_data',
        'fetched_at',
        'viewed_at',
    ];

    /**
     * Преобразования типов атрибутов модели.
     *
     * JSON-поля преобразуются в массивы,
     * а даты — в объекты CarbonImmutable.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary' => 'array',
            'experience' => 'array',
            'employment' => 'array',
            'schedule' => 'array',
            'raw_data' => 'array',
            'published_at' => 'immutable_datetime',
            'fetched_at' => 'immutable_datetime',
            'viewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * Возвращает форматированное представление зарплаты.
     *
     * Определяет символ валюты и формирует строку
     * в зависимости от указанных границ зарплаты:
     * - указаны обе границы — диапазон от и до;
     * - указана только нижняя граница — зарплата от;
     * - указана только верхняя граница — зарплата до;
     * - зарплата отсутствует — возвращает сообщение «Не указана».
     *
     * Поддерживаемые валюты: RUB, RUR, USD, EUR, KZT.
     *
     * @return string Форматированная зарплата с обозначением валюты.
     */
    public function getSalaryTextAttribute(): string
    {
        $salary = $this->salary ?? [];

        $currencySymbols = [
            'RUR' => '₽',
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
            'KZT' => '₸',
        ];

        $currency = $salary['currency'] ?? 'RUR';
        $symbol = $currencySymbols[$currency] ?? $currency;

        $from = $salary['from'] ?? null;
        $to = $salary['to'] ?? null;

        if ($from !== null && $to !== null) {
            return number_format($from, 0, '.', ' ')
                . ' – '
                . number_format($to, 0, '.', ' ')
                . ' '
                . $symbol;
        }

        if ($from !== null) {
            return 'от ' . number_format($from, 0, '.', ' ') . ' ' . $symbol;
        }

        if ($to !== null) {
            return 'до ' . number_format($to, 0, '.', ' ') . ' ' . $symbol;
        }

        return 'Не указана';
    }

    /**
     * Определяет, является ли вакансия новой.
     *
     * Вакансия считается новой, если дата её публикации
     * не старше 24 часов относительно текущего времени.
     *
     * Если дата публикации отсутствует, возвращает false.
     *
     * @return bool true, если вакансия опубликована за последние 24 часа.
     */
    public function getIsNewAttribute(): bool
    {
        return $this->published_at !== null && $this->published_at->greaterThanOrEqualTo(now()->subDay());
    }

    /**
     * Формирует список характеристик вакансии для отображения.
     *
     * Извлекает названия опыта работы, типа занятости
     * и графика работы из соответствующих атрибутов модели.
     *
     * Пустые и отсутствующие значения исключаются из результата.
     *
     * @return array<string, string> Ассоциативный массив характеристик вакансии.
     */
    public function getAttributesListAttribute(): array
    {
        return array_filter([
            'Опыт' => $this->experience['name'] ?? null,
            'Занятость' => $this->employment['name'] ?? null,
            'График' => $this->schedule['name'] ?? null,
        ]);
    }

    /**
     * Возвращает очищенное описание вакансии.
     *
     * Сохраняет переносы строк между HTML-блоками,
     * удаляет HTML-теги и декодирует HTML-сущности.
     *
     * Используется для отображения описания вакансии
     * в текстовом формате без HTML-разметки.
     *
     * @return string Очищенное описание вакансии.
     */
    public function getDescriptionTextAttribute(): string
    {
        $description = $this->description ?? '';

        // Сохраняем переносы строк между HTML-блоками.
        $description = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])\b[^>]*>/i', "\n", $description);

        // Убираем HTML и декодируем HTML-сущности.
        $description = html_entity_decode(strip_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($description);
    }
}
