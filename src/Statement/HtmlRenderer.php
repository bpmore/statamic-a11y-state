<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

/**
 * The document as an HTML fragment: one heading for the title at the level
 * asked for, one level down for each section, paragraphs and lists. No
 * classes, no script, no styling, so it reads the same in any theme and in
 * any reader, and nothing in it needs JavaScript or colour to mean
 * anything.
 *
 * A paragraph or item that is a bare URL becomes a link whose text is the
 * URL, so the destination is what a screen reader announces.
 */
final class HtmlRenderer
{
    public function render(Document $document, int $headingLevel = 1): string
    {
        $h = max(1, min(6, $headingLevel));
        $sub = min(6, $h + 1);
        $lang = htmlspecialchars($document->language, ENT_QUOTES, 'UTF-8');

        $out = [];
        $out[] = "<article class=\"grange-statement\" lang=\"{$lang}\">";
        $out[] = "<h{$h}>".$this->e($document->title)."</h{$h}>";
        $out[] = '<p class="grange-statement__organization">'.$this->e($document->organization).'</p>';
        $out[] = '<p class="grange-statement__status">Conformance status: <strong>'.$this->e($document->status->label()).'</strong></p>';

        foreach ($document->sections as $section) {
            $key = htmlspecialchars($section->key, ENT_QUOTES, 'UTF-8');
            $out[] = "<section class=\"grange-statement__section\" id=\"grange-{$key}\">";
            $out[] = "<h{$sub}>".$this->e($section->heading)."</h{$sub}>";

            foreach ($section->paragraphs as $paragraph) {
                $out[] = '<p>'.$this->inline($paragraph).'</p>';
            }

            if ($section->items !== []) {
                $out[] = '<ul>';

                foreach ($section->items as $item) {
                    $out[] = '<li>'.$this->inline($item).'</li>';
                }

                $out[] = '</ul>';
            }

            $out[] = '</section>';
        }

        $out[] = '<section class="grange-statement__section" id="grange-about">';
        $out[] = "<h{$sub}>About this statement</h{$sub}>";
        $out[] = '<p>'.$this->e($document->disclaimer).'</p>';
        $out[] = '<p><small>Generated '.$this->e($document->generatedAt).' under the '.$this->e($document->profile).' profile.</small></p>';
        $out[] = '</section>';
        $out[] = '</article>';

        return implode("\n", $out);
    }

    /**
     * Escaped text, with a bare URL made a link and line breaks kept.
     */
    private function inline(string $text): string
    {
        $trimmed = trim($text);

        if (preg_match('~^https?://\S+$~', $trimmed) === 1) {
            $url = $this->e($trimmed);

            return "<a href=\"{$url}\">{$url}</a>";
        }

        return nl2br($this->e($text), false);
    }

    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
