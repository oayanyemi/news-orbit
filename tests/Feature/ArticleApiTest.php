<?php

use App\Models\Article;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('filters articles by source and query', function (): void {
    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-1',
        'title' => 'Tech growth in 2026',
        'description' => 'Market update',
        'content' => 'Content',
        'url' => 'https://example.com/g1',
        'author' => 'Jane Doe',
        'category' => 'Technology',
        'published_at' => now(),
        'raw' => [],
    ]);

    Article::query()->create([
        'source' => 'newsapi',
        'external_id' => 'n-1',
        'title' => 'Sports daily',
        'description' => 'Sports update',
        'content' => 'Content',
        'url' => 'https://example.com/n1',
        'author' => 'John Doe',
        'category' => 'Sports',
        'published_at' => now(),
        'raw' => [],
    ]);

    $response = $this->getJson('/api/articles?sources[]=guardian&q=Tech');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.source', 'guardian');
});

it('applies saved preferences when client id is provided', function (): void {
    UserPreference::query()->create([
        'client_id' => 'client-1',
        'sources' => ['guardian'],
        'categories' => ['Technology'],
        'authors' => ['Jane Doe'],
    ]);

    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-1',
        'title' => 'Tech growth in 2026',
        'description' => 'Market update',
        'content' => 'Content',
        'url' => 'https://example.com/g1',
        'author' => 'Jane Doe',
        'category' => 'Technology',
        'published_at' => now(),
        'raw' => [],
    ]);

    Article::query()->create([
        'source' => 'nyt',
        'external_id' => 'ny-1',
        'title' => 'Politics daily',
        'description' => 'Politics update',
        'content' => 'Content',
        'url' => 'https://example.com/ny1',
        'author' => 'Reporter',
        'category' => 'Politics',
        'published_at' => now(),
        'raw' => [],
    ]);

    $response = $this->getJson('/api/articles?client_id=client-1');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.source', 'guardian');
});

it('returns distinct article filter values', function (): void {
    Cache::forget('articles.filters.v1');

    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-11',
        'title' => 'Global markets',
        'description' => 'Market update',
        'content' => 'Content',
        'url' => 'https://example.com/g11',
        'author' => 'Jane Doe',
        'category' => 'Business',
        'published_at' => now(),
        'raw' => [],
    ]);

    Article::query()->create([
        'source' => 'newsapi',
        'external_id' => 'n-11',
        'title' => 'AI trends',
        'description' => 'Tech update',
        'content' => 'Content',
        'url' => 'https://example.com/n11',
        'author' => 'John Doe',
        'category' => 'Technology',
        'published_at' => now(),
        'raw' => [],
    ]);

    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-12',
        'title' => 'Another business story',
        'description' => 'More updates',
        'content' => 'Content',
        'url' => 'https://example.com/g12',
        'author' => 'Jane Doe',
        'category' => 'Business',
        'published_at' => now(),
        'raw' => [],
    ]);

    $response = $this->getJson('/api/articles/filters');

    $response->assertOk();
    $response->assertJsonPath('sources', ['guardian', 'newsapi']);
    $response->assertJsonPath('categories', ['Business', 'Technology']);
    $response->assertJsonPath('authors', ['Jane Doe', 'John Doe']);
});

it('caches article filter values until cache is cleared', function (): void {
    Cache::forget('articles.filters.v1');

    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-cache-1',
        'title' => 'Initial story',
        'description' => 'Initial',
        'content' => 'Content',
        'url' => 'https://example.com/g-cache-1',
        'author' => 'Initial Author',
        'category' => 'Initial Category',
        'published_at' => now(),
        'raw' => [],
    ]);

    $first = $this->getJson('/api/articles/filters');
    $first->assertOk();
    $first->assertJsonPath('sources', ['guardian']);

    Article::query()->create([
        'source' => 'nyt',
        'external_id' => 'ny-cache-1',
        'title' => 'New story',
        'description' => 'New',
        'content' => 'Content',
        'url' => 'https://example.com/ny-cache-1',
        'author' => 'New Author',
        'category' => 'New Category',
        'published_at' => now(),
        'raw' => [],
    ]);

    $cached = $this->getJson('/api/articles/filters');
    $cached->assertOk();
    $cached->assertJsonPath('sources', ['guardian']);

    Cache::forget('articles.filters.v1');

    $fresh = $this->getJson('/api/articles/filters');
    $fresh->assertOk();
    $fresh->assertJsonPath('sources', ['guardian', 'nyt']);
});
