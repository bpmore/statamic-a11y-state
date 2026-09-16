<?php

declare(strict_types=1);

use Bpmore\Grange\Evidence\Actions;
use Bpmore\Grange\Evidence\Record;
use Bpmore\Grange\Feedback\FeedbackWindow;
use Bpmore\Grange\Feedback\Submission;

function ack(string $submission, string $at, string $id = 'ack'): Record
{
    return new Record($id, $at, 'Grace', Actions::FEEDBACK_ACKNOWLEDGED, null, $submission, 'Replied by email');
}

it('reports each submission with its clock, and calls the unacknowledged ones past the window overdue', function () {
    $window = new FeedbackWindow(5);
    $report = $window->report(
        [
            new Submission('s1', '2026-09-01T09:00:00+00:00', 'Cannot read the menu'),
            new Submission('s2', '2026-09-12T09:00:00+00:00', 'Form has no labels'),
            new Submission('s3', '2026-09-02T09:00:00+00:00', 'Video has no captions'),
        ],
        [ack('s3', '2026-09-04T09:00:00+00:00')],
        at('2026-09-15T09:00:00+00:00'),
    );

    $rows = array_combine(array_map(fn ($r) => $r->submission->id, $report->rows), $report->rows);

    expect($rows['s1']->overdue)->toBeTrue();
    expect($rows['s1']->days)->toBe(14);
    expect($rows['s2']->overdue)->toBeFalse();
    expect($rows['s2']->days)->toBe(3);
    expect($rows['s3']->isAcknowledged())->toBeTrue();
    expect($rows['s3']->days)->toBe(2);
    expect($rows['s3']->acknowledgedBy)->toBe('Grace');
    expect($rows['s3']->overdue)->toBeFalse();

    expect(count($report->overdue()))->toBe(1);
    expect(count($report->awaiting()))->toBe(2);
    expect(array_map(fn ($r) => $r->submission->id, $report->rows))->toBe(['s2', 's3', 's1']);
});

it('makes nothing overdue and no claim when no window is configured', function () {
    $report = (new FeedbackWindow(null))->report(
        [new Submission('s1', '2020-01-01T00:00:00+00:00')],
        [],
        at(),
    );

    expect($report->hasWindow())->toBeFalse();
    expect($report->overdue())->toBe([]);
    expect($report->rows[0]->days)->toBeGreaterThan(2000);
    expect($report->toArray()['response_days'])->toBeNull();
});

it('stops the clock at the first acknowledgment when there are several', function () {
    $report = (new FeedbackWindow(5))->report(
        [new Submission('s1', '2026-09-01T09:00:00+00:00')],
        [ack('s1', '2026-09-10T09:00:00+00:00', 'later'), ack('s1', '2026-09-03T09:00:00+00:00', 'first')],
        at('2026-09-15T09:00:00+00:00'),
    );

    expect($report->rows[0]->days)->toBe(2);
    expect($report->rows[0]->recordId)->toBe('first');
});

it('ignores records that are not acknowledgments', function () {
    $note = new Record('n', '2026-09-03T09:00:00+00:00', 'Grace', Actions::NOTE, null, 's1', 'Looked at it');
    $report = (new FeedbackWindow(1))->report([new Submission('s1', '2026-09-01T09:00:00+00:00')], [$note], at('2026-09-15T09:00:00+00:00'));

    expect($report->rows[0]->isAcknowledged())->toBeFalse();
    expect($report->rows[0]->overdue)->toBeTrue();
});

it('reports an empty form as empty', function () {
    $report = (new FeedbackWindow(5))->report([], [], at());

    expect($report->rows)->toBe([]);
    expect($report->toArray()['total'])->toBe(0);
});
