<?php

namespace App\Services\News;

readonly class NewsArticleData
{
    /**
     * Provider-agnostic DTO used to normalize external payloads.
     */
    public function __construct(
        public string $source,
        public string $externalId,
        public string $title,
        public ?string $description,
        public ?string $content,
        public string $url,
        public ?string $imageUrl,
        public ?string $author,
        public ?string $category,
        public ?\DateTimeInterface $publishedAt,
        public array $raw,
    ) {
    }

    public function toDatabaseArray(): array
    {
        // Returns a DB-ready payload for bulk upsert (no model casting layer involved).
        return [
            'source' => $this->source,
            'external_id' => $this->externalId,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'url' => $this->url,
            'image_url' => $this->imageUrl,
            'author' => $this->author,
            'category' => $this->category,
            'published_at' => $this->publishedAt?->format('Y-m-d H:i:s'),
            // Upsert bypasses model casts, so JSON columns must be serialized manually.
            'raw' => json_encode($this->raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
