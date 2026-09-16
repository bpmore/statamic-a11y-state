<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

/**
 * The document as Markdown, for a site that keeps its statement in a
 * repository or pastes it somewhere the HTML cannot go.
 */
final class MarkdownRenderer
{
    public function render(Document $document): string
    {
        $out = [];
        $out[] = '# '.$document->title;
        $out[] = '';
        $out[] = $document->organization;
        $out[] = '';
        $out[] = '**Conformance status:** '.$document->status->label();
        $out[] = '';

        foreach ($document->sections as $section) {
            $out[] = '## '.$section->heading;
            $out[] = '';

            foreach ($section->paragraphs as $paragraph) {
                $out[] = $paragraph;
                $out[] = '';
            }

            foreach ($section->items as $item) {
                $out[] = '- '.$item;
            }

            if ($section->items !== []) {
                $out[] = '';
            }
        }

        $out[] = '## About this statement';
        $out[] = '';
        $out[] = $document->disclaimer;
        $out[] = '';
        $out[] = 'Generated '.$document->generatedAt.' under the '.$document->profile.' profile.';
        $out[] = '';

        return implode("\n", $out);
    }
}
