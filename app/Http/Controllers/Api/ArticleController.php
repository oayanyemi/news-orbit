<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    private const FILTERS_CACHE_KEY = 'articles.filters.v1';

    private const TOP_STORY_SECTIONS = [
        'arts',
        'automobiles',
        'books/review',
        'business',
        'fashion',
        'food',
        'health',
        'home',
        'insider',
        'magazine',
        'movies',
        'nyregion',
        'obituaries',
        'opinion',
        'politics',
        'realestate',
        'science',
        'sports',
        'sundayreview',
        'technology',
        'theater',
        't-magazine',
        'travel',
        'upshot',
        'us',
        'world',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'sources' => ['nullable', 'array'],
            'sources.*' => ['string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
            'authors' => ['nullable', 'array'],
            'authors.*' => ['string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'client_id' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $filters = $this->mergePreferenceFilters($validated);

        $articles = Article::query()
            ->filter($filters)
            ->latest('published_at')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($articles);
    }

    public function filters(): JsonResponse
    {
        $ttl = max(1, (int) config('news.filters_cache_ttl_seconds', 300));

        $filters = Cache::remember(self::FILTERS_CACHE_KEY, now()->addSeconds($ttl), function (): array {
            $sources = Article::query()
                ->whereNotNull('source')
                ->where('source', '!=', '')
                ->distinct()
                ->orderBy('source')
                ->pluck('source')
                ->values()
                ->all();

            $categories = Article::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->values()
                ->all();

            $authors = Article::query()
                ->whereNotNull('author')
                ->where('author', '!=', '')
                ->distinct()
                ->orderBy('author')
                ->pluck('author')
                ->values()
                ->all();

            return [
                'sources' => $sources,
                'categories' => $categories,
                'authors' => $authors,
            ];
        });

        return response()->json($filters);
    }

    public function topStories(Request $request, string $section): JsonResponse
    {
        $request->merge(['section' => $section]);
        $validated = $request->validate([
            'section' => ['required', 'string', 'in:'.implode(',', self::TOP_STORY_SECTIONS)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $section = strtolower($validated['section']);
        $limit = (int) ($validated['limit'] ?? 25);
        $offset = (int) ($validated['offset'] ?? 0);

        $query = Article::query()->latest('published_at');

        if ($section !== 'home') {
            $query->whereRaw('LOWER(category) = ?', [$section]);
        }

        $articles = $query->skip($offset)->take($limit)->get();

        $results = $articles->map(function (Article $article) use ($section): array {
            $raw = is_array($article->raw) ? $article->raw : [];
            $multimedia = Arr::get($raw, 'multimedia', []);
            $fallbackMedia = $article->image_url
                ? [[
                    'url' => $article->image_url,
                    'format' => 'Standard Thumbnail',
                    'height' => 75,
                    'width' => 75,
                    'type' => 'image',
                    'subtype' => 'photo',
                    'caption' => $article->title,
                    'copyright' => '',
                ]]
                : [];

            return [
                'section' => Arr::get($raw, 'section', strtolower((string) ($article->category ?? $section))),
                'subsection' => Arr::get($raw, 'subsection', ''),
                'title' => Arr::get($raw, 'title', $article->title),
                'abstract' => Arr::get($raw, 'abstract', $article->description ?? ''),
                'url' => $article->url,
                'uri' => Arr::get($raw, 'uri', $article->external_id),
                'byline' => Arr::get($raw, 'byline', $article->author ? 'By '.$article->author : ''),
                'item_type' => Arr::get($raw, 'item_type', 'Article'),
                'updated_date' => Arr::get($raw, 'updated_date', optional($article->updated_at)->toIso8601String()),
                'created_date' => Arr::get($raw, 'created_date', optional($article->created_at)->toIso8601String()),
                'published_date' => Arr::get($raw, 'published_date', optional($article->published_at)->toIso8601String()),
                'material_type_facet' => Arr::get($raw, 'material_type_facet', 'News'),
                'kicker' => Arr::get($raw, 'kicker', $article->source),
                'des_facet' => Arr::get($raw, 'des_facet', []),
                'org_facet' => Arr::get($raw, 'org_facet', []),
                'per_facet' => Arr::get($raw, 'per_facet', []),
                'geo_facet' => Arr::get($raw, 'geo_facet', []),
                'multimedia' => is_array($multimedia) && ! empty($multimedia) ? $multimedia : $fallbackMedia,
                'short_url' => Arr::get($raw, 'short_url', $article->url),
            ];
        })->values();

        return response()->json([
            'status' => 'OK',
            'copyright' => 'Copyright (c) News Orbit',
            'section' => $section,
            'last_updated' => now()->toIso8601String(),
            'num_results' => $results->count(),
            'results' => $results,
        ]);
    }

    private function mergePreferenceFilters(array $filters): array
    {
        if (empty($filters['client_id'])) {
            return $filters;
        }

        $preference = UserPreference::query()
            ->where('client_id', $filters['client_id'])
            ->first();

        if (! $preference) {
            return $filters;
        }

        if (empty($filters['sources']) && ! empty($preference->sources)) {
            $filters['sources'] = $preference->sources;
        }

        if (empty($filters['categories']) && ! empty($preference->categories)) {
            $filters['categories'] = $preference->categories;
        }

        if (empty($filters['authors']) && ! empty($preference->authors)) {
            $filters['authors'] = $preference->authors;
        }

        return $filters;
    }
}
