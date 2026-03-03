<?php

return [
    'default_sync_window_hours' => env('NEWS_DEFAULT_SYNC_WINDOW_HOURS', 24),
    'max_pages_per_source' => env('NEWS_MAX_PAGES_PER_SOURCE', 2),
    'upsert_chunk_size' => env('NEWS_UPSERT_CHUNK_SIZE', 50),
    'filters_cache_ttl_seconds' => env('NEWS_FILTERS_CACHE_TTL_SECONDS', 300),

    'providers' => [
        'guardian' => [
            'class' => App\Services\News\Providers\GuardianProvider::class,
            'enabled' => env('GUARDIAN_ENABLED', true),
            'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
            'api_key' => env('GUARDIAN_API_KEY'),
        ],
        'nyt' => [
            'class' => App\Services\News\Providers\NewYorkTimesProvider::class,
            'enabled' => env('NYT_ENABLED', true),
            'base_url' => env('NYT_BASE_URL', 'https://api.nytimes.com'),
            'api_key' => env('NYT_API_KEY'),
        ],
        'newsapi' => [
            'class' => App\Services\News\Providers\NewsApiOrgProvider::class,
            'enabled' => env('NEWSAPI_ENABLED', true),
            'base_url' => env('NEWSAPI_BASE_URL', 'https://newsapi.org'),
            'api_key' => env('NEWSAPI_API_KEY'),
        ],
    ],
];
