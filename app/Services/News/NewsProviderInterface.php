<?php

namespace App\Services\News;

use Illuminate\Support\Collection;

interface NewsProviderInterface
{
    /**
     * Stable internal key used in config and sync summaries.
     */
    public function sourceKey(): string;

    /**
     * Fetch and normalize provider articles from the given start time.
     *
     * @return \Illuminate\Support\Collection<int, \App\Services\News\NewsArticleData>
     */
    public function fetch(\DateTimeInterface $from): Collection;
}
