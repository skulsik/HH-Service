<?php

namespace App\Services\Hh;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP-клиент для взаимодействия с HeadHunter API.
 *
 * Обеспечивает авторизацию приложения через OAuth 2.0
 * с использованием типа предоставления client_credentials.
 *
 * Реализует получение и кеширование токена доступа,
 * настройку авторизованного HTTP-клиента и выполнение
 * запросов к API HeadHunter для поиска вакансий
 * и получения подробной информации о них.
 *
 * Параметры подключения и авторизации загружаются
 * из конфигурации приложения config/hh.php.
 *
 * @package App\Services\Hh
 */
class HhApiClient
{
    /**
     * Получает OAuth-токен приложения или возвращает его из кеша.
     *
     * При отсутствии действующего токена выполняет запрос
     * к endpoint /token с использованием client_credentials.
     *
     * Полученный токен сохраняется в кеше на 23 часа,
     * что позволяет сократить количество запросов авторизации.
     *
     * Для получения токена необходимы настройки:
     * - hh.client_id;
     * - hh.client_secret;
     * - hh.api_url;
     * - hh.user_agent.
     *
     * @return string OAuth-токен доступа.
     *
     * @throws RuntimeException Если отсутствуют учётные данные
     *                          или API не вернул токен доступа.
     * @throws \Illuminate\Http\Client\RequestException При ошибке HTTP-запроса.
     */
    private function getAccessToken(): string
    {
        return Cache::remember(
            'hh_api_access_token',
            now()->addHours(23),
            function (): string {
                $clientId = config('hh.client_id');
                $clientSecret = config('hh.client_secret');

                if (!$clientId || !$clientSecret) {
                    throw new RuntimeException(
                        'HH_CLIENT_ID or HH_CLIENT_SECRET is missing.'
                    );
                }

                $response = Http::asForm()
                    ->acceptJson()
                    ->withHeaders([
                        'HH-User-Agent' => config('hh.user_agent'),
                    ])
                    ->timeout(30)
                    ->post(
                        rtrim(config('hh.api_url'), '/') . '/token',
                        [
                            'grant_type' => 'client_credentials',
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                        ]
                    );

                $response->throw();

                $token = $response->json('access_token');

                if (!$token) {
                    throw new RuntimeException('HH API did not return access_token.');
                }

                return $token;
            }
        );
    }

    /**
     * Создаёт авторизованный HTTP-клиент для запросов к HeadHunter API.
     *
     * Настраивает:
     * - автоматическую передачу OAuth-токена в заголовке Authorization;
     * - приём JSON-ответов;
     * - заголовок HH-User-Agent;
     * - базовый URL API;
     * - тайм-аут HTTP-запроса 30 секунд.
     *
     * Для авторизации использует токен, полученный методом getAccessToken().
     *
     * @return PendingRequest Настроенный HTTP-клиент Laravel.
     *
     * @throws RuntimeException Если не удалось получить OAuth-токен.
     * @throws \Illuminate\Http\Client\RequestException При ошибке авторизации.
     */
    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->getAccessToken())
            ->withHeaders([
                'HH-User-Agent' => config('hh.user_agent'),
            ])
            ->baseUrl(rtrim(config('hh.api_url'), '/'))
            ->timeout(30);
    }

    /**
     * Выполняет поиск вакансий через HeadHunter API.
     *
     * Отправляет GET-запрос к endpoint /vacancies
     * с указанными параметрами поиска.
     *
     * Поддерживает пагинацию и ограничение количества вакансий
     * на одной странице (от 1 до 100).
     *
     * Результаты сортируются по времени публикации.
     *
     * @param string $text Поисковый запрос.
     * @param string $area Идентификатор региона HeadHunter.
     * @param int $page Номер страницы результатов (начиная с 0).
     * @param int $perPage Количество вакансий на странице (от 1 до 100).
     *
     * @return array<string, mixed> Данные ответа HeadHunter API,
     *                               содержащие найденные вакансии
     *                               и информацию о пагинации.
     *
     * @throws RuntimeException Если количество вакансий на странице
     *                          выходит за допустимые пределы.
     * @throws \Illuminate\Http\Client\RequestException При ошибке HTTP-запроса.
     */
    public function searchVacancies(
        string $text,
        string $area,
        int $page = 0,
        int $perPage = 100
    ): array {
        if ($perPage < 1 || $perPage > 100) {
            throw new RuntimeException('per_page must be between 1 and 100.');
        }

        $response = $this->client()->get('/vacancies', [
            'text' => $text,
            'area' => $area,
            'order_by' => 'publication_time',
            'per_page' => $perPage,
            'page' => $page,
        ]);

        $response->throw();

        return $response->json();
    }

    /**
     * Получает подробную информацию о вакансии через HeadHunter API.
     *
     * Отправляет GET-запрос к endpoint /vacancies/{id}
     * для получения полной карточки вакансии.
     *
     * Идентификатор вакансии предварительно кодируется
     * для безопасного использования в URL.
     *
     * @param string $vacancyId Идентификатор вакансии на HeadHunter.
     *
     * @return array<string, mixed> Полная информация о вакансии,
     *                               возвращённая HeadHunter API.
     *
     * @throws \Illuminate\Http\Client\RequestException При ошибке HTTP-запроса.
     */
    public function getVacancy(string $vacancyId): array
    {
        $response = $this->client()->get('/vacancies/' . urlencode($vacancyId));

        $response->throw();

        return $response->json();
    }
}
