<?php

namespace App\Console\Commands;

use App\Services\Hh\HhSyncStatusService;
use App\Services\Hh\HhVacancyParser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Команда для получения и синхронизации вакансий с HeadHunter API.
 *
 * Выполняет поиск вакансий по заданному поисковому запросу и региону,
 * обрабатывает полученные данные и сохраняет их в базе данных.
 *
 * Поддерживает постраничную загрузку и возможность отключения
 * получения подробной информации о вакансиях.
 *
 * После завершения парсинга обновляет статус синхронизации
 * и выводит статистику обработки.
 *
 * @package App\Console\Commands
 */
#[Signature('hh:parse
       {--query= : Search query}
       {--area=113 : Region ID}
       {--pages=1 : Number of pages}
       {--per-page=100 : Vacancies per page}
       {--no-details : Skip loading detailed vacancy information}')]
#[Description('Parse vacancies from HeadHunter API')]
class ParseHhVacancies extends Command
{
    protected $signature = 'hh:parse
       {--query= : Search query}
       {--area=113 : Region ID}
       {--pages=1 : Number of pages}
       {--per-page=100 : Vacancies per page}
       {--no-details : Skip loading detailed vacancy information}';

    protected $description = 'Parse vacancies from HeadHunter API';

    /**
     * Выполняет парсинг вакансий HeadHunter и синхронизацию данных.
     *
     * Получает параметры поиска из аргументов команды или конфигурации,
     * запускает парсер, обновляет статус синхронизации и выводит
     * статистику результатов обработки.
     *
     * В случае возникновения исключения фиксирует ошибку синхронизации
     * и завершает выполнение команды с кодом ошибки.
     *
     * @param HhVacancyParser $parser Сервис получения и обработки вакансий.
     * @param HhSyncStatusService $syncStatus Сервис управления статусом синхронизации.
     *
     * @return int Код завершения команды (SUCCESS или FAILURE).
     *
     * @throws Throwable Исключения обрабатываются внутри метода.
     */
    public function handle(HhVacancyParser $parser, HhSyncStatusService $syncStatus): int
    {
        $query = $this->option('query') ?: config('hh.search.text');

        $area = $this->option('area');
        $pages = (int) $this->option('pages');
        $perPage = (int) $this->option('per-page');
        $withDetails = !$this->option('no-details');

        $this->info('Starting HH vacancy parser...');
        $this->info("Query: {$query}");
        $this->info("Area: {$area}");

        $syncStatus->markAsRunning();

        try {
            $stats = $parser->parse(
                query: $query,
                area: $area,
                pages: $pages,
                perPage: $perPage,
                withDetails: $withDetails
            );

            $syncStatus->markAsSuccessful($stats);

            $this->newLine();
            $this->info('Parsing completed.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Found', $stats['found']],
                    ['Processed', $stats['processed']],
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Errors', $stats['errors']],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $syncStatus->markAsFailed($e);

            $this->error('Parsing failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
