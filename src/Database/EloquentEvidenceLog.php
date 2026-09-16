<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Database;

use Bpmore\A11yState\Evidence\EvidenceLog;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Evidence\Ordering;
use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Evidence\RecordExists;
use Bpmore\A11yState\Evidence\UnknownPredecessor;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use RuntimeException;

/**
 * The log in a database table, for a site that already runs one and wants
 * the evidence where its backups are.
 *
 * The same contract as the YAML driver, enforced the same way: `append` is
 * the only write, it is an INSERT and never an UPDATE, and the id is the
 * primary key so the database itself refuses a second record with the
 * same id. There is no method here that updates or deletes a row, and
 * the query builder is not exposed. Outside this class, a person with
 * database access can of course do anything; that is true of the YAML
 * files too, and the answer in both cases is the site's backups and its
 * access controls, not this addon.
 *
 * Takes a connection, not the container, so it can be built and tested
 * with nothing but illuminate/database.
 */
final class EloquentEvidenceLog implements EvidenceLog
{
    public const TABLE = 'a11y_state_evidence';

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly string $table = self::TABLE,
    ) {}

    public function append(Record $record): void
    {
        $this->assertInstalled();

        if ($this->exists($record->id)) {
            throw new RecordExists($record->id);
        }

        if ($record->supersedes !== null && ! $this->exists($record->supersedes)) {
            throw new UnknownPredecessor($record->id, $record->supersedes);
        }

        try {
            $this->connection->table($this->table)->insert([
                'id' => $record->id,
                'at' => $record->at,
                'actor' => $record->actor,
                'action' => $record->action,
                'criterion' => $record->criterion,
                'subject' => $record->subject,
                'notes' => $record->notes,
                'supersedes' => $record->supersedes,
                'meta' => json_encode($record->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'created_at' => $record->at,
            ]);
        } catch (QueryException $e) {
            // Two writers racing for one id: the check above passed for
            // both, and the primary key stopped the second. Reported as
            // the same refusal the check gives, not as a database error.
            if ($this->exists($record->id)) {
                throw new RecordExists($record->id);
            }

            throw $e;
        }
    }

    public function find(string $id): ?Record
    {
        $this->assertInstalled();

        $row = $this->connection->table($this->table)->where('id', $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function all(): array
    {
        $this->assertInstalled();

        $records = [];

        foreach ($this->connection->table($this->table)->orderBy('at')->orderBy('id')->get() as $row) {
            $records[] = $this->hydrate($row);
        }

        return Ordering::chronological($records);
    }

    public function filter(Filter $filter): array
    {
        return Ordering::apply($this->all(), $filter);
    }

    public function count(): int
    {
        $this->assertInstalled();

        return $this->connection->table($this->table)->count();
    }

    public function isInstalled(): bool
    {
        try {
            return $this->connection->getSchemaBuilder()->hasTable($this->table);
        } catch (\Throwable) {
            // A SQLite file that does not exist yet throws rather than
            // answering "no tables", and for this question those are the
            // same answer.
            return false;
        }
    }

    private function exists(string $id): bool
    {
        return $this->connection->table($this->table)->where('id', $id)->exists();
    }

    private function assertInstalled(): void
    {
        if (! $this->isInstalled()) {
            throw new RuntimeException("The evidence table [{$this->table}] does not exist on this connection. Run `php please a11y:state:install`, or set storage.evidence_driver back to yaml.");
        }
    }

    private function hydrate(object $row): Record
    {
        $meta = is_string($row->meta) ? json_decode($row->meta, true) : null;

        return new Record(
            id: (string) $row->id,
            at: (string) $row->at,
            actor: (string) $row->actor,
            action: (string) $row->action,
            criterion: $row->criterion === null ? null : (string) $row->criterion,
            subject: $row->subject === null ? null : (string) $row->subject,
            notes: (string) ($row->notes ?? ''),
            supersedes: $row->supersedes === null ? null : (string) $row->supersedes,
            meta: is_array($meta) ? $meta : [],
        );
    }
}
