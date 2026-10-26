<?php

declare(strict_types=1);

namespace Bpmore\A11yState;

use Bpmore\A11yState\Database\EvidenceDatabase;
use Bpmore\A11yState\Evidence\EvidenceLog;
use Bpmore\A11yState\Evidence\YamlEvidenceLog;
use Bpmore\A11yState\Feedback\FeedbackWindow;
use Bpmore\A11yState\Feedback\FormSubmissions;
use Bpmore\A11yState\Integrations\A11yReportSource;
use Bpmore\A11yState\Integrations\FindingSource;
use Bpmore\A11yState\Integrations\FindingSources;
use Bpmore\A11yState\Statement\Generator;
use Bpmore\A11yState\Statement\Profiles;
use Bpmore\A11yState\Statement\Rules;
use Bpmore\A11yState\Statement\Validator;
use Bpmore\A11yState\Storage\StatementRepository;
use Bpmore\A11yState\Storage\YamlStatementRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Statamic\Facades\Permission;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;

/**
 * The addon: a config file, two YAML directories, a command, a public page,
 * a tag, and a utility in the control panel.
 *
 * Nothing here depends on another `bpmore/*` addon. The integrations are
 * adapters behind an interface, each of which asks whether its addon is
 * installed before doing anything, and the null source is what runs when
 * none is. There is no licence check and there must never be one, for the
 * reason A11y Report gives: a code path whose failure stops a site
 * from publishing its accessibility statement is a code path this addon
 * should not have.
 */
class ServiceProvider extends AddonServiceProvider
{
    public const PACKAGE = 'bpmore/statamic-a11y-state';

    public const UTILITY = 'a11y-state';

    protected $config = true;

    protected $commands = [
        Commands\Check::class,
        Commands\Install::class,
    ];

    protected $tags = [
        Tags\A11yState::class,
    ];

    protected $viewNamespace = 'a11y-state';

    protected $routes = [
        'web' => __DIR__.'/../routes/web.php',
    ];

    public function bootAddon()
    {
        // `@a11yStateText($x)` for any value that reaches a view Vue will
        // compile. Its own name rather than `@plain`, which A11y
        // Report registers: two addons registering one directive name is a
        // collision the last one to boot wins silently.
        Blade::directive('a11yStateText', fn ($expression) => "<?php echo \\Bpmore\\A11yState\\Support\\VueSafe::text({$expression}); ?>");

        // Seeing the statement and the log is Statamic's own "access
        // utility" permission. Writing is split three ways, because
        // attesting to full conformance is a different act from editing
        // text, and a site may well want different people doing each.
        Permission::extend(function () {
            Permission::group('a11y-state', 'A11y State', function () {
                Permission::register('edit accessibility statement')
                    ->label('Edit the accessibility statement')
                    ->description('Change the profile, the disclosures, the conformance status and the known exceptions.');
                Permission::register('attest accessibility conformance')
                    ->label('Attest to full conformance')
                    ->description('Confirm, by name and date, that the site fully conforms. This is the only way a statement can say so, and the attestation is printed on it.');
                Permission::register('record accessibility evidence')
                    ->label('Record evidence')
                    ->description('Write records to the evidence log and acknowledge feedback. Records cannot be edited or deleted once written.');
            });
        });

        Utility::extend(fn () => Utility::register(
            Utility::make(self::UTILITY)
                ->title('A11y State')
                ->navTitle('A11y State')
                ->icon('document-certificate')
                ->description('The statement, whether it is complete, and the evidence behind it.')
                ->view('a11y-state::utilities.statement', fn (Request $request) => Http\Controllers\StatementPageData::for($request))
                ->routes(function ($router) {
                    $router->post('save', Http\Controllers\SaveStatementController::class)->name('save');
                    $router->post('attest', Http\Controllers\AttestController::class)->name('attest');
                    $router->get('preview', Http\Controllers\PreviewController::class)->name('preview');
                    $router->get('export/{format}', Http\Controllers\ExportStatementController::class)->name('export');
                    $router->get('evidence', Http\Controllers\EvidenceController::class)->name('evidence');
                    $router->post('evidence', Http\Controllers\AppendEvidenceController::class)->name('evidence.append');
                    $router->get('evidence/export/{format}', Http\Controllers\ExportEvidenceController::class)->name('evidence.export');
                    $router->get('feedback', Http\Controllers\FeedbackController::class)->name('feedback');
                    $router->post('feedback/acknowledge', Http\Controllers\AcknowledgeFeedbackController::class)->name('feedback.acknowledge');
                })
        ));
    }

    public function register()
    {
        parent::register();

        $this->app->singleton(Profiles::class, fn () => Profiles::fromArray((array) config('statamic-a11y-state.profiles', [])));
        $this->app->singleton(Rules::class, fn () => Rules::fromConfig((array) config('statamic-a11y-state', [])));
        $this->app->bind(Validator::class, fn ($app) => new Validator($app->make(Profiles::class), $app->make(Rules::class)));
        $this->app->bind(Generator::class, fn ($app) => new Generator($app->make(Profiles::class), $app->make(Validator::class)));

        $this->app->bind(StatementRepository::class, fn () => new YamlStatementRepository(self::path('statements', 'content/a11y-state/statements')));
        // The evidence log: YAML files by default, a database table when the
        // config file asks for one. Both refuse the same two things and
        // nothing else; see `EvidenceLog`.
        $this->app->bind(EvidenceLog::class, fn () => EvidenceDatabase::driver() === EvidenceDatabase::ELOQUENT
            ? EvidenceDatabase::log()
            : new YamlEvidenceLog(self::path('evidence', 'content/a11y-state/evidence')));

        // Every adapter, available or not. `FindingSources` asks each
        // whether its addon is installed and skips the ones that are not,
        // so with nothing else installed this is the null source in effect.
        $this->app->bind(FindingSource::class, fn ($app) => new FindingSources([
            new A11yReportSource,
        ]));

        $this->app->bind(FeedbackWindow::class, fn () => new FeedbackWindow(self::responseDays()));
        $this->app->bind(FormSubmissions::class, fn () => new FormSubmissions(config('statamic-a11y-state.feedback.form')));

        $this->app->bind(Statements::class, fn ($app) => new Statements(
            $app->make(StatementRepository::class),
            $app->make(EvidenceLog::class),
            $app->make(Profiles::class),
            $app->make(Rules::class),
            $app->make(Validator::class),
            $app->make(Generator::class),
            $app->make(FindingSource::class),
            (string) config('statamic-a11y-state.profile', 'w3c'),
            self::responseDays(),
        ));
    }

    /** A storage path from the config file, relative to the project root unless absolute. */
    public static function path(string $key, string $default): string
    {
        $configured = config("statamic-a11y-state.storage.{$key}");
        $path = is_string($configured) && $configured !== '' ? $configured : $default;

        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    public static function responseDays(): ?int
    {
        $days = config('statamic-a11y-state.feedback.response_days');

        return is_numeric($days) && (int) $days > 0 ? (int) $days : null;
    }
}
