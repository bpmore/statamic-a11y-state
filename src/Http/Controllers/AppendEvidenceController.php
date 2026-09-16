<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Evidence\UnknownPredecessor;
use Bpmore\A11yState\Statement\Dates;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * Write one record by hand, or pull fixes from the integrations. The
 * actor is the signed-in user and is not a field: a record that lets its
 * author type somebody else's name is not evidence of who did it.
 */
final class AppendEvidenceController extends CpController
{
    public function __invoke(Request $request, Statements $statements)
    {
        abort_unless(User::current()?->can('record accessibility evidence'), 403);

        $site = StatementPageData::site($request);
        $to = StatementPageData::urls($site)['evidence'];

        if ($request->input('import') === '1') {
            $since = Dates::parse((string) $request->input('since', '')) ?? Dates::now()->modify('-365 days');
            $written = $statements->importFixes($site, $since);

            return redirect()->to($to)->with('success', $written === 0 ? 'Nothing new to import.' : "{$written} ".($written === 1 ? 'record' : 'records').' imported from '.$statements->findings()->name().'.');
        }

        $action = (string) $request->input('action', '');

        if (! Actions::isKnown($action) || $action === Actions::ATTESTED) {
            // Attestations are written by the attestation step and nowhere else.
            return redirect()->to($to.'#a11y-state-record')->withInput()->with('error', 'Pick what was done.');
        }

        $notes = trim((string) $request->input('notes', ''));

        if ($notes === '') {
            return redirect()->to($to.'#a11y-state-record')->withInput()->with('error', 'Say what happened. A record with no notes is a date and nothing else.');
        }

        $supersedes = trim((string) $request->input('supersedes', ''));
        $now = Dates::now();

        try {
            $record = new Record(
                id: Record::newId($now),
                at: Dates::moment($now),
                actor: SaveStatementController::actor(),
                action: $action,
                criterion: self::string($request->input('criterion')),
                subject: self::string($request->input('subject')),
                notes: $notes,
                supersedes: $supersedes === '' ? null : $supersedes,
                meta: ['site' => $site],
            );

            $statements->log()->append($record);
        } catch (UnknownPredecessor|\InvalidArgumentException $e) {
            return redirect()->to($to.'#a11y-state-record')->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to($to)->with('success', 'Record '.$record->id.' written.');
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
