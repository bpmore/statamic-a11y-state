<?php

declare(strict_types=1);

namespace Bpmore\Grange\Integrations;

use Bpmore\Grange\Evidence\Actions;
use Bpmore\Grange\Evidence\Record;
use Bpmore\Grange\Statement\Dates;
use Bpmore\Grange\Statement\KnownException;
use Bpmore\Grange\Statement\Severity;
use DateTimeImmutable;

/**
 * Accessibility Report's open issues as known exceptions, and its fixes as
 * evidence. Optional: when the report is not installed, or is installed
 * and has never run, this source says it is unavailable and is skipped.
 *
 * Reads the report's own tables through its own models, resolved by name
 * so that this file compiles with the report absent. Nothing here writes
 * to the report. The one definition of "open" is the report's
 * (`IssueState::openNow`), used rather than copied, so the two addons
 * cannot disagree about which problems are outstanding.
 */
final class A11yReportSource implements FindingSource
{
    public const NAME = 'a11y-report';

    private const STATE = '\Bpmore\A11yReport\Models\IssueState';

    private const ISSUE = '\Bpmore\A11yReport\Models\Issue';

    private const DATABASE = '\Bpmore\A11yReport\Storage\ReportDatabase';

    public function name(): string
    {
        return self::NAME;
    }

    public function isAvailable(): bool
    {
        if (! class_exists(self::STATE) || ! class_exists(self::DATABASE)) {
            return false;
        }

        try {
            return (bool) app(self::DATABASE)->isInstalled();
        } catch (\Throwable) {
            return false;
        }
    }

    public function openFindings(?string $site): array
    {
        $state = self::STATE;
        $query = $state::query()
            ->join('a11y_issues', function ($join) {
                $join->on('a11y_issues.fingerprint', '=', 'a11y_issue_states.fingerprint')
                    ->on('a11y_issues.scan_id', '=', 'a11y_issue_states.last_scan_id');
            })
            ->select(['a11y_issue_states.*', 'a11y_issues.label', 'a11y_issues.message', 'a11y_issues.wcag_criteria']);

        $state::openNow($query, 'a11y_issue_states.status');

        if ($site !== null) {
            $query->where('a11y_issue_states.site', $site);
        }

        $findings = [];

        foreach ($query->orderBy('a11y_issue_states.first_seen_at')->get() as $row) {
            $criteria = is_string($row->wcag_criteria) ? (array) json_decode($row->wcag_criteria, true) : (array) $row->wcag_criteria;
            $criterion = isset($criteria[0]) && is_string($criteria[0]) ? $criteria[0] : null;

            $findings[] = new KnownException(
                id: self::NAME.':'.$row->fingerprint,
                description: trim((string) $row->label.': '.(string) $row->message),
                severity: Severity::tryFromLoose($row->impact) ?? Severity::Moderate,
                status: KnownException::OPEN,
                criterion: $criterion,
                page: (string) $row->path,
                reason: null,
                remediationDate: null,
                source: self::NAME,
            );
        }

        return $findings;
    }

    public function fixesSince(DateTimeImmutable $since, ?string $site): array
    {
        $state = self::STATE;
        $issue = self::ISSUE;

        // No join to the last scan here: a fixed problem has no issue row
        // in the scan that found it gone, which is what "fixed" means. The
        // label and the criterion come from the newest row that ever
        // carried the fingerprint.
        $query = $state::query()
            ->where('status', $state::FIXED)
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $since->format('Y-m-d H:i:s'));

        if ($site !== null) {
            $query->where('site', $site);
        }

        $records = [];

        foreach ($query->orderBy('resolved_at')->get() as $row) {
            $last = $issue::query()->where('fingerprint', $row->fingerprint)->orderByDesc('id')->first();
            $label = $last !== null ? (string) $last->label : (string) $row->rule_id;
            $criteria = $last === null ? [] : (is_string($last->wcag_criteria) ? (array) json_decode($last->wcag_criteria, true) : (array) $last->wcag_criteria);
            $resolved = $row->resolved_at instanceof \DateTimeInterface
                ? DateTimeImmutable::createFromInterface($row->resolved_at)
                : Dates::parse((string) $row->resolved_at);

            if ($resolved === null) {
                continue;
            }

            // The id is the fingerprint, so importing twice writes nothing twice.
            $records[] = new Record(
                id: self::NAME.'-fixed-'.$row->fingerprint,
                at: Dates::moment($resolved),
                actor: is_string($row->updated_by) && $row->updated_by !== '' ? $row->updated_by : 'Accessibility Report scan',
                action: Actions::FIXED,
                criterion: isset($criteria[0]) && is_string($criteria[0]) ? $criteria[0] : null,
                subject: (string) $row->path,
                notes: 'No longer found by the scan: '.$label,
                meta: ['source' => self::NAME, 'fingerprint' => $row->fingerprint, 'site' => $row->site],
            );
        }

        return $records;
    }
}
