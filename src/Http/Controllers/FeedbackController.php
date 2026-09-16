<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Feedback\FeedbackWindow;
use Bpmore\A11yState\Feedback\FormSubmissions;
use Bpmore\A11yState\Statement\Dates;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * The acknowledgment clock for the feedback form: every submission, how
 * long it waited, and which are past the window if one is configured.
 */
final class FeedbackController extends CpController
{
    public function __invoke(Request $request, Statements $statements, FormSubmissions $submissions, FeedbackWindow $window)
    {
        $site = StatementPageData::site($request);
        $acks = $statements->log()->filter(new Filter(action: Actions::FEEDBACK_ACKNOWLEDGED));
        $report = $window->report($submissions->all(), $acks, Dates::now());

        return Inertia::render('utilities/Show', [
            'title' => 'A11y State: Feedback',
            'html' => view('a11y-state::utilities.feedback', [
                'site' => $site,
                'form' => $submissions->handle(),
                'formExists' => $submissions->formExists(),
                'report' => $report,
                'urls' => StatementPageData::urls($site),
                'canRecord' => (bool) User::current()?->can('record accessibility evidence'),
            ])->render(),
        ]);
    }
}
