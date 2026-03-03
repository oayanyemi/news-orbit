<?php

namespace App\Services\News\Providers;

use App\Services\News\NewsArticleData;
use App\Services\News\NewsProviderInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Collection;

class GuardianProvider implements NewsProviderInterface
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function sourceKey(): string
    {
        return 'guardian';
    }

    public function fetch(\DateTimeInterface $from): Collection
    {
        $config = config('news.providers.guardian');

        if (empty($config['api_key'])) {
            // Keep sync resilient when credentials are missing locally.
            return collect();
        }

        $articles = collect();
        $maxPages = (int) config('news.max_pages_per_source', 2);

        for ($page = 1; $page <= $maxPages; $page++) {
            $response = $this->http->baseUrl($config['base_url'])
                ->acceptJson()
                ->get('/search', [
                    'api-key' => $config['api_key'],
                    'from-date' => $from->format('Y-m-d'),
                    'show-fields' => 'trailText,byline,thumbnail',
                    'page-size' => 50,
                    'page' => $page,
                    'order-by' => 'newest',
                ]);

            if (! $response->successful()) {
                break;
            }

            $payload = $response->json();
            $results = data_get($payload, 'response.results', []);

            if (empty($results)) {
                break;
            }

            $articles = $articles->merge(
                collect($results)->map(function (array $item): NewsArticleData {
                    // Guardian shape is mapped into our unified article DTO.
                    return new NewsArticleData(
                        source: 'guardian',
                        externalId: (string) ($item['id'] ?? $item['webUrl'] ?? ''),
                        title: (string) ($item['webTitle'] ?? 'Untitled'),
                        description: data_get($item, 'fields.trailText'),
                        // Body text is intentionally skipped to keep packet sizes safe for upsert.
                        content: null,
                        url: (string) ($item['webUrl'] ?? ''),
                        imageUrl: data_get($item, 'fields.thumbnail'),
                        author: data_get($item, 'fields.byline'),
                        category: $item['sectionName'] ?? null,
                        publishedAt: isset($item['webPublicationDate']) ? new \DateTimeImmutable($item['webPublicationDate']) : null,
                        raw: $item,
                    );
                })
            );

            $pages = (int) data_get($payload, 'response.pages', 1);
            if ($page >= $pages) {
                break;
            }
        }

        return $articles->filter(fn (NewsArticleData $article) => $article->externalId !== '' && $article->url !== '');
    }
}
