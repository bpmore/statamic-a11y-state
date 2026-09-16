<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Feedback;

use Bpmore\A11yState\Statement\Dates;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * One piece of feedback, as much of it as the clock needs: an id, when it
 * arrived, and a line to show. What was said stays in the form's own
 * submission; the log records that it was answered, not what it was.
 */
final class Submission
{
    public function __construct(
        public readonly string $id,
        public readonly string $at,
        public readonly string $summary = '',
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A submission needs an id.');
        }

        if (Dates::parse($at) === null) {
            throw new InvalidArgumentException("Submission [{$id}] has a received time that is not a moment: [{$at}].");
        }
    }

    public function receivedAt(): DateTimeImmutable
    {
        /** @var DateTimeImmutable $parsed */
        $parsed = Dates::parse($this->at);

        return $parsed;
    }
}
