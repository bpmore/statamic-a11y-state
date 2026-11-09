<?php

declare(strict_types=1);

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Export;
use Bpmore\A11yState\Evidence\Record;

function exportRecords(): array
{
    return [
        new Record('r1', '2026-09-01T10:00:00+00:00', 'Ada Example', Actions::ASSESSED, '1.1.1', '/gallery', "Line one\nLine two, with \"quotes\" and a comma", null, ['finding' => 42]),
        new Record('r2', '2026-09-02T10:00:00+00:00', 'Grace', Actions::FIXED, '1.1.1', '/gallery', 'Alt text added', 'r1'),
    ];
}

it('writes CSV with a header, every column, quoted multi-line notes, and the superseded_by column', function () {
    $csv = Export::csv(exportRecords());
    $rows = array_map(fn (string $line) => str_getcsv($line, ',', '"', ''), explode("\n", trim(substr($csv, 3))));

    expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF");
    expect($rows[0])->toBe(Export::COLUMNS);

    // The multi-line note is one field, so a naive line split gives three
    // "rows" for two records. Parse it properly instead.
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, substr($csv, 3));
    rewind($handle);
    $parsed = [];

    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $parsed[] = $row;
    }

    fclose($handle);

    expect($parsed)->toHaveCount(3);
    $r1 = array_combine(Export::COLUMNS, $parsed[1]);
    $r2 = array_combine(Export::COLUMNS, $parsed[2]);

    expect($r1['notes'])->toBe("Line one\nLine two, with \"quotes\" and a comma");
    expect($r1['superseded_by'])->toBe('r2');
    expect($r1['meta'])->toBe('{"finding":42}');
    expect($r2['supersedes'])->toBe('r1');
    expect($r2['superseded_by'])->toBe('');
});

it('keeps a CSV cell that starts like a formula as text, and leaves JSON alone', function () {
    // Notes and subjects are typed by a person, and a spreadsheet runs a
    // cell beginning with one of these characters when the file is opened.
    $records = [];

    foreach (['=HYPERLINK("https://example.test","x")', '+1', '-1', '@SUM(A1)', "\tcmd", "\rcmd"] as $i => $notes) {
        $records[] = new Record('r'.$i, '2026-09-01T10:00:00+00:00', 'Ada', Actions::NOTE, null, $notes, $notes);
    }

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, substr(Export::csv($records), 3));
    rewind($handle);
    $rows = [];

    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        $rows[] = array_combine(Export::COLUMNS, $row);
    }

    fclose($handle);
    array_shift($rows);

    foreach ($rows as $i => $row) {
        expect($row['notes'])->toBe("'".$records[$i]->notes, $records[$i]->notes);
        expect($row['subject'])->toBe("'".$records[$i]->subject);
        // The id, the date and the actor begin with a letter or a digit and
        // are passed through as they are.
        expect($row['id'])->toBe($records[$i]->id);
        expect($row['at'])->toBe($records[$i]->at);
    }

    // JSON is read by a script, not a spreadsheet, and says what was written.
    $json = json_decode(Export::json($records), true, 512, JSON_THROW_ON_ERROR);
    expect($json['records'][0]['notes'])->toBe('=HYPERLINK("https://example.test","x")');
});

it('writes JSON that a script can read back, with the count', function () {
    $json = json_decode(Export::json(exportRecords()), true, 512, JSON_THROW_ON_ERROR);

    expect($json['count'])->toBe(2);
    expect(array_keys($json['records'][0]))->toBe(Export::COLUMNS);
    expect($json['records'][0]['meta'])->toBe(['finding' => 42]);
    expect($json['records'][1]['supersedes'])->toBe('r1');
});

it('exports an empty log as a header only, or an empty list', function () {
    expect(trim(substr(Export::csv([]), 3)))->toBe(implode(',', Export::COLUMNS));
    expect(json_decode(Export::json([]), true))->toBe(['records' => [], 'count' => 0]);
});

it('refuses a format it does not have', function () {
    Export::render('xlsx', []);
})->throws(RuntimeException::class, 'xlsx');
