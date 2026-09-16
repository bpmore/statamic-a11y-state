<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Statement;

use DateTimeImmutable;

/**
 * Every rule about what a statement may say, in one place, answered as a
 * list of problems rather than as an exception, so the editor can show all
 * of them at once and the generator can refuse on any of them.
 *
 * The rules, in the order they are checked:
 *
 * 1. "Fully conformant" with an open exception at or above the configured
 *    severity is an overclaim. The refusal names every such exception.
 * 2. "Fully conformant" without an attestation is a claim nobody made.
 * 3. A required disclosure that is empty is named, one problem each.
 * 4. A claim of partial or non-conformance under a profile that requires
 *    the exceptions to be listed, with none listed, is incoherent: the
 *    statement says "due to the non-compliances listed below" over nothing.
 * 5. An assessment older than the staleness window is a warning, never an
 *    error.
 *
 * Exceptions fed by an integration count the same as ones entered by hand.
 * The addon does not know which of the two a regulator would believe more.
 */
final class Validator
{
    public function __construct(
        private readonly Profiles $profiles,
        private readonly Rules $rules,
    ) {}

    /**
     * @param  list<KnownException>  $external  open findings from integrations, if any
     * @return list<Problem>
     */
    public function check(Statement $statement, DateTimeImmutable $now, array $external = []): array
    {
        if (! $this->profiles->has($statement->profile)) {
            return [Problem::error(Problem::UNKNOWN_PROFILE, "The statement is written under a profile that is not configured: [{$statement->profile}].", 'profile')];
        }

        $profile = $this->profiles->get($statement->profile);
        $problems = [];

        $blocking = $this->blockingExceptions($statement, $external);

        if ($statement->status === ConformanceStatus::FullyConformant && $blocking !== []) {
            $problems[] = Problem::error(
                Problem::OVERCLAIM,
                (new Overclaim($blocking, $this->rules->overclaimSeverity))->getMessage(),
                'conformance_status',
                ['exceptions' => array_map(fn (KnownException $e) => $e->toArray(), $blocking)],
            );
        }

        if ($statement->status === ConformanceStatus::FullyConformant && $statement->attestation === null) {
            $problems[] = Problem::error(
                Problem::ATTESTATION_MISSING,
                (new AttestationRequired)->getMessage(),
                'attestation',
            );
        }

        foreach ($profile->requiredFor($statement->status) as $disclosure) {
            if ($disclosure->key === 'exceptions') {
                if ($statement->status->isClaim() && $statement->status !== ConformanceStatus::FullyConformant && $statement->exceptions === [] && $external === []) {
                    $problems[] = Problem::error(
                        Problem::MISSING_EXCEPTIONS,
                        "The {$profile->name} profile requires the known exceptions to be listed when the statement claims {$statement->status->label()}, and none are.",
                        'exceptions',
                    );
                }

                continue;
            }

            if ($disclosure->key === 'conformance_status') {
                // Always present on a statement; "not assessed" is an answer.
                continue;
            }

            if (! $statement->has($disclosure->key)) {
                $problems[] = Problem::error(
                    Problem::MISSING_DISCLOSURE,
                    "The {$profile->name} profile requires \"{$disclosure->label}\" and the statement does not have it.",
                    $disclosure->key,
                    ['source' => $disclosure->source],
                );
            }
        }

        $stale = Staleness::check($statement, $this->rules->staleAfterDays, $now);

        if ($stale !== null) {
            $problems[] = $stale;
        }

        return $problems;
    }

    /**
     * @param  list<Problem>  $problems
     * @return list<Problem>
     */
    public static function errors(array $problems): array
    {
        return array_values(array_filter($problems, fn (Problem $p) => $p->isError()));
    }

    /**
     * The open exceptions, manual and external together, at or above the
     * severity that refuses full conformance.
     *
     * @param  list<KnownException>  $external
     * @return list<KnownException>
     */
    public function blockingExceptions(Statement $statement, array $external = []): array
    {
        $all = array_merge($statement->openExceptions(), array_values(array_filter($external, fn (KnownException $e) => $e->isOpen())));

        return array_values(array_filter($all, fn (KnownException $e) => $e->severity->atLeast($this->rules->overclaimSeverity)));
    }
}
