<?php

return [
    'max_reviews' => (int) env('YANDEX_MAPS_MAX_REVIEWS', 600),
    'page_size' => (int) env('YANDEX_MAPS_PAGE_SIZE', 50),
    'request_delay_ms' => (int) env('YANDEX_MAPS_REQUEST_DELAY_MS', 250),
    'timeout' => (int) env('YANDEX_MAPS_TIMEOUT', 30),
    'user_agent' => env(
        'YANDEX_MAPS_USER_AGENT',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/125 Safari/537.36'
    ),
];
