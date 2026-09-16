<?php

declare(strict_types=1);

namespace Bpmore\Grange\Statement;

use DateTimeImmutable;

/**
 * Turns a statement into a document, or refuses.
 *
 * It refuses on any error the validator reports and says which: a missing
 * required disclosure is named, an overclaim names the exceptions. There
 * is no "generate anyway". A statement with a required disclosure missing
 * is not a statement with a gap, it is a document the regime it is written
 * under does not recognise.
 *
 * The order of sections follows the EU model statement, which is the most
 * prescriptive of the profiles, and the W3C's where the EU model is silent.
 * Any disclosure a profile adds that has no place here gets a section of
 * its own, headed with the disclosure's label, after the standard ones.
 */
final class Generator
{
    public const DISCLAIMER = 'This statement was prepared with software that records what the organisation entered and what its testing found. It is not legal advice, and the software cannot confirm that a site is accessible or that any legal obligation has been met. The organisation named above is responsible for the accuracy of this statement.';

    /**
     * The keys placed by hand below. Everything else a profile declares is
     * appended as its own section, in the profile's order.
     */
    private const PLACED = [
        'organization', 'commitment', 'scope', 'standard', 'conformance_status', 'exceptions',
        'feedback_mechanism', 'contact', 'evaluation_method', 'assessment_date', 'preparation_date',
        'review_date', 'evaluation_report', 'enforcement_procedure', 'enforcement_body',
    ];

    public function __construct(
        private readonly Profiles $profiles,
        private readonly Validator $validator,
    ) {}

    /**
     * @param  list<KnownException>  $external  open findings from integrations
     * @param  int|null  $responseDays  the configured response window, or null when none is configured and no claim is made
     *
     * @throws IncompleteStatement
     */
    public function generate(Statement $statement, DateTimeImmutable $now, array $external = [], ?string $siteName = null, ?int $responseDays = null): Document
    {
        $problems = $this->validator->check($statement, $now, $external);
        $errors = Validator::errors($problems);

        if ($errors !== []) {
            throw new IncompleteStatement($errors);
        }

        $profile = $this->profiles->get($statement->profile);
        $organization = $this->text($statement, 'organization') ?? $siteName ?? $statement->site;
        $sections = [];

        $sections[] = new Section('commitment', 'Our commitment', array_values(array_filter([
            $this->text($statement, 'commitment'),
        ])));

        $sections[] = new Section('scope', 'Scope of this statement', array_values(array_filter([
            $this->text($statement, 'scope'),
        ])));

        $standard = $this->text($statement, 'standard');
        $sections[] = new Section('conformance', 'Conformance status', array_values(array_filter([
            $standard !== null ? "The standard applied is {$standard}." : null,
            $this->statusSentence($statement->status, $standard),
            $statement->status->definition(),
            $statement->attestation !== null && $statement->status === ConformanceStatus::FullyConformant
                ? "Full conformance was confirmed by {$statement->attestation->name} on {$statement->attestation->date}. Basis: {$statement->attestation->basis}"
                : null,
        ])));

        $open = array_merge($statement->openExceptions(), array_values(array_filter($external, fn (KnownException $e) => $e->isOpen())));
        $sections[] = new Section(
            'exceptions',
            'Known exceptions',
            $open === [] ? ['No known exceptions are recorded.'] : [],
            array_map(fn (KnownException $e) => $this->exceptionLine($e), $open),
        );

        $feedback = array_values(array_filter([
            $this->text($statement, 'feedback_mechanism'),
            $responseDays !== null ? sprintf('We aim to acknowledge feedback within %d %s.', $responseDays, $responseDays === 1 ? 'day' : 'days') : null,
        ]));
        $sections[] = new Section('feedback', 'Feedback', $feedback);

        $sections[] = new Section('contact', 'Contact', array_values(array_filter([
            $this->text($statement, 'contact'),
        ])));

        $sections[] = new Section('assessment', 'How this statement was prepared', array_values(array_filter([
            $this->text($statement, 'evaluation_method'),
            $this->dated('The most recent assessment was carried out on', $this->text($statement, 'assessment_date')),
            $this->dated('This statement was prepared on', $this->text($statement, 'preparation_date')),
            $this->dated('This statement was last reviewed on', $this->text($statement, 'review_date')),
            $this->text($statement, 'evaluation_report'),
        ])));

        $sections[] = new Section('enforcement', 'Enforcement procedure', array_values(array_filter([
            $this->text($statement, 'enforcement_procedure'),
            $this->text($statement, 'enforcement_body'),
        ])));

        foreach ($profile->disclosures as $disclosure) {
            if (in_array($disclosure->key, self::PLACED, true)) {
                continue;
            }

            $value = $this->text($statement, $disclosure->key);

            if ($value !== null) {
                $sections[] = new Section($disclosure->key, $disclosure->label, [$value]);
            }
        }

        return new Document(
            title: $profile->title,
            organization: $organization,
            status: $statement->status,
            profile: $profile->name,
            sections: array_values(array_filter($sections, fn (Section $s) => ! $s->isEmpty())),
            disclaimer: self::DISCLAIMER,
            generatedAt: Dates::moment($now),
        );
    }

    private function statusSentence(ConformanceStatus $status, ?string $standard): string
    {
        $with = $standard !== null ? " with {$standard}" : '';

        return match ($status) {
            ConformanceStatus::FullyConformant => "This website is fully conformant{$with}.",
            ConformanceStatus::PartiallyConformant => "This website is partially conformant{$with}, due to the exceptions listed below.",
            ConformanceStatus::NonConformant => "This website is non-conformant{$with}. The exceptions are listed below.",
            ConformanceStatus::NotAssessed => 'This website has not been assessed'.($standard !== null ? " against {$standard}" : '').'.',
        };
    }

    private function exceptionLine(KnownException $e): string
    {
        $parts = [$e->description];

        if ($e->criterion !== null) {
            $parts[] = "(WCAG {$e->criterion})";
        }

        if ($e->page !== null) {
            $parts[] = "on {$e->page}";
        }

        $line = implode(' ', $parts).'.';

        if ($e->reason !== null) {
            $line .= " Reason: {$e->reason}.";
        }

        if ($e->remediationDate !== null) {
            $line .= " Planned fix: {$e->remediationDate}.";
        }

        return $line;
    }

    private function dated(string $lead, ?string $date): ?string
    {
        return $date === null ? null : "{$lead} {$date}.";
    }

    private function text(Statement $statement, string $key): ?string
    {
        $value = $statement->disclosure($key);

        if (is_array($value)) {
            $value = implode("\n", array_filter(array_map('strval', $value)));
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
