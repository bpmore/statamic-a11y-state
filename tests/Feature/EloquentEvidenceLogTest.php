<?php

declare(strict_types=1);

use Bpmore\A11yState\Database\EloquentEvidenceLog;
use Bpmore\A11yState\Database\EvidenceDatabase;
use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\EvidenceLog;
use Bpmore\A11yState\Evidence\Export;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Evidence\RecordExists;
use Bpmore\A11yState\Evidence\UnknownPredecessor;
use Bpmore\A11yState\Evidence\YamlEvidenceLog;
use Bpmore\A11yState\Statements;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
 * The Eloquent evidence driver, held to the same contract as the YAML one
 * (discriminating test 4, on a database): no write over an existing id,
 * no unknown predecessor, no update, no delete, and the same export.
 */

beforeEach(function () {
    config()->set('database.connections.a11y_state_testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    config()->set('statamic-a11y-state.storage.evidence_driver', 'eloquent');
    config()->set('statamic-a11y-state.storage.evidence_connection', 'a11y_state_testing');
});

function dbRecord(string $id, string $at = '2026-09-01T10:00:00+00:00', ?string $supersedes = null, string $notes = 'Checked by hand'): Record
{
    return new Record($id, $at, 'Ada Example', Actions::ASSESSED, '1.1.1', '/gallery', $notes, $supersedes, ['finding' => 42]);
}

it('refuses to read or write before the table exists, and says what to run', function () {
    $log = EvidenceDatabase::log();

    expect($log->isInstalled())->toBeFalse();

    try {
        $log->append(dbRecord('r1'));
        $this->fail('wrote to a table that does not exist');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('a11y:state:install');
    }
});

it('creates the table through the install command, on the configured connection, and is idempotent', function () {
    $this->artisan('statamic:a11y:state:install')
        ->expectsOutputToContain('[a11y_state_evidence] is in place on [a11y_state_testing]')
        ->assertExitCode(0);

    expect(EvidenceDatabase::log()->isInstalled())->toBeTrue();
    expect(DB::connection('a11y_state_testing')->table('migrations')->count())->toBe(1);

    Artisan::call('statamic:a11y:state:install');
    expect(DB::connection('a11y_state_testing')->table('migrations')->count())->toBe(1);
});

it('is the log the addon resolves when the driver is eloquent, and the YAML one otherwise', function () {
    expect(app(EvidenceLog::class))->toBeInstanceOf(EloquentEvidenceLog::class);

    config()->set('statamic-a11y-state.storage.evidence_driver', 'yaml');
    expect(app(EvidenceLog::class))->toBeInstanceOf(YamlEvidenceLog::class);

    config()->set('statamic-a11y-state.storage.evidence_driver', 'something-else');
    expect(app(EvidenceLog::class))->toBeInstanceOf(YamlEvidenceLog::class);
});

it('refuses to write a record whose id is already written', function () {
    Artisan::call('statamic:a11y:state:install');
    $log = EvidenceDatabase::log();
    $log->append(dbRecord('r1'));

    try {
        $log->append(dbRecord('r1', notes: 'a different record with the same id'));
        $this->fail('overwrote a record');
    } catch (RecordExists $e) {
        expect($e->id)->toBe('r1');
    }

    expect($log->find('r1')->notes)->toBe('Checked by hand');
    expect($log->count())->toBe(1);
});

it('is refused by the primary key too, even when the id check is bypassed', function () {
    Artisan::call('statamic:a11y:state:install');
    EvidenceDatabase::log()->append(dbRecord('r1'));

    $insert = fn () => DB::connection('a11y_state_testing')->table(EloquentEvidenceLog::TABLE)->insert([
        'id' => 'r1', 'at' => '2026-09-02T10:00:00+00:00', 'actor' => 'x', 'action' => 'note', 'notes' => 'x', 'meta' => '[]', 'created_at' => 'x',
    ]);

    expect(fn () => $insert())->toThrow(\Illuminate\Database\QueryException::class);
    expect(EvidenceDatabase::log()->find('r1')->notes)->toBe('Checked by hand');
});

it('links a superseding record to its predecessor, keeps both, and refuses an unknown predecessor', function () {
    Artisan::call('statamic:a11y:state:install');
    $log = EvidenceDatabase::log();
    $log->append(dbRecord('r1', notes: 'Marked as passing'));
    $log->append(dbRecord('r2', '2026-09-02T10:00:00+00:00', 'r1', 'Correction'));

    expect($log->find('r1')->notes)->toBe('Marked as passing');
    expect($log->find('r2')->supersedes)->toBe('r1');
    expect(array_map(fn (Record $r) => $r->id, $log->all()))->toBe(['r1', 'r2']);
    expect(array_map(fn (Record $r) => $r->id, $log->filter(new Filter(hideSuperseded: true))))->toBe(['r2']);

    $json = json_decode(Export::json($log->all()), true);
    expect($json['records'][0]['superseded_by'])->toBe('r2');
    expect($json['records'][0]['meta'])->toBe(['finding' => 42]);

    expect(fn () => $log->append(dbRecord('r3', supersedes: 'never')))->toThrow(UnknownPredecessor::class);
});

it('has no update and no delete, and exposes no query builder', function () {
    foreach ((new ReflectionClass(EloquentEvidenceLog::class))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        expect(preg_match('/^(update|delete|remove|replace|edit|truncate|clear|forget|purge|query|table|builder)/i', $m->getName()))->toBe(0, "EloquentEvidenceLog::{$m->getName()}() exists");
    }

    $source = (string) file_get_contents((new ReflectionClass(EloquentEvidenceLog::class))->getFileName());
    expect(preg_match('/->(update|delete|truncate|upsert|updateOrInsert)\(/', $source))->toBe(0, 'the driver calls a write other than insert');
});

it('comes back oldest first whatever order the rows were written in', function () {
    Artisan::call('statamic:a11y:state:install');
    $log = EvidenceDatabase::log();
    $log->append(dbRecord('b', '2026-09-03T10:00:00+00:00'));
    $log->append(dbRecord('a', '2026-09-01T10:00:00+00:00'));
    $log->append(dbRecord('c', '2026-09-02T10:00:00+00:00'));

    expect(array_map(fn (Record $r) => $r->id, $log->all()))->toBe(['a', 'c', 'b']);
});

it('carries the whole addon: a save, an attestation and the command all write to the table', function () {
    Artisan::call('statamic:a11y:state:install');
    saveStatement();
    app(Statements::class)->save(app(Statements::class)->for('default')->withExceptions([]), 'fixture');
    app(Statements::class)->attest('default', 'Grace', '2026-09-14', 'Audit', 'fixture');

    $actions = array_map(fn (Record $r) => $r->action, EvidenceDatabase::log()->all());
    expect($actions)->toContain(Actions::STATEMENT_UPDATED, Actions::ATTESTED);
    expect(glob($this->storage.'/evidence/*') ?: [])->toBe([]);

    Artisan::call('statamic:a11y:state:check', ['--json' => true]);
    $json = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
    expect($json['sites'][0]['status'])->toBe('fully-conformant');
});
