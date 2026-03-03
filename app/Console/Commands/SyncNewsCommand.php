<?php

namespace App\Console\Commands;

use App\Services\News\NewsSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncNewsCommand extends Command
{
    protected $signature = 'news:sync
        {--source=* : Specific source keys to sync (guardian, nyt, newsapi)}
        {--from= : Start datetime (example: 2026-03-01 00:00:00)}';

    protected $description = 'Fetch and store latest articles from configured providers';

    public function handle(NewsSyncService $syncService): int
    {
        $fromOption = $this->option('from');
        $from = $fromOption ? Carbon::parse($fromOption) : null;

        $summary = $syncService->sync(
            requestedSources: $this->option('source'),
            from: $from,
        );

        $this->info('News sync completed.');
        $this->line('From: '.$summary['from']);
        $this->line('Total stored: '.$summary['stored']);

        foreach ($summary['sources'] as $source => $count) {
            $this->line(" - {$source}: {$count}");
        }

        return self::SUCCESS;
    }
}
