<?php

declare(strict_types=1);

namespace Bpmore\Grange;

use Bpmore\Grange\Evidence\Actions;
use Bpmore\Grange\Evidence\EvidenceLog;
use Bpmore\Grange\Evidence\Record;
use Bpmore\Grange\Evidence\RecordExists;
use Bpmore\Grange\Integrations\FindingSource;
use Bpmore\Grange\Statement\Attestation;
use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Dates;
use Bpmore\Grange\Statement\Document;
use Bpmore\Grange\Statement\Generator;
use Bpmore\Grange\Statement\IncompleteStatement;
use Bpmore\Grange\Statement\KnownException;
use Bpmore\Grange\Statement\Overclaim;
use Bpmore\Grange\Statement\Problem;
use Bpmore\Grange\Statement\Profiles;
use Bpmore\Grange\Statement\Rules;
use Bpmore\Grange\Statement\Statement;
use Bpmore\Grange\Statement\Validator;
use Bpmore\Grange\Storage\StatementRepository;
use Statamic\Facades\Site;

/**
 * The addon's one front door: a site's statement, what is wrong with it,
 * the document it generates, and the two writes that carry rules with
 * them. Controllers and the command call this and nothing below it, so the
 * rules are applied the same way from every surface.
 *
 * Every write to a statement also writes to the evidence log, so the log
 * says when the statement changed and who changed it, alongside the
 * statement file's own last-saved stamp.
 */
final class Statements
{
    public function __construct(
        private readonly StatementRepository $repository,
        private readonly EvidenceLog $log,
        private readonly Profiles $profiles,
        private readonly Rules $rules,
        private readonly Validator $validator,
        private readonly Generator $generator,
        private readonly FindingSource $findings,
        private readonly string $defaultProfile,
        private readonly ?int $responseDays,
    ) {}

    public function profiles(): Profiles
    {
        return $this->profiles;
    }

    public function log(): EvidenceLog
    {
        return $this->log;
    }

    public function findings(): FindingSource
    {
        return $this->findings;
    }

    /** The site's statement, or a blank one under the default profile if none is saved. */
    public function for(string $site): Statement
    {
        return $this->repository->find($site) ?? Statement::blank($site, $this->defaultProfile);
    }

    public function exists(string $site): bool
    {
        return $this->repository->find($site) !== null;
    }

    /**
     * @return list<KnownException>
     */
    public function externalFindings(string $site): array
    {
        return $this->findings->openFindings($site);
    }

    /**
     * @return list<Problem>
     */
    public function problems(Statement $statement): array
    {
        return $this->validator->check($statement, Dates::now(), $this->externalFindings($statement->site));
    }

    /**
     * The open exceptions that stand in the way of "fully conformant" right
     * now, from the statement and from the integrations together.
     *
     * @return list<KnownException>
     */
    public function blocking(Statement $statement): array
    {
        return $this->validator->blockingExceptions($statement, $this->externalFindings($statement->site));
    }

    /**
     * @throws IncompleteStatement
     */
    public function document(Statement $statement): Document
    {
        return $this->generator->generate(
            $statement,
            Dates::now(),
            $this->externalFindings($statement->site),
            Site::get($statement->site)?->name(),
            $this->responseDays,
        );
    }

    /** The document if the statement is complete, else null. For the public page, which must not throw. */
    public function documentOrNull(Statement $statement): ?Document
    {
        try {
            return $this->document($statement);
        } catch (IncompleteStatement) {
            return null;
        }
    }

    /**
     * Save an edit. Refuses to move the status to "fully conformant" here:
     * that is the attestation step's job, and the refusal says so. A
     * statement already fully conformant keeps its attestation only while
     * its status stays; any other status clears it, because an attestation
     * is of a claim, not of a statement.
     *
     * @throws Overclaim when the status would be fully conformant over open exceptions
     * @throws \Bpmore\Grange\Statement\AttestationRequired when the status would be fully conformant without the attestation step
     */
    public function save(Statement $statement, string $actor, string $note = 'Statement saved'): Statement
    {
        $previous = $this->repository->find($statement->site);

        if ($statement->status === ConformanceStatus::FullyConformant) {
            $blocking = $this->blocking($statement);

            if ($blocking !== []) {
                throw new Overclaim($blocking, $this->rules->overclaimSeverity);
            }

            // Keep the attestation only if nothing that was attested to
            // has changed: same status as before and the same exceptions.
            $unchanged = $previous !== null
                && $previous->status === ConformanceStatus::FullyConformant
                && $previous->attestation !== null
                && $this->exceptionsMatch($previous, $statement);

            $statement = $statement->withAttestation($unchanged ? $previous->attestation : null);
        } else {
            $statement = $statement->withAttestation(null);
        }

        $now = Dates::now();
        $statement = $statement->withUpdated(Dates::moment($now), $actor);

        // The guard in the repository is the one that throws
        // AttestationRequired; the save is attempted first so a refused
        // statement leaves no evidence record claiming it was saved.
        $this->repository->save($statement);

        $this->log->append(new Record(
            id: Record::newId($now),
            at: Dates::moment($now),
            actor: $actor,
            action: Actions::STATEMENT_UPDATED,
            notes: $note,
            meta: ['site' => $statement->site, 'status' => $statement->status->value, 'profile' => $statement->profile],
        ));

        return $statement;
    }

    /**
     * The separate step that makes a statement fully conformant: a named
     * person, a date, and the basis, written to the evidence log first and
     * then referenced from the statement.
     *
     * @throws Overclaim
     * @throws IncompleteStatement when anything other than the attestation is missing
     */
    public function attest(string $site, string $name, string $date, string $basis, string $actor): Statement
    {
        $statement = $this->for($site)->withStatus(ConformanceStatus::FullyConformant);
        $blocking = $this->blocking($statement);

        if ($blocking !== []) {
            throw new Overclaim($blocking, $this->rules->overclaimSeverity);
        }

        $attestation = new Attestation($name, $date, $basis);
        $candidate = $statement->withAttestation($attestation);

        // Everything else has to be in order first. An attestation on a
        // statement missing a required disclosure would be a record of
        // somebody confirming a document that cannot be generated.
        $errors = Validator::errors($this->validator->check($candidate, Dates::now(), $this->externalFindings($site)));

        if ($errors !== []) {
            throw new IncompleteStatement($errors);
        }

        $now = Dates::now();
        $record = new Record(
            id: Record::newId($now),
            at: Dates::moment($now),
            actor: $actor,
            action: Actions::ATTESTED,
            notes: "{$name} attested on {$date} that the site is fully conformant. Basis: {$basis}",
            meta: ['site' => $site, 'attested_by' => $name, 'attested_on' => $date],
        );

        $this->log->append($record);

        $statement = $candidate
            ->withAttestation(new Attestation($name, $date, $basis, $record->id))
            ->withUpdated(Dates::moment($now), $actor);

        $this->repository->save($statement);

        return $statement;
    }

    /**
     * Pull fixes from the integrations into the log. Records already written
     * (same id) are skipped, so this can run as often as it likes.
     *
     * @return int how many were written
     */
    public function importFixes(string $site, \DateTimeImmutable $since): int
    {
        $written = 0;

        foreach ($this->findings->fixesSince($since, $site) as $record) {
            try {
                $this->log->append($record);
                $written++;
            } catch (RecordExists) {
                // Already in the log from an earlier import.
            }
        }

        return $written;
    }

    private function exceptionsMatch(Statement $a, Statement $b): bool
    {
        $ids = fn (Statement $s) => array_map(fn (KnownException $e) => $e->id.':'.$e->status, $s->exceptions);
        $x = $ids($a);
        $y = $ids($b);
        sort($x);
        sort($y);

        return $x === $y;
    }
}
