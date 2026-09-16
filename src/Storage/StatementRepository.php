<?php

declare(strict_types=1);

namespace Bpmore\A11yState\Storage;

use Bpmore\A11yState\Statement\Statement;

/**
 * Where a site's statement is kept. One per site.
 *
 * Every driver calls `Guard::assertPersistable()` before it writes. The
 * rule that "fully conformant" needs an attestation is enforced there, at
 * the storage layer, so no controller, command or future driver can store
 * a claim nobody made.
 */
interface StatementRepository
{
    public function find(string $site): ?Statement;

    /**
     * @throws \Bpmore\A11yState\Statement\AttestationRequired
     */
    public function save(Statement $statement): void;

    /**
     * @return list<string> the sites that have a statement
     */
    public function sites(): array;
}
