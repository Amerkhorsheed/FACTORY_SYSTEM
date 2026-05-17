<?php

namespace Tests\Feature;

use App\Services\Deployment\PreflightCheckService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    /** @test */
    public function production_cache_commands_can_be_warmed(): void
    {
        try {
            $this->assertSame(0, Artisan::call('config:cache'));
            $this->assertSame(0, Artisan::call('route:cache'));
            $this->assertSame(0, Artisan::call('view:cache'));
            $this->assertSame(0, Artisan::call('event:cache'));
        } finally {
            Artisan::call('optimize:clear');
        }
    }

    /** @test */
    public function deployment_assets_are_present(): void
    {
        foreach ($this->deploymentFiles() as $file) {
            $this->assertFileExists(base_path($file));
        }
    }

    /** @test */
    public function local_preflight_command_reports_no_failures(): void
    {
        $this->assertSame(0, Artisan::call('factory:preflight', ['--json' => true]));

        $report = json_decode(Artisan::output(), true);

        $this->assertSame(0, $report['summary']['failed']);
        $this->assertGreaterThan(0, $report['summary']['total']);
    }

    /** @test */
    public function production_preflight_fails_for_insecure_settings(): void
    {
        config([
            'app.debug' => true,
            'app.env' => 'production',
            'app.url' => 'http://factory.test',
        ]);

        $this->assertSame(1, Artisan::call('factory:preflight', [
            '--json' => true,
            '--production' => true,
        ]));

        $checks = collect(json_decode(Artisan::output(), true)['checks']);

        $this->assertSame('fail', $checks->firstWhere('name', 'APP_DEBUG')['status']);
        $this->assertSame('fail', $checks->firstWhere('name', 'APP_URL')['status']);
    }

    /** @test */
    public function preflight_service_returns_summary_counts(): void
    {
        $service = app(PreflightCheckService::class);
        $checks = $service->run();
        $summary = $service->summary($checks);

        $this->assertArrayHasKey('passed', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        $this->assertArrayHasKey('failed', $summary);
        $this->assertSame(count($checks), $summary['total']);
    }

    /** @test */
    public function preflight_reports_backup_readiness(): void
    {
        $checks = collect(app(PreflightCheckService::class)->run());

        $this->assertNotNull($checks->firstWhere('name', 'executable: mysqldump'));
        $this->assertNotNull($checks->firstWhere('name', 'writable: storage/app/backups'));
    }

    /** @return array<int, string> */
    private function deploymentFiles(): array
    {
        return [
            '.env.production.example',
            'DEPLOYMENT.md',
            'LAUNCH_CHECKLIST.md',
            'deploy.sh',
            'nginx/factory.conf',
            'supervisor/factory.conf',
        ];
    }
}
