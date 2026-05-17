<?php

namespace App\Console\Commands;

use App\Services\Deployment\PreflightCheckService;
use Illuminate\Console\Command;

class RunProductionPreflight extends Command
{
    protected $signature = 'factory:preflight
        {--production : Enforce production launch requirements}
        {--runtime : Check live database, cache, and Redis connectivity}
        {--json : Output the report as JSON}';

    protected $description = 'Run final launch preflight checks';

    public function __construct(
        private readonly PreflightCheckService $preflight,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $checks = $this->preflight->run(
            production: (bool) $this->option('production'),
            runtime: (bool) $this->option('runtime'),
        );
        $summary = $this->preflight->summary($checks);

        if ($this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->newLine();
            $this->info('Factory System Launch Preflight');
            $this->table(
                ['Status', 'Check', 'Message'],
                collect($checks)->map(fn (array $check): array => [
                    strtoupper($check['status']),
                    $check['name'],
                    $check['message'],
                ])->all()
            );
            $this->line("Passed: {$summary['passed']}  Warnings: {$summary['warnings']}  Failed: {$summary['failed']}  Total: {$summary['total']}");
        }

        return $this->preflight->hasFailures($checks) ? self::FAILURE : self::SUCCESS;
    }
}
