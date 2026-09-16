<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Feedback;

final class FeedbackRow
{
    public function __construct(
        public readonly Submission $submission,
        public readonly ?string $acknowledgedAt,
        public readonly ?string $acknowledgedBy,
        public readonly ?string $recordId,
        /** Days from receipt to acknowledgment, or to now while unacknowledged. */
        public readonly int $days,
        public readonly bool $overdue,
    ) {}

    public function isAcknowledged(): bool
    {
        return $this->acknowledgedAt !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->submission->id,
            'received_at' => $this->submission->at,
            'summary' => $this->submission->summary,
            'acknowledged_at' => $this->acknowledgedAt,
            'acknowledged_by' => $this->acknowledgedBy,
            'record_id' => $this->recordId,
            'days' => $this->days,
            'overdue' => $this->overdue,
        ];
    }
}
