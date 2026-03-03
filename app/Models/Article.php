<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'source',
        'external_id',
        'title',
        'description',
        'content',
        'url',
        'image_url',
        'author',
        'category',
        'published_at',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'raw' => 'array',
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%")
                        ->orWhere('author', 'like', "%{$term}%");
                });
            })
            ->when($filters['sources'] ?? null, fn (Builder $query, array $sources) => $query->whereIn('source', $sources))
            ->when($filters['categories'] ?? null, fn (Builder $query, array $categories) => $query->whereIn('category', $categories))
            ->when($filters['authors'] ?? null, fn (Builder $query, array $authors) => $query->whereIn('author', $authors))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('published_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('published_at', '<=', $to));
    }
}
