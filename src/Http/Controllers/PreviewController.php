<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Statement\HtmlRenderer;
use Bpmore\A11yState\Statement\IncompleteStatement;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Http\Controllers\CP\CpController;

/**
 * The generated document as the public page will show it, inside the
 * control panel, or the list of reasons it cannot be generated.
 */
final class PreviewController extends CpController
{
    public function __invoke(Request $request, Statements $statements)
    {
        $site = StatementPageData::site($request);
        $statement = $statements->for($site);
        $html = null;
        $problems = [];

        try {
            $html = (new HtmlRenderer)->render($statements->document($statement), 2);
        } catch (IncompleteStatement $e) {
            $problems = $e->problems;
        }

        return Inertia::render('utilities/Show', [
            'title' => 'A11y State: Preview',
            'html' => view('a11y-state::utilities.preview', [
                'site' => $site,
                'html' => $html,
                'problems' => $problems,
                'urls' => StatementPageData::urls($site),
            ])->render(),
        ]);
    }
}
