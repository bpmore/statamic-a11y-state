<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

/**
 * One headed part of the document. Paragraphs first, then a list, which is
 * the only two shapes the statement needs. A link is a paragraph or item
 * whose whole text is a URL; the HTML renderer makes it one.
 */
final class Section
{
    /**
     * @param  list<string>  $paragraphs
     * @param  list<string>  $items
     */
    public function __construct(
        public readonly string $key,
        public readonly string $heading,
        public readonly array $paragraphs = [],
        public readonly array $items = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->paragraphs === [] && $this->items === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'heading' => $this->heading,
            'paragraphs' => $this->paragraphs,
            'items' => $this->items,
        ];
    }
}
