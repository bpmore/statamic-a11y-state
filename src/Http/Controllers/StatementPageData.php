<?php

declare(strict_types=1);

namespace Bpmore\Grange\Http\Controllers;

use Bpmore\Grange\ServiceProvider;
use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Problem;
use Bpmore\Grange\Statement\Severity;
use Bpmore\Grange\Statement\Staleness;
use Bpmore\Grange\Statement\Statement;
use Bpmore\Grange\Statements;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Facades\User;

/**
 * Everything the statement editor shows, in one place, so the utility
 * view and the controllers that redirect back to it agree.
 */
final class StatementPageData
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Request $request): array
    {
        $statements = app(Statements::class);
        $site = self::site($request);
        $statement = $statements->for($site);
        $profiles = $statements->profiles();

        // The editor can be asked to show another profile's fields before
        // saving, which is how the switch warns before it happens.
        $requested = $request->query('profile');
        $viewing = is_string($requested) && $profiles->has($requested) ? $requested : $statement->profile;
        $profile = $profiles->has($viewing) ? $profiles->get($viewing) : $profiles->all()[0];

        $problems = $statements->problems($statement->withProfile($profile->handle));
        $external = $statements->externalFindings($site);
        $orphans = $profiles->orphanedBy($statement, $profile);

        return [
            'site' => $site,
            'sites' => Site::all()->map(fn ($s) => ['handle' => $s->handle(), 'name' => $s->name()])->values()->all(),
            'statement' => $statement,
            'exists' => $statements->exists($site),
            'profile' => $profile,
            'profiles' => $profiles->all(),
            'switching' => $profile->handle !== $statement->profile,
            'orphans' => $orphans,
            'problems' => $problems,
            'errors' => array_values(array_filter($problems, fn (Problem $p) => $p->isError())),
            'warnings' => array_values(array_filter($problems, fn (Problem $p) => ! $p->isError())),
            'stale' => array_values(array_filter($problems, fn (Problem $p) => $p->code === Problem::STALE))[0] ?? null,
            'ageDays' => Staleness::ageInDays($statement, \Bpmore\Grange\Statement\Dates::now()),
            'blocking' => $statements->blocking($statement),
            'external' => $external,
            'externalSource' => $statements->findings()->name(),
            'statuses' => self::statusOptions($statement),
            'severities' => Severity::cases(),
            'canEdit' => (bool) User::current()?->can('edit accessibility statement'),
            'canAttest' => (bool) User::current()?->can('attest accessibility conformance'),
            'urls' => self::urls($site),
            'staleAfterDays' => (int) config('statamic-grange.stale_after_days', 365),
            'overclaimSeverity' => (string) config('statamic-grange.overclaim_severity', 'minor'),
            'responseDays' => ServiceProvider::responseDays(),
            'publicUrl' => self::publicUrl($site),
            'disclaimer' => \Bpmore\Grange\Statement\Generator::DISCLAIMER,
        ];
    }

    public static function site(Request $request): string
    {
        $site = $request->query('site', $request->input('site'));

        return is_string($site) && Site::get($site) !== null ? $site : (Site::default()?->handle() ?? 'default');
    }

    /**
     * @return array<string, string>
     */
    public static function urls(string $site): array
    {
        $q = '?site='.rawurlencode($site);

        return [
            'index' => cp_route('utilities.index').'/'.ServiceProvider::UTILITY.$q,
            'save' => cp_route('utilities.grange.save'),
            'attest' => cp_route('utilities.grange.attest'),
            'preview' => cp_route('utilities.grange.preview').$q,
            'exportHtml' => cp_route('utilities.grange.export', ['format' => 'html']).$q,
            'exportMarkdown' => cp_route('utilities.grange.export', ['format' => 'md']).$q,
            'evidence' => cp_route('utilities.grange.evidence').$q,
            'evidenceAppend' => cp_route('utilities.grange.evidence.append'),
            'evidenceCsv' => cp_route('utilities.grange.evidence.export', ['format' => 'csv']),
            'evidenceJson' => cp_route('utilities.grange.evidence.export', ['format' => 'json']),
            'feedback' => cp_route('utilities.grange.feedback').$q,
            'feedbackAcknowledge' => cp_route('utilities.grange.feedback.acknowledge'),
        ];
    }

    /**
     * The statuses the editor offers. "Fully conformant" is not one of
     * them unless the statement already is: getting there is the
     * attestation step, on purpose.
     *
     * @return list<ConformanceStatus>
     */
    private static function statusOptions(Statement $statement): array
    {
        $options = [ConformanceStatus::NotAssessed, ConformanceStatus::NonConformant, ConformanceStatus::PartiallyConformant];

        if ($statement->status === ConformanceStatus::FullyConformant) {
            $options[] = ConformanceStatus::FullyConformant;
        }

        return $options;
    }

    private static function publicUrl(string $site): ?string
    {
        $route = config('statamic-grange.route');

        if (! is_string($route) || trim($route, '/ ') === '') {
            return null;
        }

        $s = Site::get($site);

        return $s === null ? null : rtrim((string) $s->absoluteUrl(), '/').'/'.trim($route, '/');
    }
}
