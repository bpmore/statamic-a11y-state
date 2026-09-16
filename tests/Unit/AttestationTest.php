<?php

declare(strict_types=1);

use Bpmore\A11yState\Statement\Attestation;
use Bpmore\A11yState\Statement\AttestationRequired;
use Bpmore\A11yState\Statement\ConformanceStatus;
use Bpmore\A11yState\Statement\Problem;
use Bpmore\A11yState\Storage\Guard;
use Bpmore\A11yState\Storage\YamlStatementRepository;

/*
 * Discriminating test 2: attestation required. "Fully conformant" cannot be
 * persisted without a recorded attestation containing a name and a date.
 */

beforeEach(function () {
    $this->dir = tempDir('statements');
});

afterEach(function () {
    removeDir($this->dir);
});

it('refuses to persist a fully conformant statement with no attestation', function () {
    $repository = new YamlStatementRepository($this->dir);
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)->withExceptions([]);

    expect($statement->attestation)->toBeNull();

    try {
        $repository->save($statement);
        $this->fail('A fully conformant statement was saved with no attestation.');
    } catch (AttestationRequired $e) {
        expect($e->getMessage())->toContain('attestation');
    }

    expect(glob($this->dir.'/*'))->toBe([]);
    expect($repository->find('default'))->toBeNull();
});

it('persists the same statement once a named, dated attestation is on it', function () {
    $repository = new YamlStatementRepository($this->dir);
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([])
        ->withAttestation(attestation());

    $repository->save($statement);

    $found = $repository->find('default');
    expect($found)->not->toBeNull();
    expect($found->status)->toBe(ConformanceStatus::FullyConformant);
    expect($found->attestation->name)->toBe('Ada Example');
    expect($found->attestation->date)->toBe('2026-09-10');
});

it('cannot make an attestation without a name', function () {
    new Attestation('', '2026-09-10', 'A basis');
})->throws(InvalidArgumentException::class, 'name');

it('cannot make an attestation without a date', function () {
    new Attestation('Ada Example', '', 'A basis');
})->throws(InvalidArgumentException::class, 'date');

it('cannot make an attestation with a date that is not one', function () {
    new Attestation('Ada Example', 'last Tuesday-ish', 'A basis');
})->throws(InvalidArgumentException::class, 'date');

it('cannot make an attestation without a basis', function () {
    new Attestation('Ada Example', '2026-09-10', '   ');
})->throws(InvalidArgumentException::class, 'basis');

it('the storage guard is what every driver calls, and it refuses the same way', function () {
    Guard::assertPersistable(completeStatement('w3c', ConformanceStatus::FullyConformant)->withExceptions([]));
})->throws(AttestationRequired::class);

it('the validator names the missing attestation as an error', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)->withExceptions([]);
    $problems = checker()->check($statement, at());

    $missing = array_values(array_filter($problems, fn (Problem $p) => $p->code === Problem::ATTESTATION_MISSING));
    expect($missing)->toHaveCount(1);
    expect($missing[0]->isError())->toBeTrue();
});

it('a statement read back from YAML with an attestation missing its name refuses to load rather than guessing', function () {
    file_put_contents($this->dir.'/default.yaml', "profile: w3c\nstatus: fully-conformant\ndisclosures: {}\nexceptions: []\nattestation:\n  name: ''\n  date: '2026-09-10'\n  basis: 'x'\n");

    (new YamlStatementRepository($this->dir))->find('default');
})->throws(InvalidArgumentException::class, 'name');

it('the guard does not touch any other status', function () {
    foreach ([ConformanceStatus::NotAssessed, ConformanceStatus::NonConformant, ConformanceStatus::PartiallyConformant] as $status) {
        Guard::assertPersistable(completeStatement('w3c', $status));
    }

    expect(true)->toBeTrue();
});
