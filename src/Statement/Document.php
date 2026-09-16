<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

/**
 * The generated statement: headed sections of plain text, with nothing in
 * it that a renderer has to interpret. Markdown and HTML are both written
 * from this, so they cannot disagree about what the statement says.
 */
final class Document
{
    /**
     * @param  list<Section>  $sections
     */
    public function __construct(
        public readonly string $title,
        public readonly string $organization,
        public readonly ConformanceStatus $status,
        public readonly string $profile,
        public readonly array $sections,
        public readonly string $disclaimer,
        public readonly string $generatedAt,
        public readonly string $language = 'en',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'organization' => $this->organization,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'profile' => $this->profile,
            'sections' => array_map(fn (Section $s) => $s->toArray(), $this->sections),
            'disclaimer' => $this->disclaimer,
            'generated_at' => $this->generatedAt,
            'language' => $this->language,
        ];
    }
}
