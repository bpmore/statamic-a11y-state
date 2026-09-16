<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Storage;

use Bpmore\A11yState\Statement\AttestationRequired;
use Bpmore\A11yState\Statement\ConformanceStatus;
use Bpmore\A11yState\Statement\Statement;

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
