<?php

declare(strict_types=1);

namespace Bpmore\Grange\Evidence;

use RuntimeException;

/**
 * Thrown by every driver when asked to write a record whose id is already
 * in the log. Writing over a record is the one thing the log refuses.
 */
final class RecordExists extends RuntimeException
{
    public function __construct(public readonly string $id)
    {
        parent::__construct("Evidence record [{$id}] is already written and cannot be changed. Write a new record that supersedes it.");
    }
}
