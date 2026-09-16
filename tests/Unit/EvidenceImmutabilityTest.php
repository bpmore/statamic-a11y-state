<?php

declare(strict_types=1);

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\EvidenceLog;
use Bpmore\A11yState\Evidence\Export;
use Bpmore\A11yState\Evidence\Filter;
use Bpmore\A11yState\Evidence\InMemoryEvidenceLog;
use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Evidence\RecordExists;
use Bpmore\A11yState\Evidence\UnknownPredecessor;
use Bpmore\A11yState\Evidence\YamlEvidenceLog;

/*
 * Discriminating test 4: evidence immutability. Modifying a written record
 * through any public path fails. Deleting one fails. A superseding record
 * links to its predecessor and both remain readable.
 */

beforeEach(function () {
    $this->dir = tempDir('evidence');
    $this->log = new YamlEvidenceLog($this->dir);
});

afterEach(function () {
    removeDir($this->dir);
});

function record(string $id, string $at = '2026-09-01T10:00:00+00:00', ?string $supersedes = null, string $notes = 'Checked by hand'): Record
{
    return new Record(
        id: $id,
        at: $at,
        actor: 'Ada Example',
        action: Actions::ASSESSED,
        criterion: '1.1.1',
        subject: '/gallery',
        notes: $notes,
        supersedes: $supersedes,
    );
}

it('has no public way to change a record', function () {
    $reflection = new ReflectionClass(Record::class);

    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue("Record::\${$property->getName()} is writable");
    }

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $name = $method->getName();
        expect(preg_match('/^(set|with|update|delete|remove)/i', $name))->toBe(0, "Record::{$name}() is a mutator");
    }

    $record = record('r1');

    try {
        $record->notes = 'changed';
        $this->fail('A readonly property was written.');
    } catch (Error $e) {
        expect($e->getMessage())->toContain('readonly');
    }
});

it('has no update and no delete on the log interface', function () {
    $methods = array_map(fn (ReflectionMethod $m) => $m->getName(), (new ReflectionClass(EvidenceLog::class))->getMethods());

    sort($methods);
    expect($methods)->toBe(['all', 'append', 'count', 'filter', 'find']);

    foreach ([YamlEvidenceLog::class, InMemoryEvidenceLog::class] as $driver) {
        foreach ((new ReflectionClass($driver))->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            expect(preg_match('/^(update|delete|remove|replace|edit|truncate|clear|forget|purge)/i', $m->getName()))->toBe(0, "{$driver}::{$m->getName()}() exists");
        }
    }
});

it('refuses to write a record whose id is already written, in both drivers', function () {
    foreach ([$this->log, new InMemoryEvidenceLog] as $log) {
        $log->append(record('r1'));

        try {
            $log->append(record('r1', notes: 'a different record with the same id'));
            $this->fail(get_class($log).' overwrote a record.');
        } catch (RecordExists $e) {
            expect($e->id)->toBe('r1');
        }

        expect($log->find('r1')->notes)->toBe('Checked by hand');
        expect($log->count())->toBe(1);
    }
});

it('writes each record as a read-only file', function () {
    $this->log->append(record('r1'));
    $path = $this->dir.'/r1.yaml';

    expect(file_exists($path))->toBeTrue();
    expect(fileperms($path) & 0222)->toBe(0, 'the record file is writable');
});

it('links a superseding record to its predecessor and keeps both readable', function () {
    foreach ([$this->log, new InMemoryEvidenceLog] as $log) {
        $log->append(record('r1', notes: 'Marked as passing'));
        $log->append(record('r2', '2026-09-02T10:00:00+00:00', 'r1', 'Correction: the gallery images on the second page still have no alt text'));

        $first = $log->find('r1');
        $second = $log->find('r2');

        expect($first)->not->toBeNull();
        expect($first->notes)->toBe('Marked as passing');
        expect($second->supersedes)->toBe('r1');

        $all = $log->all();
        expect(array_map(fn (Record $r) => $r->id, $all))->toBe(['r1', 'r2']);

        // Hiding corrected records is a view, and the export still carries both.
        $visible = $log->filter(new Filter(hideSuperseded: true));
        expect(array_map(fn (Record $r) => $r->id, $visible))->toBe(['r2']);

        $json = json_decode(Export::json($log->all()), true);
        expect($json['count'])->toBe(2);
        expect($json['records'][0]['superseded_by'])->toBe('r2');
        expect($json['records'][1]['supersedes'])->toBe('r1');
    }
});

it('refuses a superseding record whose predecessor is not in the log', function () {
    $this->log->append(record('r2', supersedes: 'never-written'));
})->throws(UnknownPredecessor::class);

it('refuses a record that supersedes itself', function () {
    record('r1', supersedes: 'r1');
})->throws(InvalidArgumentException::class);

it('refuses a record with an unknown action rather than storing a word nobody defined', function () {
    new Record('r1', '2026-09-01T10:00:00+00:00', 'Ada', 'vibes-checked');
})->throws(InvalidArgumentException::class, 'unknown action');

it('refuses an id that could escape the directory', function () {
    new Record('../etc/passwd', '2026-09-01T10:00:00+00:00', 'Ada', Actions::NOTE);
})->throws(InvalidArgumentException::class);

it('refuses a file whose name and id disagree, because somebody edited it by hand', function () {
    $this->log->append(record('r1'));
    $moved = $this->dir.'/r9.yaml';
    copy($this->dir.'/r1.yaml', $moved);

    $this->log->all();
})->throws(RuntimeException::class, 'is not its name');

it('comes back oldest first whatever order the files were written in', function () {
    $this->log->append(record('b', '2026-09-03T10:00:00+00:00'));
    $this->log->append(record('a', '2026-09-01T10:00:00+00:00'));
    $this->log->append(record('c', '2026-09-02T10:00:00+00:00'));

    expect(array_map(fn (Record $r) => $r->id, $this->log->all()))->toBe(['a', 'c', 'b']);
});

it('reads an empty log from a directory that does not exist yet', function () {
    $log = new YamlEvidenceLog($this->dir.'/not-yet');

    expect($log->all())->toBe([]);
    expect($log->count())->toBe(0);
    expect($log->find('anything'))->toBeNull();
});

it('filters by action, criterion, actor, subject and date without dropping anything from the log itself', function () {
    $this->log->append(new Record('n1', '2026-09-01T10:00:00+00:00', 'Ada', Actions::NOTE, null, null, 'first'));
    $this->log->append(new Record('f1', '2026-09-05T10:00:00+00:00', 'Grace', Actions::FIXED, '1.4.3', '/home', 'contrast'));
    $this->log->append(new Record('a1', '2026-09-09T10:00:00+00:00', 'Ada', Actions::ASSESSED, '1.4.3', '/about', 'checked'));

    $ids = fn (array $rs) => array_map(fn (Record $r) => $r->id, $rs);

    expect($ids($this->log->filter(new Filter(action: Actions::FIXED))))->toBe(['f1']);
    expect($ids($this->log->filter(new Filter(criterion: '1.4.3'))))->toBe(['f1', 'a1']);
    expect($ids($this->log->filter(new Filter(actor: 'ada'))))->toBe(['n1', 'a1']);
    expect($ids($this->log->filter(new Filter(subject: '/home'))))->toBe(['f1']);
    expect($ids($this->log->filter(new Filter(from: '2026-09-02', to: '2026-09-08'))))->toBe(['f1']);
    expect($ids($this->log->filter(Filter::fromArray(['from' => 'not a date']))))->toBe(['n1', 'f1', 'a1']);
    expect($this->log->count())->toBe(3);
});
