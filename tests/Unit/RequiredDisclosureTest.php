<?php

declare(strict_types=1);

use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Disclosure;
use Bpmore\Grange\Statement\Generator;
use Bpmore\Grange\Statement\IncompleteStatement;
use Bpmore\Grange\Statement\Problem;
use Bpmore\Grange\Statement\Validator;

/*
 * Discriminating test 3: required disclosure enforcement. Each profile,
 * with one required disclosure removed, fails generation and names the
 * missing disclosure. Parameterized over every profile and every required
 * disclosure in it, read from the shipped config file, so a profile added
 * later is covered without a test being written.
 */

$cases = [];

foreach (grangeConfig()['profiles'] as $handle => $spec) {
    foreach ($spec['disclosures'] as $key => $d) {
        if (($d['required'] ?? false) === true && ! in_array($key, Disclosure::STRUCTURED, true)) {
            $cases["{$handle}: {$key}"] = [$handle, $key];
        }
    }
}

expect($cases)->not->toBe([]);

it('fails generation when a required disclosure is missing, and names it', function (string $profile, string $key) {
    $complete = completeStatement($profile);
    $label = profiles()->get($profile)->disclosure($key)->label;

    // Sanity: the complete statement generates. A fixture that never
    // generated would make every removal "fail" for the wrong reason.
    $generator = new Generator(profiles(), checker());
    $generator->generate($complete, at());

    $disclosures = $complete->disclosures;
    unset($disclosures[$key]);
    $missing = new \Bpmore\Grange\Statement\Statement($complete->site, $complete->profile, $complete->status, $disclosures, $complete->exceptions);

    try {
        $generator->generate($missing, at());
        $this->fail("Profile [{$profile}] generated without required disclosure [{$key}].");
    } catch (IncompleteStatement $e) {
        expect($e->fields())->toBe([$key]);
        expect($e->getMessage())->toContain($label);
        expect($e->problems[0]->code)->toBe(Problem::MISSING_DISCLOSURE);
        expect($e->problems[0]->context['source'])->not->toBe('');
    }
})->with($cases);

it('treats a disclosure that is only whitespace as missing', function () {
    $statement = completeStatement('w3c')->withDisclosures(['contact' => "  \n "]);
    $errors = Validator::errors(checker()->check($statement, at()));

    expect(array_map(fn (Problem $p) => $p->field, $errors))->toBe(['contact']);
});

it('names every missing disclosure at once rather than the first', function () {
    $statement = completeStatement('en301549')->withDisclosures(['contact' => '', 'scope' => '', 'enforcement_body' => null]);
    $errors = Validator::errors(checker()->check($statement, at()));

    expect(array_map(fn (Problem $p) => $p->field, $errors))->toBe(['scope', 'contact', 'enforcement_body']);
});

it('requires the exceptions list for a partial claim under the EU profile, and not for a full or unassessed one', function () {
    $partial = completeStatement('en301549', ConformanceStatus::PartiallyConformant)->withExceptions([]);
    $codes = array_map(fn (Problem $p) => $p->code, Validator::errors(checker()->check($partial, at())));
    expect($codes)->toBe([Problem::MISSING_EXCEPTIONS]);

    $unassessed = completeStatement('en301549', ConformanceStatus::NotAssessed)->withExceptions([]);
    expect(Validator::errors(checker()->check($unassessed, at())))->toBe([]);

    $full = completeStatement('en301549', ConformanceStatus::FullyConformant)->withExceptions([])->withAttestation(attestation());
    expect(Validator::errors(checker()->check($full, at())))->toBe([]);
});

it('does not require the exceptions list under the W3C profile, which marks them optional', function () {
    $partial = completeStatement('w3c', ConformanceStatus::PartiallyConformant)->withExceptions([]);

    expect(Validator::errors(checker()->check($partial, at())))->toBe([]);
});

it('refuses a statement under a profile that is not configured', function () {
    $statement = completeStatement('w3c')->withProfile('made_up');
    $errors = Validator::errors(checker()->check($statement, at()));

    expect($errors)->toHaveCount(1);
    expect($errors[0]->code)->toBe(Problem::UNKNOWN_PROFILE);
});

it('every disclosure in the shipped config names a source', function () {
    foreach (profiles()->all() as $profile) {
        expect($profile->disclosures)->not->toBe([]);

        foreach ($profile->disclosures as $d) {
            expect(strlen($d->source))->toBeGreaterThan(10, "{$profile->handle}.{$d->key} has no source");
        }
    }
});
