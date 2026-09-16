<?php

declare(strict_types=1);

use Bpmore\Grange\Statement\Statement;

/*
 * Discriminating test 7: profile isolation. Switching profiles does not
 * silently drop data belonging only to the previous profile: it warns
 * and preserves.
 */

it('keeps every disclosure when the profile changes, and names the ones the new profile has no field for', function () {
    $statement = completeStatement('section508');
    expect($statement->has('relay_service'))->toBeTrue();
    expect($statement->has('complaint_instructions'))->toBeTrue();

    $switched = $statement->withProfile('w3c');
    $orphans = profiles()->orphanedBy($statement, profiles()->get('w3c'));

    sort($orphans);
    expect($orphans)->toBe(['complaint_instructions', 'policy_links', 'reasonable_accommodations', 'relay_service']);

    // Preserved on the statement, byte for byte.
    foreach ($orphans as $key) {
        expect($switched->disclosure($key))->toBe($statement->disclosure($key));
    }

    expect($switched->disclosures)->toBe($statement->disclosures);
});

it('names nothing when every filled disclosure has a home in the new profile', function () {
    $statement = completeStatement('w3c');

    expect(profiles()->orphanedBy($statement, profiles()->get('en301549')))->toBe([]);
});

it('does not name an empty disclosure as orphaned, since there is nothing to lose', function () {
    $statement = completeStatement('section508')->withDisclosures(['relay_service' => '', 'complaint_instructions' => null]);
    $orphans = profiles()->orphanedBy($statement, profiles()->get('w3c'));

    sort($orphans);
    expect($orphans)->toBe(['policy_links', 'reasonable_accommodations']);
});

it('survives a round trip through the array form with the orphans intact', function () {
    $statement = completeStatement('aoda')->withProfile('w3c');
    $again = Statement::fromArray($statement->toArray());

    expect($again->disclosure('accessible_formats_notice'))->toBe($statement->disclosure('accessible_formats_notice'));
    expect($again->profile)->toBe('w3c');
});
