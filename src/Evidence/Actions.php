<?php

declare(strict_types=1);

namespace Bpmore\Grange\Evidence;

/**
 * What a record can say was done. A closed list, so an export can be
 * filtered and a reader knows every word that can appear in the column.
 */
final class Actions
{
    /** A criterion or page was evaluated; the notes say how and what was found. */
    public const ASSESSED = 'assessed';

    /** A problem was fixed. */
    public const FIXED = 'fixed';

    /** A known exception was added to the statement. */
    public const EXCEPTION_OPENED = 'exception-opened';

    /** A known exception was marked resolved. */
    public const EXCEPTION_RESOLVED = 'exception-resolved';

    /** A person attested to full conformance. The statement's attestation points at this record. */
    public const ATTESTED = 'attested';

    /** The statement itself was saved; the notes say what changed. */
    public const STATEMENT_UPDATED = 'statement-updated';

    /** A feedback submission was acknowledged; the subject is the submission id. */
    public const FEEDBACK_ACKNOWLEDGED = 'feedback-acknowledged';

    /** Anything else worth writing down. */
    public const NOTE = 'note';

    public const ALL = [
        self::ASSESSED,
        self::FIXED,
        self::EXCEPTION_OPENED,
        self::EXCEPTION_RESOLVED,
        self::ATTESTED,
        self::STATEMENT_UPDATED,
        self::FEEDBACK_ACKNOWLEDGED,
        self::NOTE,
    ];

    public static function isKnown(string $action): bool
    {
        return in_array($action, self::ALL, true);
    }

    public static function label(string $action): string
    {
        return ucfirst(str_replace('-', ' ', $action));
    }
}
