<?php

declare(strict_types=1);

namespace Bpmore\Grange\Tags;

use Bpmore\Grange\Statement\HtmlRenderer;
use Bpmore\Grange\Statements;
use Statamic\Facades\Site;
use Statamic\Tags\Tags;

/**
 * `{{ grange:statement }}` in Antlers, `<s:grange:statement />` in Blade.
 *
 * Renders the generated statement for the current site as an HTML
 * fragment, headings from the level the `heading` parameter names (2 by
 * default, 1 on the statement's own page). `site` picks another site.
 *
 * An incomplete statement renders a short notice instead of a document,
 * because the alternative is a page that says nothing while looking like
 * it says something. The notice is plain text and true; it is not the
 * statement and does not claim to be.
 */
class Grange extends Tags
{
    public function statement(): string
    {
        $handle = $this->params->get('site');
        $site = (is_string($handle) ? Site::get($handle) : null) ?? Site::current() ?? Site::default();
        $level = (int) $this->params->get('heading', 2);

        $statements = app(Statements::class);
        $statement = $statements->for($site->handle());
        $document = $statements->documentOrNull($statement);

        if ($document === null) {
            $h = max(1, min(6, $level));

            return "<article class=\"grange-statement grange-statement--unavailable\" lang=\"en\"><h{$h}>Accessibility statement</h{$h}><p>An accessibility statement for this site is being prepared and is not yet published.</p></article>";
        }

        return (new HtmlRenderer)->render($document, $level);
    }
}
