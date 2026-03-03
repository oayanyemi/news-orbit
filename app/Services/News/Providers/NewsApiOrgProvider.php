<?php

namespace App\Services\News\Providers;

use App\Services\News\NewsArticleData;
use App\Services\News\NewsProviderInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Collection;

class NewsApiOrgProvider implements NewsProviderInterface
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function sourceKey(): string
    {
        return 'newsapi';
    }

    public function fetch(\DateTimeInterface $from): Collection
    {
        $config = config('news.providers.newsapi');

        if (empty($config['api_key'])) {
            return collect();
        }

        $articles = collect();
        $maxPages = (int) config('news.max_pages_per_source', 2);

        for ($page = 1; $page <= $maxPages; $page++) {
            $response = $this->http->baseUrl($config['base_url'])
                ->acceptJson()
                ->get('/v2/everything', [
                    'apiKey' => $config['api_key'],
                    'q' => 'news',
                    'from' => $from->format(\DateTimeInterface::ATOM),
                    'language' => 'en',
                    'sortBy' => 'publishedAt',
                    'pageSize' => 100,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                break;
            }

            $items = data_get($response->json(), 'articles', []);

            if (empty($items)) {
                break;
            }

            $articles = $articles->merge(
                collect($items)->map(function (array $item): NewsArticleData {
                    $url = (string) ($item['url'] ?? '');

                    return new NewsArticleData(
                        source: 'newsapi',
                        externalId: sha1($url),
                        title: (string) ($item['title'] ?? 'Untitled'),
                        description: $item['description'] ?? null,
                        content: $item['content'] ?? null,
                        url: $url,
                        imageUrl: $item['urlToImage'] ?? null,
                        author: $item['author'] ?? null,
                        category: data_get($item, 'source.name'),
                        publishedAt: isset($item['publishedAt']) ? new \DateTimeImmutable($item['publishedAt']) : null,
                        raw: $item,
                    );
                })
            );
        }

        return $articles->filter(fn (NewsArticleData $article) => $article->url !== '');
    }
}
