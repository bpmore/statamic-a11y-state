<?php

declare(strict_types=1);

namespace Bpmore\Grange\Feedback;

final class FeedbackReport
{
    /**
     * @param  list<FeedbackRow>  $rows  newest first
     */
    public function __construct(
        public readonly ?int $responseDays,
        public readonly array $rows,
    ) {}

    /**
     * @return list<FeedbackRow>
     */
    public function overdue(): array
    {
        return array_values(array_filter($this->rows, fn (FeedbackRow $r) => $r->overdue));
    }

    /**
     * @return list<FeedbackRow>
     */
    public function awaiting(): array
    {
        return array_values(array_filter($this->rows, fn (FeedbackRow $r) => ! $r->isAcknowledged()));
    }

    public function hasWindow(): bool
    {
        return $this->responseDays !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'response_days' => $this->responseDays,
            'total' => count($this->rows),
            'awaiting' => count($this->awaiting()),
            'overdue' => count($this->overdue()),
            'rows' => array_map(fn (FeedbackRow $r) => $r->toArray(), $this->rows),
        ];
    }
}
