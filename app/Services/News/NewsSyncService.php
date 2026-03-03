<?php

namespace App\Services\News;

use App\Models\Article;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NewsSyncService
{
    /**
     * Pulls normalized rows from enabled providers and bulk upserts them.
     */
    public function sync(array $requestedSources = [], ?Carbon $from = null): array
    {
        $providers = $this->resolveProviders($requestedSources);
        $from ??= now()->subHours((int) config('news.default_sync_window_hours', 24));

        $summary = [
            'from' => $from->toIso8601String(),
            'sources' => [],
            'stored' => 0,
        ];

        foreach ($providers as $source => $provider) {
            /** @var \App\Services\News\NewsProviderInterface $provider */
            $articles = $provider->fetch($from);

            if ($articles->isEmpty()) {
                $summary['sources'][$source] = 0;
                continue;
            }

            $rows = $articles
                ->map(fn (NewsArticleData $article) => $article->toDatabaseArray())
                ->values()
                ->all();

            $chunkSize = max(1, (int) config('news.upsert_chunk_size', 50));

            // Chunking avoids oversized SQL packets on large sync windows.
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                Article::upsert(
                    $chunk,
                    ['source', 'external_id'],
                    ['title', 'description', 'content', 'url', 'image_url', 'author', 'category', 'published_at', 'raw', 'updated_at']
                );
            }

            $summary['sources'][$source] = count($rows);
            $summary['stored'] += count($rows);
        }

        // New/updated articles can change filter values, so invalidate filters cache.
        Cache::forget('articles.filters.v1');

        return $summary;
    }

    private function resolveProviders(array $requestedSources): Collection
    {
        $requested = collect($requestedSources)->filter()->values();

        return collect(config('news.providers', []))
            // Resolve only enabled providers, optionally narrowed by --source option.
            ->filter(function (array $providerConfig, string $source) use ($requested): bool {
                if (! ($providerConfig['enabled'] ?? false)) {
                    return false;
                }

                if ($requested->isNotEmpty() && ! $requested->contains($source)) {
                    return false;
                }

                return isset($providerConfig['class']) && class_exists($providerConfig['class']);
            })
            ->map(fn (array $providerConfig) => app($providerConfig['class']));
    }
}
