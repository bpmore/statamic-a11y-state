<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Feedback;

use Bpmore\A11yState\Evidence\Actions;
use Bpmore\A11yState\Evidence\Record;
use Bpmore\A11yState\Statement\Dates;
use DateTimeImmutable;

/**
 * The acknowledgment clock. Given the feedback submissions a site has
 * received and the evidence log, which is where acknowledgments are
 * written (an `feedback-acknowledged` record whose subject is the
 * submission id), says for each submission whether it was acknowledged,
 * how long that took or has taken, and whether it is past the window.
 *
 * A null window means the site has configured no response time. Then
 * nothing is overdue, the report says so, and the statement makes no
 * claim about response times. A window nobody set is not a promise.
 */
final class FeedbackWindow
{
    public function __construct(private readonly ?int $responseDays) {}

    public function responseDays(): ?int
    {
        return $this->responseDays;
    }

    /**
     * @param  list<Submission>  $submissions
     * @param  list<Record>  $records  the evidence log, or the feedback-acknowledged part of it
     */
    public function report(array $submissions, array $records, DateTimeImmutable $now): FeedbackReport
    {
        $acknowledged = [];

        foreach ($records as $record) {
            if ($record->action !== Actions::FEEDBACK_ACKNOWLEDGED || $record->subject === null) {
                continue;
            }

            // The first acknowledgment is the one that stops the clock.
            if (! isset($acknowledged[$record->subject]) || $record->at < $acknowledged[$record->subject]->at) {
                $acknowledged[$record->subject] = $record;
            }
        }

        $rows = [];

        foreach ($submissions as $submission) {
            $ack = $acknowledged[$submission->id] ?? null;
            $received = $submission->receivedAt();
            $end = $ack !== null ? $ack->timestamp() : $now;
            $days = max(0, Dates::daysBetween($received, $end));

            $rows[] = new FeedbackRow(
                submission: $submission,
                acknowledgedAt: $ack?->at,
                acknowledgedBy: $ack?->actor,
                recordId: $ack?->id,
                days: $days,
                overdue: $this->responseDays !== null && $ack === null && $days > $this->responseDays,
            );
        }

        usort($rows, fn (FeedbackRow $a, FeedbackRow $b) => $b->submission->at <=> $a->submission->at);

        return new FeedbackReport($this->responseDays, array_values($rows));
    }
}
