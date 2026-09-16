<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Feedback\FormSubmissions;
use Bpmore\A11yState\Statement\Dates;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * Acknowledging feedback is an evidence record whose subject is the
 * submission id. That is what stops the clock, and it is in the log with
 * everything else, so an auditor reads one record of what was done.
 */
final class AcknowledgeFeedbackController extends CpController
{
    public function __invoke(Request $request, Statements $statements, FormSubmissions $submissions)
    {
        abort_unless(User::current()?->can('record accessibility evidence'), 403);

        $site = StatementPageData::site($request);
        $to = StatementPageData::urls($site)['feedback'];
        $id = trim((string) $request->input('submission', ''));
        $known = array_filter($submissions->all(), fn ($s) => $s->id === $id);

        if ($id === '' || $known === []) {
            return redirect()->to($to)->with('error', 'That submission is not on the configured form.');
        }

        $notes = trim((string) $request->input('notes', ''));
        $now = Dates::now();

        $record = new Record(
            id: Record::newId($now),
            at: Dates::moment($now),
            actor: SaveStatementController::actor(),
            action: Actions::FEEDBACK_ACKNOWLEDGED,
            subject: $id,
            notes: $notes === '' ? 'Acknowledged' : $notes,
            meta: ['site' => $site, 'form' => $submissions->handle()],
        );

        $statements->log()->append($record);

        return redirect()->to($to)->with('success', 'Acknowledged. Record '.$record->id.' written.');
    }
}
