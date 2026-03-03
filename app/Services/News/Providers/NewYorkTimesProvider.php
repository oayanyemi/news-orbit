<?php

namespace App\Services\News\Providers;

use App\Services\News\NewsArticleData;
use App\Services\News\NewsProviderInterface;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Collection;

class NewYorkTimesProvider implements NewsProviderInterface
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function sourceKey(): string
    {
        return 'nyt';
    }

    public function fetch(\DateTimeInterface $from): Collection
    {
        $config = config('news.providers.nyt');

        if (empty($config['api_key'])) {
            return collect();
        }

        $articles = collect();
        $maxPages = (int) config('news.max_pages_per_source', 2);

        for ($page = 0; $page < $maxPages; $page++) {
            $response = $this->http->baseUrl($config['base_url'])
                ->acceptJson()
                ->get('/svc/search/v2/articlesearch.json', [
                    'api-key' => $config['api_key'],
                    'begin_date' => $from->format('Ymd'),
                    'sort' => 'newest',
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                break;
            }

            $docs = data_get($response->json(), 'response.docs', []);

            if (empty($docs)) {
                break;
            }

            $articles = $articles->merge(
                collect($docs)->map(function (array $item): NewsArticleData {
                    $image = collect($item['multimedia'] ?? [])->firstWhere('subtype', 'xlarge');

                    return new NewsArticleData(
                        source: 'nyt',
                        externalId: (string) ($item['_id'] ?? $item['web_url'] ?? ''),
                        title: (string) data_get($item, 'headline.main', 'Untitled'),
                        description: $item['abstract'] ?? null,
                        content: $item['lead_paragraph'] ?? null,
                        url: (string) ($item['web_url'] ?? ''),
                        imageUrl: isset($image['url']) ? 'https://www.nytimes.com/'.$image['url'] : null,
                        author: data_get($item, 'byline.original'),
                        category: data_get($item, 'section_name'),
                        publishedAt: isset($item['pub_date']) ? new \DateTimeImmutable($item['pub_date']) : null,
                        raw: $item,
                    );
                })
            );
        }

        return $articles->filter(fn (NewsArticleData $article) => $article->externalId !== '' && $article->url !== '');
    }
}
