<?php

namespace App\Services\YandexMaps;

use App\Services\Logging\AppLogger;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use JsonException;

class YandexMapsScraper
{
    private Client $client;

    public function __construct(private readonly AppLogger $logger)
    {
        $this->client = new Client([
            'allow_redirects' => true,
            'http_errors' => false,
            'timeout' => config('yandex_maps.timeout'),
        ]);
    }

    /**
     * @return array{
     *     business_id: string,
     *     source_url: string,
     *     title: string|null,
     *     address: string|null,
     *     average_rating: float|null,
     *     rating_count: int,
     *     review_count: int,
     *     parsed_review_count: int,
     *     reviews: array<int, array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     */
    public function parse(string $url): array
    {
        $this->logger->log('YandexMapsScraper@parse.start', ['url' => $url]);
        $this->assertValidUrl($url);

        $cookieJar = new CookieJar();
        $html = $this->fetchHtml($url, $cookieJar);
        $state = $this->extractState($html);
        $expectedBusinessId = $this->extractBusinessIdFromUrl($url);
        $business = $this->findBusinessResult($state, $expectedBusinessId);
        $config = $state['config'] ?? [];

        $businessId = (string) ($business['id'] ?? $expectedBusinessId);
        if ($businessId === '') {
            throw new YandexMapsParserException('Не удалось определить ID организации на Яндекс.Картах.');
        }

        $reviews = $this->fetchAllReviews($businessId, $business, $config, $cookieJar, $url);
        $ratingData = is_array($business['ratingData'] ?? null) ? $business['ratingData'] : [];

        $result = [
            'business_id' => $businessId,
            'source_url' => $url,
            'title' => $business['title'] ?? $business['shortTitle'] ?? null,
            'address' => $business['fullAddress'] ?? $business['address'] ?? null,
            'average_rating' => isset($ratingData['ratingValue']) ? (float) $ratingData['ratingValue'] : null,
            'rating_count' => (int) ($ratingData['ratingCount'] ?? 0),
            'review_count' => (int) ($ratingData['reviewCount'] ?? 0),
            'parsed_review_count' => count($reviews),
            'reviews' => array_values($reviews),
            'meta' => [
                'parser' => 'yandex_maps_internal_reviews_endpoint',
                'business_request_id' => $business['requestId'] ?? null,
                'page_size' => (int) config('yandex_maps.page_size'),
                'max_reviews' => (int) config('yandex_maps.max_reviews'),
                'initial_review_results_params' => $business['reviewResults']['params'] ?? null,
            ],
        ];

        $this->logger->log('YandexMapsScraper@parse.done', [
            'business_id' => $businessId,
            'rating_count' => $result['rating_count'],
            'review_count' => $result['review_count'],
            'parsed_review_count' => $result['parsed_review_count'],
        ]);

        return $result;
    }

    private function assertValidUrl(string $url): void
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new YandexMapsParserException('Ссылка должна быть корректным URL.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));

        $isYandexHost = preg_match('/(^|\.)yandex\.[a-z.]+$/', $host) === 1 || $host === 'ya.ru';
        $looksLikeMaps = str_contains($path, '/maps') || str_starts_with($host, 'maps.');

        if (! in_array($scheme, ['http', 'https'], true) || ! $isYandexHost || ! $looksLikeMaps) {
            throw new YandexMapsParserException('Нужна ссылка на карточку организации в Яндекс.Картах.');
        }
    }

    private function fetchHtml(string $url, CookieJar $cookieJar): string
    {
        try {
            $response = $this->client->get($url, [
                'cookies' => $cookieJar,
                'headers' => $this->browserHeaders($url),
            ]);
        } catch (GuzzleException $exception) {
            throw new YandexMapsParserException('Страница Яндекс.Карт недоступна: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->getStatusCode() >= 400) {
            throw new YandexMapsParserException('Яндекс.Карты вернули HTTP '.$response->getStatusCode().'.');
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            throw new YandexMapsParserException('Яндекс.Карты вернули пустую страницу.');
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractState(string $html): array
    {
        if (! preg_match('/<script[^>]*class="state-view"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            throw new YandexMapsParserException('Не найден JSON state-view на странице Яндекс.Карт.');
        }

        $json = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        try {
            $state = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new YandexMapsParserException('Не удалось разобрать state-view JSON.', previous: $exception);
        }

        if (! is_array($state)) {
            throw new YandexMapsParserException('state-view JSON имеет неожиданный формат.');
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function findBusinessResult(array $state, ?string $expectedBusinessId): array
    {
        $results = [];
        $this->collectBusinessResults($state, $results);

        if ($results === []) {
            throw new YandexMapsParserException('На странице не найдена карточка организации.');
        }

        if ($expectedBusinessId !== null) {
            foreach ($results as $result) {
                if ((string) ($result['id'] ?? '') === $expectedBusinessId) {
                    return $result;
                }
            }
        }

        foreach ($results as $result) {
            if (isset($result['reviewResults']) || isset($result['ratingData'])) {
                return $result;
            }
        }

        return $results[0];
    }

    /**
     * @param mixed $node
     * @param array<int, array<string, mixed>> $results
     */
    private function collectBusinessResults(mixed $node, array &$results): void
    {
        if (! is_array($node)) {
            return;
        }

        if (($node['type'] ?? null) === 'business' && isset($node['id'])) {
            $results[] = $node;
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectBusinessResults($value, $results);
            }
        }
    }

    private function extractBusinessIdFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');

        if (preg_match('#/org/[^/]+/(\d+)#', $path, $matches)) {
            return $matches[1];
        }

        parse_str($query, $queryParams);
        foreach (['oid', 'businessId', 'll'] as $key) {
            if (isset($queryParams[$key]) && is_scalar($queryParams[$key]) && preg_match('/^\d+$/', (string) $queryParams[$key])) {
                return (string) $queryParams[$key];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $business
     * @param array<string, mixed> $config
     * @return array<string, array<string, mixed>>
     */
    private function fetchAllReviews(
        string $businessId,
        array $business,
        array $config,
        CookieJar $cookieJar,
        string $referer
    ): array {
        $pageSize = max(1, (int) config('yandex_maps.page_size'));
        $maxReviews = max($pageSize, (int) config('yandex_maps.max_reviews'));
        $reportedReviewCount = (int) data_get($business, 'ratingData.reviewCount', $maxReviews);
        $targetCount = min($maxReviews, max($reportedReviewCount, $pageSize));
        $pageLimit = (int) ceil($targetCount / $pageSize);
        $reviews = [];

        for ($page = 1; $page <= $pageLimit && count($reviews) < $targetCount; $page++) {
            $pageData = $this->fetchReviewsPage($businessId, $business, $config, $cookieJar, $referer, $page, $pageSize);
            $pageReviews = $pageData['reviews'] ?? [];

            if (! is_array($pageReviews) || $pageReviews === []) {
                $this->logger->log('YandexMapsScraper@fetchAllReviews.empty_page', [
                    'business_id' => $businessId,
                    'page' => $page,
                ]);
                break;
            }

            foreach ($pageReviews as $rawReview) {
                if (! is_array($rawReview)) {
                    continue;
                }

                $review = $this->normalizeReview($rawReview);
                $reviews[$review['yandex_review_id']] = $review;

                if (count($reviews) >= $targetCount) {
                    break;
                }
            }

            $totalPages = (int) data_get($pageData, 'params.totalPages', $pageLimit);
            if ($totalPages > 0) {
                $pageLimit = min($pageLimit, $totalPages);
            }

            $this->logger->log('YandexMapsScraper@fetchAllReviews.page_loaded', [
                'business_id' => $businessId,
                'page' => $page,
                'loaded_reviews' => count($reviews),
                'target_count' => $targetCount,
            ]);

            $delayMs = (int) config('yandex_maps.request_delay_ms');
            if ($delayMs > 0 && $page < $pageLimit) {
                usleep($delayMs * 1000);
            }
        }

        return $reviews;
    }

    /**
     * @param array<string, mixed> $business
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function fetchReviewsPage(
        string $businessId,
        array $business,
        array $config,
        CookieJar $cookieJar,
        string $referer,
        int $page,
        int $pageSize,
        bool $allowCsrfRetry = true
    ): array {
        $query = $this->buildReviewsQuery($businessId, $business, $config, $page, $pageSize);
        $apiBaseUrl = (string) ($config['apiBaseUrl'] ?? '/maps');
        $endpoint = 'https://yandex.ru'.rtrim($apiBaseUrl, '/').'/api/business/fetchReviews';
        $url = $endpoint.'?'.$this->toSignedQueryString($query);

        try {
            $response = $this->client->get($url, [
                'cookies' => $cookieJar,
                'headers' => $this->browserHeaders($referer) + ['X-Retpath-Y' => $referer],
            ]);
        } catch (GuzzleException $exception) {
            throw new YandexMapsParserException('Не удалось загрузить отзывы: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->getStatusCode() >= 400) {
            throw new YandexMapsParserException('Запрос отзывов вернул HTTP '.$response->getStatusCode().'.');
        }

        try {
            $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new YandexMapsParserException('Ответ отзывов не является JSON.', previous: $exception);
        }

        if (isset($payload['type']) && $payload['type'] === 'captcha') {
            throw new YandexMapsParserException('Яндекс запросил капчу при загрузке отзывов.');
        }

        if (isset($payload['csrfToken'])) {
            if (! $allowCsrfRetry) {
                throw new YandexMapsParserException('Яндекс вернул новый CSRF token, повторный запрос не сработал.');
            }

            $config['csrfToken'] = $payload['csrfToken'];

            return $this->fetchReviewsPage($businessId, $business, $config, $cookieJar, $referer, $page, $pageSize, false);
        }

        if (isset($payload['error'])) {
            $message = is_array($payload['error'])
                ? (string) ($payload['error']['message'] ?? 'ошибка API Яндекс.Карт')
                : (string) $payload['error'];

            throw new YandexMapsParserException('Яндекс.Карты вернули ошибку: '.$message);
        }

        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            throw new YandexMapsParserException('В ответе отзывов нет поля data.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $business
     * @param array<string, mixed> $config
     * @return array<string, scalar|null>
     */
    private function buildReviewsQuery(string $businessId, array $business, array $config, int $page, int $pageSize): array
    {
        return [
            'csrfToken' => $config['csrfToken'] ?? null,
            'sessionId' => data_get($config, 'counters.analytics.sessionId'),
            'host_config' => $config['hostConfig'] ?? null,
            'host_exp' => $config['hostExp'] ?? null,
            'ajax' => '1',
            'businessId' => $businessId,
            'page' => $page,
            'pageSize' => $pageSize,
            'reqId' => $business['requestId'] ?? $config['requestId'] ?? null,
            'ranking' => 'by_relevance_org',
            'locale' => $config['locale'] ?? 'ru_RU',
            'patch' => data_get($config, 'experiments.ui.ugcReviewsPatch'),
            'ugc_params' => data_get($config, 'query.ugc_params'),
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function toSignedQueryString(array $query): string
    {
        $filtered = [];
        foreach ($query as $key => $value) {
            if ($value === null || $value === false || (is_array($value) && $value === [])) {
                continue;
            }

            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $filtered[$key] = $value;
        }

        uksort($filtered, static fn (string $left, string $right): int => strcasecmp($left, $right));
        $signature = $this->queryHash($this->stringifyQuery($filtered));
        $filtered['s'] = $signature;

        return $this->stringifyQuery($filtered);
    }

    /**
     * @param array<string, mixed> $query
     */
    private function stringifyQuery(array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                foreach (Arr::flatten($value) as $item) {
                    $pairs[] = rawurlencode((string) $key).'='.rawurlencode((string) $item);
                }
                continue;
            }

            $pairs[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        return implode('&', $pairs);
    }

    private function queryHash(string $queryString): string
    {
        $hash = 5381;
        $length = strlen($queryString);

        for ($index = 0; $index < $length; $index++) {
            $hash = (($hash * 33) ^ ord($queryString[$index])) & 0xffffffff;
        }

        return (string) $hash;
    }

    /**
     * @param array<string, mixed> $rawReview
     * @return array<string, mixed>
     */
    private function normalizeReview(array $rawReview): array
    {
        $reviewId = (string) ($rawReview['reviewId'] ?? '');
        if ($reviewId === '') {
            $reviewId = sha1(json_encode($rawReview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: uniqid('', true));
        }

        $reviewedAt = null;
        $rawDate = $rawReview['updatedTime'] ?? $rawReview['createdTime'] ?? null;
        if (is_string($rawDate) && $rawDate !== '') {
            try {
                $reviewedAt = CarbonImmutable::parse($rawDate)->toDateTimeString();
            } catch (\Throwable) {
                $reviewedAt = null;
            }
        }

        return [
            'yandex_review_id' => $reviewId,
            'author_name' => data_get($rawReview, 'author.name'),
            'author_public_id' => data_get($rawReview, 'author.publicId'),
            'reviewed_at' => $reviewedAt,
            'text' => $rawReview['text'] ?? null,
            'rating' => isset($rawReview['rating']) ? (int) $rawReview['rating'] : null,
            'raw' => $rawReview,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function browserHeaders(string $referer): array
    {
        return [
            'User-Agent' => (string) config('yandex_maps.user_agent'),
            'Accept' => 'application/json, text/html, text/plain, */*',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8',
            'Referer' => $referer,
        ];
    }
}
