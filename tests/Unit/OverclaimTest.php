<?php

declare(strict_types=1);

use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Generator;
use Bpmore\Grange\Statement\IncompleteStatement;
use Bpmore\Grange\Statement\Problem;
use Bpmore\Grange\Statement\Validator;

/*
 * Discriminating test 1: refusal to overclaim. With one open exception at
 * or above the configured severity, "fully conformant" is refused and the
 * refusal names the exception.
 */

it('refuses full conformance while an open exception at the configured severity exists, and names it', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([exception('gallery-alt', 'serious')])
        ->withAttestation(attestation());

    $problems = checker('serious')->check($statement, at());
    $overclaims = array_values(array_filter($problems, fn (Problem $p) => $p->code === Problem::OVERCLAIM));

    expect($overclaims)->toHaveCount(1);
    expect($overclaims[0]->isError())->toBeTrue();
    expect($overclaims[0]->message)->toContain('Images in the gallery have no text alternative');
    expect($overclaims[0]->message)->toContain('/gallery');
    expect($overclaims[0]->message)->toContain('1.1.1');
    expect($overclaims[0]->context['exceptions'][0]['id'])->toBe('gallery-alt');
});

it('refuses through the generator too, with the exception in the message', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([exception('gallery-alt', 'critical')])
        ->withAttestation(attestation());

    $generator = new Generator(profiles(), checker('serious'));

    try {
        $generator->generate($statement, at());
        $this->fail('The generator produced a fully conformant statement over an open critical exception.');
    } catch (IncompleteStatement $e) {
        expect($e->getMessage())->toContain('Full conformance is refused');
        expect($e->getMessage())->toContain('Images in the gallery have no text alternative');
        expect($e->fields())->toContain('conformance_status');
    }
});

it('does not refuse when the only open exception is below the configured severity', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([exception('typo', 'minor')])
        ->withAttestation(attestation());

    $errors = Validator::errors(checker('moderate')->check($statement, at()));

    expect(array_map(fn (Problem $p) => $p->code, $errors))->not->toContain(Problem::OVERCLAIM);
});

it('does not refuse for a resolved exception at any severity', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([exception('fixed-one', 'critical', 'resolved')])
        ->withAttestation(attestation());

    $errors = Validator::errors(checker('minor')->check($statement, at()));

    expect(array_map(fn (Problem $p) => $p->code, $errors))->not->toContain(Problem::OVERCLAIM);
});

it('counts an open finding fed by an integration exactly like one entered by hand', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([])
        ->withAttestation(attestation());

    $external = [exception('report-42', 'serious')];
    $problems = checker('serious')->check($statement, at(), $external);
    $codes = array_map(fn (Problem $p) => $p->code, $problems);

    expect($codes)->toContain(Problem::OVERCLAIM);
});

it('the default threshold refuses every open exception, because fully conformant means without any exceptions', function () {
    $statement = completeStatement('w3c', ConformanceStatus::FullyConformant)
        ->withExceptions([exception('typo', 'minor')])
        ->withAttestation(attestation());

    $config = grangeConfig();
    expect($config['overclaim_severity'])->toBe('minor');

    $problems = checker($config['overclaim_severity'])->check($statement, at());
    expect(array_map(fn (Problem $p) => $p->code, $problems))->toContain(Problem::OVERCLAIM);
});

it('does not touch a partially conformant statement with open exceptions', function () {
    $statement = completeStatement('w3c', ConformanceStatus::PartiallyConformant)
        ->withExceptions([exception('gallery-alt', 'critical')]);

    expect(Validator::errors(checker('minor')->check($statement, at())))->toBe([]);
});
