<?php

declare(strict_types=1);

use Bpmore\A11yState\Commands\Check;
use Bpmore\A11yState\Statement\ConformanceStatus;
use Illuminate\Support\Facades\Artisan;

/*
 * `php please a11y:state:check`: exit codes, the JSON contract, and the
 * staleness flag (discriminating test 5, the command half).
 */

function check(array $options = []): array
{
    $code = Artisan::call('statamic:a11y:state:check', $options);

    return [$code, Artisan::output()];
}

it('exits 1 with no statement saved, because there is nothing complete to publish', function () {
    [$code, $out] = check();

    expect($code)->toBe(Check::EXIT_FOUND);
    expect($out)->toContain('no statement saved');
    expect($out)->toContain('error');
});

it('exits 0 when the statement is complete and current', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant, ['assessment_date' => date('Y-m-d')]);

    [$code, $out] = check();

    expect($code)->toBe(Check::SUCCESS);
    expect($out)->toContain('Complete and current');
});

it('writes clean JSON to stdout with --json and nothing else', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant, ['assessment_date' => '2020-01-01']);

    [$code, $out] = check(['--json' => true]);

    $json = json_decode($out, true, 512, JSON_THROW_ON_ERROR);

    expect($code)->toBe(Check::EXIT_FOUND);
    expect($json['sites'][0]['site'])->toBe('default');
    expect($json['sites'][0]['stale'])->toBeTrue();
    expect($json['sites'][0]['complete'])->toBeTrue();
    expect($json['found'])->toBe(['stale']);
    expect($json['failed'])->toBeTrue();
    expect($json['feedback']['form'])->toBeNull();
    expect($json['integrations'])->toBe('none');
    // The first byte is the JSON and the last is its newline: no banner, no progress.
    expect($out[0])->toBe('{');
    expect(substr($out, -2))->toBe("}\n");
});

it('flags a stale assessment and exits 1 by default, and exits 0 when stale is not in --fail-on', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant, ['assessment_date' => '2020-01-01']);

    [$code, $out] = check();
    expect($code)->toBe(Check::EXIT_FOUND);
    expect($out)->toContain('warning');
    expect($out)->toContain('older than the 365-day window');

    [$code] = check(['--fail-on' => 'incomplete,overclaim']);
    expect($code)->toBe(Check::SUCCESS);

    [$code] = check(['--fail-on' => 'stale']);
    expect($code)->toBe(Check::EXIT_FOUND);
});

it('does not alter the published statement when flagging it stale', function () {
    saveStatement('w3c', ConformanceStatus::PartiallyConformant, ['assessment_date' => '2020-01-01']);
    $before = file_get_contents($this->storage.'/statements/default.yaml');

    check();

    expect(file_get_contents($this->storage.'/statements/default.yaml'))->toBe($before);
});

it('exits 2 on a --fail-on it does not know', function () {
    [$code, $out] = check(['--fail-on' => 'vibes']);

    expect($code)->toBe(Check::EXIT_INVALID);
    expect($out)->toContain('vibes');
});

it('exits 2 on a site it does not know', function () {
    [$code, $out] = check(['--site' => ['mars']]);

    expect($code)->toBe(Check::EXIT_INVALID);
    expect($out)->toContain('mars');
});

it('names the missing disclosure in the human output and the JSON', function () {
    saveStatement('en301549', ConformanceStatus::PartiallyConformant, ['enforcement_body' => '', 'assessment_date' => date('Y-m-d')]);

    [$code, $out] = check();
    expect($code)->toBe(Check::EXIT_FOUND);
    expect($out)->toContain('Contact information of the enforcement body');

    [, $out] = check(['--json' => true]);
    $json = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
    expect($json['sites'][0]['problems'][0]['field'])->toBe('enforcement_body');
    expect($json['found'])->toBe(['incomplete']);
});
