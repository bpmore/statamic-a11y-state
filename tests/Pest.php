<?php

declare(strict_types=1);

use Bpmore\A11yState\Statement\Attestation;
use Bpmore\A11yState\Statement\ConformanceStatus;
use Bpmore\A11yState\Statement\KnownException;
use Bpmore\A11yState\Statement\Profiles;
use Bpmore\A11yState\Statement\Rules;
use Bpmore\A11yState\Statement\Severity;
use Bpmore\A11yState\Statement\Statement;
use Bpmore\A11yState\Statement\Validator;
use Bpmore\A11yState\Tests\TestCase;

// Only the feature tests boot Statamic. The core takes values and returns
// values, and a suite that boots nothing for it cannot grow a dependency
// on something booted.
uses(TestCase::class)->in('Feature');

/** The shipped config file, read straight from disk, as the unit tests see it. */
function a11yStateConfig(): array
{
    return require __DIR__.'/../config/statamic-a11y-state.php';
}

function profiles(): Profiles
{
    return Profiles::fromArray(a11yStateConfig()['profiles']);
}

function rules(string $severity = 'minor', int $staleAfterDays = 365): Rules
{
    return new Rules(Severity::from($severity), $staleAfterDays);
}

function checker(string $severity = 'minor', int $staleAfterDays = 365): Validator
{
    return new Validator(profiles(), rules($severity, $staleAfterDays));
}

function at(string $when = '2026-09-15T12:00:00+00:00'): DateTimeImmutable
{
    return new DateTimeImmutable($when);
}

/**
 * A statement with every disclosure a profile lists filled in, so a test
 * can remove exactly one and know that one is what it is testing.
 */
function completeStatement(string $profile = 'w3c', ConformanceStatus $status = ConformanceStatus::PartiallyConformant): Statement
{
    $disclosures = [];

    foreach (profiles()->get($profile)->disclosures as $d) {
        $disclosures[$d->key] = match ($d->type) {
            'date' => '2026-09-01',
            'url' => 'https://example.test/report',
            default => 'Example '.$d->label,
        };
    }

    unset($disclosures['conformance_status'], $disclosures['exceptions']);

    return (new Statement('default', $profile, $status, $disclosures))
        ->withExceptions([exception('e1', 'minor')]);
}

function exception(string $id, string $severity = 'serious', string $status = KnownException::OPEN, ?string $criterion = '1.1.1'): KnownException
{
    return new KnownException(
        id: $id,
        description: 'Images in the gallery have no text alternative',
        severity: Severity::from($severity),
        status: $status,
        criterion: $criterion,
        page: '/gallery',
        reason: 'Legacy uploads',
        remediationDate: '2026-12-01',
    );
}

function attestation(): Attestation
{
    return new Attestation('Ada Example', '2026-09-10', 'Full manual evaluation of every template against WCAG 2.2 AA, 8 and 9 September 2026.');
}

/** A directory of this test's own under the system temp dir, removed by the caller. */
function tempDir(string $name): string
{
    $dir = sys_get_temp_dir().'/a11y-state-'.$name.'-'.bin2hex(random_bytes(4));
    mkdir($dir, 0775, true);

    return $dir;
}

function removeDir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        @chmod($file->getPathname(), 0666);
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($dir);
}

/**
 * A control-panel user with the named permissions, signed in. Super when
 * no permissions are named.
 *
 * @param  list<string>  $permissions
 */
function cpUser(array $permissions = []): \Statamic\Contracts\Auth\User
{
    $user = \Statamic\Facades\User::make()->email(uniqid('u').'@example.test')->data(['name' => 'Ada Example']);

    if ($permissions === []) {
        $user->makeSuper();
    } else {
        $role = \Statamic\Facades\Role::make('a11y-state-'.uniqid())->permissions($permissions);
        $role->save();
        $user->assignRole($role);
    }

    $user->save();

    return $user;
}

/** Save a complete statement straight through the service, as a fixture for the feature tests. */
function saveStatement(string $profile = 'w3c', \Bpmore\A11yState\Statement\ConformanceStatus $status = \Bpmore\A11yState\Statement\ConformanceStatus::PartiallyConformant, array $disclosures = []): \Bpmore\A11yState\Statement\Statement
{
    $statement = completeStatement($profile, $status)->withDisclosures($disclosures);

    return app(\Bpmore\A11yState\Statements::class)->save($statement, 'fixture');
}

/** The utility's own markup, as Vue will receive it: unpacked from Inertia's page object. */
function utilityHtml(string $path = ''): string
{
    $response = test()->get(cp_route('utilities.index').'/a11y-state'.$path)->assertOk()->getContent();

    preg_match('/data-page="([^"]+)"/', $response, $m);
    $page = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

    return (string) $page['props']['html'];
}

/**
 * Whether markup Vue will compile is well formed. A utility view becomes
 * `defineComponent({ template })` in the browser, where an unclosed tag is
 * a compile error that shows as a blank page and never as an exception on
 * the server. Vue's template syntax is close enough to XML that parsing the
 * markup as XML, with void elements made self-closing, catches it here.
 */
function assertVueTemplateIsWellFormed(string $html): void
{
    $xml = preg_replace('/<(input|br|hr|img|meta|link)(\s[^<>]*?)?\s*\/?>/', '<$1$2 />', $html);

    $xml = preg_replace_callback('/<[a-z][\w-]*(\s[^<>]*)?\/?>/i', function ($m) {
        return preg_replace_callback(
            '/(\s)([a-z][\w:@.-]*)(="[^"]*")?/i',
            fn ($a) => $a[1].$a[2].($a[3] ?? '="'.$a[2].'"'),
            $m[0],
        );
    }, (string) $xml);
    // Escaped angle brackets in text stay escaped, or a name like
    // "Ada <ada@example.test>" would be read as a tag by this check when
    // the browser would read it as text.
    $xml = str_replace(['&lt;', '&gt;', '&amp;'], ["\0LT\0", "\0GT\0", "\0AMP\0"], (string) $xml);
    $xml = html_entity_decode((string) $xml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $xml = str_replace('&', '&amp;', $xml);
    $xml = str_replace(["\0LT\0", "\0GT\0", "\0AMP\0"], ['&lt;', '&gt;', '&amp;'], $xml);

    $previous = libxml_use_internal_errors(true);
    $ok = simplexml_load_string('<root>'.$xml.'</root>') !== false;
    $errors = array_map(fn ($e) => trim($e->message).' (line '.$e->line.')', libxml_get_errors());
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    expect($ok)->toBeTrue('the template is not well formed, so Vue would not compile it: '.implode('; ', array_slice($errors, 0, 5)));
}

/** The page's text, flattened, for assertions on words rather than markup. */
function pageText(string $html): string
{
    return trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}
