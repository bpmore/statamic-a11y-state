<?php

declare(strict_types=1);

namespace Bpmore\Grange\Http\Controllers;

use Bpmore\Grange\Evidence\Actions;
use Bpmore\Grange\Evidence\Record;
use Bpmore\Grange\Statement\AttestationRequired;
use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Dates;
use Bpmore\Grange\Statement\KnownException;
use Bpmore\Grange\Statement\Overclaim;
use Bpmore\Grange\Statement\Severity;
use Bpmore\Grange\Statements;
use Illuminate\Http\Request;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

/**
 * The editor's save: profile, status, every disclosure the profile shows,
 * a new exception if one was typed, and any exceptions ticked as resolved.
 *
 * A save never reaches "fully conformant" from anywhere else. The status
 * control does not offer it, and a request that sends it anyway is
 * refused by `Statements::save` with the reason.
 *
 * Disclosures the current profile does not list are not in the form and
 * are not touched: `withDisclosures` merges, so a value entered under
 * another profile stays exactly as it was.
 */
final class SaveStatementController extends CpController
{
    public function __invoke(Request $request, Statements $statements)
    {
        abort_unless(User::current()?->can('edit accessibility statement'), 403);

        $site = StatementPageData::site($request);
        $urls = StatementPageData::urls($site);
        $current = $statements->for($site);
        $profiles = $statements->profiles();

        $profileHandle = (string) $request->input('profile', $current->profile);

        if (! $profiles->has($profileHandle)) {
            return self::refuse($urls['index'], "There is no profile named [{$profileHandle}].");
        }

        $status = ConformanceStatus::tryFromLoose($request->input('status'));

        if ($status === null) {
            return self::refuse($urls['index'], 'Pick a conformance status.');
        }

        if ($status === ConformanceStatus::FullyConformant && $current->status !== ConformanceStatus::FullyConformant) {
            return self::refuse($urls['index'], 'A statement becomes fully conformant through the attestation step, not the status control.');
        }

        $profile = $profiles->get($profileHandle);
        $disclosures = [];

        foreach ($profile->disclosures as $d) {
            if (in_array($d->key, \Bpmore\Grange\Statement\Disclosure::STRUCTURED, true)) {
                continue;
            }

            $value = $request->input('disclosures.'.$d->key);
            $value = is_string($value) ? trim($value) : '';

            if ($d->type === 'date' && $value !== '' && ! Dates::isDate($value)) {
                return self::refuse($urls['index'], "\"{$d->label}\" needs a date in the form YYYY-MM-DD.");
            }

            $disclosures[$d->key] = $value;
        }

        $statement = $current->withProfile($profileHandle)->withStatus($status)->withDisclosures($disclosures);
        $actor = self::actor();
        $now = Dates::now();
        $changes = [];
        $records = [];

        // Exceptions ticked as resolved.
        foreach ((array) $request->input('resolve', []) as $id) {
            $existing = is_string($id) ? $statement->exception($id) : null;

            if ($existing === null || ! $existing->isOpen()) {
                continue;
            }

            $statement = $statement->withException(new KnownException(
                $existing->id, $existing->description, $existing->severity, KnownException::RESOLVED,
                $existing->criterion, $existing->page, $existing->reason, $existing->remediationDate, $existing->source,
            ));
            $changes[] = "resolved exception [{$existing->id}]";
            $records[] = new Record(Record::newId($now), Dates::moment($now), $actor, Actions::EXCEPTION_RESOLVED, $existing->criterion, $existing->page, 'Resolved: '.$existing->description, null, ['site' => $site, 'exception' => $existing->id]);
        }

        // A new exception, if the description was filled in.
        $new = (array) $request->input('new_exception', []);
        $description = is_string($new['description'] ?? null) ? trim($new['description']) : '';

        if ($description !== '') {
            $severity = Severity::tryFromLoose($new['severity'] ?? null);

            if ($severity === null) {
                return self::refuse($urls['index'], 'A new exception needs a severity.');
            }

            $date = is_string($new['remediation_date'] ?? null) ? trim($new['remediation_date']) : '';

            if ($date !== '' && ! Dates::isDate($date)) {
                return self::refuse($urls['index'], 'The planned fix date needs to be in the form YYYY-MM-DD.');
            }

            try {
                $exception = new KnownException(
                    id: 'x-'.$now->format('Ymd\THis').'-'.bin2hex(random_bytes(3)),
                    description: $description,
                    severity: $severity,
                    criterion: self::string($new['criterion'] ?? null),
                    page: self::string($new['page'] ?? null),
                    reason: self::string($new['reason'] ?? null),
                    remediationDate: $date === '' ? null : $date,
                );
            } catch (\InvalidArgumentException $e) {
                return self::refuse($urls['index'], $e->getMessage());
            }

            $statement = $statement->withException($exception);
            $changes[] = "added exception [{$exception->id}]";
            $records[] = new Record(Record::newId($now), Dates::moment($now), $actor, Actions::EXCEPTION_OPENED, $exception->criterion, $exception->page, 'Opened: '.$exception->summary(), null, ['site' => $site, 'exception' => $exception->id]);
        }

        if ($profileHandle !== $current->profile) {
            $changes[] = "profile {$current->profile} to {$profileHandle}";
        }

        if ($status !== $current->status) {
            $changes[] = "status {$current->status->value} to {$status->value}";
        }

        try {
            $statements->save($statement, $actor, $changes === [] ? 'Statement saved' : 'Statement saved: '.implode('; ', $changes));
        } catch (Overclaim|AttestationRequired $e) {
            return self::refuse($urls['index'], $e->getMessage());
        }

        foreach ($records as $record) {
            $statements->log()->append($record);
        }

        $message = 'Statement saved.';
        $orphans = $profiles->orphanedBy($statement, $profile);

        if ($profileHandle !== $current->profile && $orphans !== []) {
            $message .= ' '.count($orphans).' '.(count($orphans) === 1 ? 'disclosure' : 'disclosures').' from the previous profile '.(count($orphans) === 1 ? 'is' : 'are').' kept and not shown under this one: '.implode(', ', $orphans).'.';
        }

        return redirect()->to($urls['index'])->with('success', $message);
    }

    public static function actor(): string
    {
        $user = User::current();

        if ($user === null) {
            return 'unknown';
        }

        $name = (string) $user->name();
        $email = (string) $user->email();

        return $name !== '' ? ($email !== '' ? "{$name} <{$email}>" : $name) : ($email !== '' ? $email : (string) $user->id());
    }

    private static function refuse(string $to, string $message)
    {
        return redirect()->to($to)->withInput()->with('error', $message);
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
