<?php

declare(strict_types=1);

namespace Bpmore\Grange\Tests;

use Bpmore\Grange\ServiceProvider;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

/**
 * A booted Statamic with this addon and nothing else of ours in it. That
 * is on purpose: the standalone case is the one the brief says must work,
 * and a suite that always had Accessibility Report present could not
 * prove it.
 */
abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected string $storage;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Testbench ships no app key, and Statamic's boot goes through the
        // session middleware, which encrypts.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Two users is a Pro feature, and the permission tests need a
        // second user to prove a refusal.
        $app['config']->set('statamic.editions.pro', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The statement and the log in a directory of this test's own,
        // named absolutely so the provider does not resolve it under the
        // harness's base path.
        $this->storage = sys_get_temp_dir().'/grange-feature-'.bin2hex(random_bytes(4));
        mkdir($this->storage, 0775, true);

        config()->set('statamic-grange.storage.statements', $this->storage.'/statements');
        config()->set('statamic-grange.storage.evidence', $this->storage.'/evidence');

        $this->forgetLeakedFixtures();
    }

    /**
     * Forms and roles are saved under the harness's resource path, not the
     * fake stache directory, so a test that makes one decides the answer
     * for every test after it. Removed before and after each.
     */
    private function forgetLeakedFixtures(): void
    {
        foreach (glob(resource_path('forms/*.yaml')) ?: [] as $file) {
            @unlink($file);
        }

        foreach (glob(resource_path('forms/submissions/*')) ?: [] as $dir) {
            removeDir($dir);
        }

        @unlink(resource_path('users/roles.yaml'));
    }

    protected function tearDown(): void
    {
        if (isset($this->storage) && is_dir($this->storage)) {
            removeDir($this->storage);
        }

        $this->forgetLeakedFixtures();

        parent::tearDown();
    }
}
