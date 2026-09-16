<?php

declare(strict_types=1);

namespace Bpmore\Grange\Storage;

use Bpmore\Grange\Statement\AttestationRequired;
use Bpmore\Grange\Statement\ConformanceStatus;
use Bpmore\Grange\Statement\Statement;

/**
 * What no driver may write. Called by every `save`.
 */
final class Guard
{
    /**
     * @throws AttestationRequired
     */
    public static function assertPersistable(Statement $statement): void
    {
        if ($statement->status === ConformanceStatus::FullyConformant && $statement->attestation === null) {
            throw new AttestationRequired;
        }
    }
}
