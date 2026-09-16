<?php

declare(strict_types=1);

namespace Bpmore\Grange\Http\Controllers;

use Bpmore\Grange\Evidence\Actions;
use Bpmore\Grange\Evidence\Filter;
use Bpmore\Grange\Feedback\FeedbackWindow;
use Bpmore\Grange\Feedback\FormSubmissions;
use Bpmore\Grange\Statement\Dates;
use Bpmore\Grange\Statements;
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
            'title' => 'Accessibility Statement: Feedback',
            'html' => view('grange::utilities.feedback', [
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
