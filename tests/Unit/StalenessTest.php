<?php

declare(strict_types=1);

use Bpmore\A11yState\Statement\Generator;
use Bpmore\A11yState\Statement\HtmlRenderer;
use Bpmore\A11yState\Statement\Problem;
use Bpmore\A11yState\Statement\Staleness;
use Bpmore\A11yState\Statement\Validator;

/*
 * Discriminating test 5, the core half: an assessment older than the window
 * is a warning, and generation still succeeds with the published statement
 * unchanged. The command and the control-panel notice are feature tests.
 */

it('flags an assessment older than the window as a warning, not an error', function () {
    $statement = completeStatement('w3c')->withDisclosures(['assessment_date' => '2025-01-01']);
    $problems = checker(staleAfterDays: 365)->check($statement, at('2026-09-15T00:00:00+00:00'));

    $stale = array_values(array_filter($problems, fn (Problem $p) => $p->code === Problem::STALE));
    expect($stale)->toHaveCount(1);
    expect($stale[0]->isError())->toBeFalse();
    expect($stale[0]->context['age_days'])->toBe(622);
    expect($stale[0]->message)->toContain('2025-01-01');
    expect(Validator::errors($problems))->toBe([]);
});

it('does not flag an assessment inside the window, including on the last day', function () {
    $statement = completeStatement('w3c')->withDisclosures(['assessment_date' => '2025-09-15']);
    $problems = checker(staleAfterDays: 365)->check($statement, at('2026-09-15T00:00:00+00:00'));

    expect(array_map(fn (Problem $p) => $p->code, $problems))->not->toContain(Problem::STALE);

    $statement = completeStatement('w3c')->withDisclosures(['assessment_date' => '2025-09-14']);
    $problems = checker(staleAfterDays: 365)->check($statement, at('2026-09-15T00:00:00+00:00'));

    expect(array_map(fn (Problem $p) => $p->code, $problems))->toContain(Problem::STALE);
});

it('still generates the statement when stale, and the document does not mention staleness', function () {
    $statement = completeStatement('w3c')->withDisclosures(['assessment_date' => '2020-01-01']);
    $document = (new Generator(profiles(), checker(staleAfterDays: 30)))->generate($statement, at());

    $html = (new HtmlRenderer)->render($document);
    expect(stripos($html, 'stale'))->toBeFalse();
    expect($html)->toContain('2020-01-01');
});

it('is not stale when there is no assessment date at all; that is a missing disclosure, if the profile requires one', function () {
    $statement = completeStatement('w3c')->withDisclosures(['assessment_date' => null]);

    expect(Staleness::check($statement, 1, at()))->toBeNull();
    expect(Staleness::ageInDays($statement, at()))->toBeNull();
});

it('is not stale when the date cannot be read', function () {
    $statement = completeStatement('w3c')->withDisclosures(['assessment_date' => 'soon']);

    expect(Staleness::check($statement, 1, at()))->toBeNull();
});
