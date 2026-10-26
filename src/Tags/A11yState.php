<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Tags;

use Bpmore\A11yState\Http\Controllers\StatementStylesheetController;
use Bpmore\A11yState\Statement\HtmlRenderer;
use Bpmore\A11yState\Statements;
use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;
use Statamic\Tags\Tags;

/**
 * `{{ a11y_state:statement }}` in Antlers, `<s:a11y_state:statement />` in Blade.
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
class A11yState extends Tags
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

            return $this->stylesheet()."<article class=\"a11y-state-statement a11y-state-statement--unavailable\" lang=\"en\"><h{$h}>Accessibility statement</h{$h}><p>An accessibility statement for this site is being prepared and is not yet published.</p></article>";
        }

        return $this->stylesheet().(new HtmlRenderer)->render($document, $level);
    }

    /**
     * The link to the statement's readable default styles, or nothing when
     * the config turns them off. See resources/css/statement.css.
     */
    private function stylesheet(): string
    {
        if (config('statamic-a11y-state.statement_styles', true) === false || ! Route::has('a11y-state.stylesheet')) {
            return '';
        }

        $href = htmlspecialchars(route('a11y-state.stylesheet', ['v' => StatementStylesheetController::version()]), ENT_QUOTES, 'UTF-8');

        return "<link rel=\"stylesheet\" href=\"{$href}\">\n";
    }
}
