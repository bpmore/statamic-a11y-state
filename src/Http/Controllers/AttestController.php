<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Http\Controllers;

use Bpmore\A11yState\Statement\Dates;
use Bpmore\A11yState\Statement\IncompleteStatement;
use Bpmore\A11yState\Statement\Overclaim;
use Bpmore\A11yState\Statements;
use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * The attestation step: its own form, its own permission, its own
 * refusals. The only way a statement becomes fully conformant.
 */
final class AttestController extends CpController
{
    public function __invoke(Request $request, Statements $statements)
    {
        abort_unless(User::current()?->can('attest accessibility conformance'), 403);

        $site = StatementPageData::site($request);
        $to = StatementPageData::urls($site)['index'].'#a11y-state-attest';

        $name = trim((string) $request->input('attestation.name', ''));
        $date = trim((string) $request->input('attestation.date', ''));
        $basis = trim((string) $request->input('attestation.basis', ''));

        if ($name === '') {
            return self::refuse($to, 'An attestation needs the name of the person attesting.');
        }

        if (! Dates::isDate($date)) {
            return self::refuse($to, 'An attestation needs the date it was made, in the form YYYY-MM-DD.');
        }

        if ($basis === '') {
            return self::refuse($to, 'An attestation needs its basis: what was evaluated, how, and by whom.');
        }

        try {
            $statements->attest($site, $name, $date, $basis, SaveStatementController::actor());
        } catch (Overclaim|IncompleteStatement|\InvalidArgumentException $e) {
            return self::refuse($to, $e->getMessage());
        }

        return redirect()->to(StatementPageData::urls($site)['index'])->with('success', "Recorded: {$name} attested on {$date} that the site is fully conformant.");
    }

    private static function refuse(string $to, string $message)
    {
        return redirect()->to($to)->withInput()->with('error', $message);
    }
}
