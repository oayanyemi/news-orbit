<?php

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns top stories contract envelope for home section', function (): void {
    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-100',
        'title' => 'World economy outlook',
        'description' => 'A short abstract',
        'content' => 'Body',
        'url' => 'https://example.com/world-economy',
        'image_url' => 'https://example.com/world-economy.jpg',
        'author' => 'Jane Doe',
        'category' => 'world',
        'published_at' => now(),
        'raw' => [],
    ]);

    $response = $this->getJson('/api/top-stories/home.json');

    $response->assertOk();
    $response->assertJsonPath('status', 'OK');
    $response->assertJsonPath('section', 'home');
    $response->assertJsonPath('num_results', 1);
    $response->assertJsonPath('results.0.title', 'World economy outlook');
    $response->assertJsonStructure([
        'status',
        'copyright',
        'section',
        'last_updated',
        'num_results',
        'results' => [[
            'section',
            'subsection',
            'title',
            'abstract',
            'url',
            'uri',
            'byline',
            'item_type',
            'updated_date',
            'created_date',
            'published_date',
            'material_type_facet',
            'kicker',
            'des_facet',
            'org_facet',
            'per_facet',
            'geo_facet',
            'multimedia',
            'short_url',
        ]],
    ]);
});

it('filters top stories by section', function (): void {
    Article::query()->create([
        'source' => 'nyt',
        'external_id' => 'ny-10',
        'title' => 'Tech launch today',
        'description' => 'A short abstract',
        'content' => 'Body',
        'url' => 'https://example.com/tech-launch',
        'image_url' => null,
        'author' => 'John Doe',
        'category' => 'technology',
        'published_at' => now(),
        'raw' => [],
    ]);

    Article::query()->create([
        'source' => 'guardian',
        'external_id' => 'g-10',
        'title' => 'Sports finals',
        'description' => 'A short abstract',
        'content' => 'Body',
        'url' => 'https://example.com/sports-finals',
        'image_url' => null,
        'author' => 'Reporter',
        'category' => 'sports',
        'published_at' => now(),
        'raw' => [],
    ]);

    $response = $this->getJson('/api/top-stories/technology.json');

    $response->assertOk();
    $response->assertJsonPath('num_results', 1);
    $response->assertJsonPath('results.0.title', 'Tech launch today');
});

it('supports limit and offset for top stories', function (): void {
    Article::query()->create([
        'source' => 'nyt',
        'external_id' => 'ny-1',
        'title' => 'Newest story',
        'description' => 'A short abstract',
        'content' => 'Body',
        'url' => 'https://example.com/newest',
        'image_url' => null,
        'author' => 'Writer',
        'category' => 'world',
        'published_at' => now()->addMinute(),
        'raw' => [],
    ]);

    Article::query()->create([
        'source' => 'nyt',
        'external_id' => 'ny-2',
        'title' => 'Older story',
        'description' => 'A short abstract',
        'content' => 'Body',
        'url' => 'https://example.com/older',
        'image_url' => null,
        'author' => 'Writer',
        'category' => 'world',
        'published_at' => now(),
        'raw' => [],
    ]);

    $response = $this->getJson('/api/top-stories/home.json?limit=1&offset=1');

    $response->assertOk();
    $response->assertJsonPath('num_results', 1);
    $response->assertJsonPath('results.0.title', 'Older story');
});

it('prefers nyt-like fields from raw payload when available', function (): void {
    Article::query()->create([
        'source' => 'nyt',
        'external_id' => 'ny-raw-1',
        'title' => 'Fallback title',
        'description' => 'Fallback abstract',
        'content' => 'Body',
        'url' => 'https://example.com/fallback',
        'image_url' => 'https://example.com/fallback.jpg',
        'author' => 'Fallback Author',
        'category' => 'technology',
        'published_at' => now(),
        'raw' => [
            'section' => 'technology',
            'subsection' => 'ai',
            'title' => 'Raw title',
            'abstract' => 'Raw abstract',
            'uri' => 'nyt://raw-uri',
            'byline' => 'By Raw Author',
            'item_type' => 'Article',
            'material_type_facet' => 'News',
            'kicker' => 'Tech',
            'des_facet' => ['AI'],
            'org_facet' => ['OpenAI'],
            'per_facet' => ['Researcher'],
            'geo_facet' => ['US'],
            'multimedia' => [[
                'url' => 'https://example.com/raw-media.jpg',
                'format' => 'Standard Thumbnail',
                'height' => 75,
                'width' => 75,
                'type' => 'image',
                'subtype' => 'photo',
                'caption' => 'Raw media',
                'copyright' => 'Example',
            ]],
            'short_url' => 'https://nyti.ms/raw',
        ],
    ]);

    $response = $this->getJson('/api/top-stories/technology.json');

    $response->assertOk();
    $response->assertJsonPath('results.0.title', 'Raw title');
    $response->assertJsonPath('results.0.abstract', 'Raw abstract');
    $response->assertJsonPath('results.0.byline', 'By Raw Author');
    $response->assertJsonPath('results.0.subsection', 'ai');
    $response->assertJsonPath('results.0.short_url', 'https://nyti.ms/raw');
    $response->assertJsonPath('results.0.multimedia.0.url', 'https://example.com/raw-media.jpg');
});
