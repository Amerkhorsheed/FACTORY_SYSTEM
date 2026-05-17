<?php

namespace App\Services\Deployment;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

class PreflightCheckService
{
    /** @return array<int, array{name:string,status:string,message:string}> */
    public function run(bool $production = false, bool $runtime = false): array
    {
        return array_values(array_merge(
            $this->deploymentAssetChecks(),
            $this->applicationChecks($production),
            $this->phpExtensionChecks($production),
            $this->backupChecks($production),
            $this->scheduleChecks(),
            $runtime ? $this->runtimeChecks() : [],
        ));
    }

    /** @param array<int, array{name:string,status:string,message:string}> $checks */
    public function hasFailures(array $checks): bool
    {
        return collect($checks)->contains(fn (array $check): bool => $check['status'] === 'fail');
    }

    /** @return array{passed:int,warnings:int,failed:int,total:int} */
    public function summary(array $checks): array
    {
        return [
            'passed' => collect($checks)->where('status', 'pass')->count(),
            'warnings' => collect($checks)->where('status', 'warn')->count(),
            'failed' => collect($checks)->where('status', 'fail')->count(),
            'total' => count($checks),
        ];
    }

    /** @return array<int, array{name:string,status:string,message:string}> */
    private function deploymentAssetChecks(): array
    {
        return collect([
            '.env.production.example',
            'DEPLOYMENT.md',
            'LAUNCH_CHECKLIST.md',
            'deploy.sh',
            'nginx/factory.conf',
            'supervisor/factory.conf',
        ])->map(fn (string $path): array => $this->check(
            File::exists(base_path($path)),
            "asset: {$path}",
            'Deployment asset exists.',
            'Required deployment asset is missing.',
        ))->all();
    }

    /** @return array<int, array{name:string,status:string,message:string}> */
    private function applicationChecks(bool $production): array
    {
        return [
            $this->required(config('app.key'), 'APP_KEY', 'Application key is configured.', 'APP_KEY is missing.', $production),
            $this->productionEquals('APP_ENV', config('app.env'), 'production', $production),
            $this->productionEquals('APP_DEBUG', config('app.debug'), false, $production),
            $this->httpsUrl($production),
            $this->productionEquals('APP_LOCALE', config('app.locale'), 'ar', $production),
            $this->productionEquals('APP_FALLBACK_LOCALE', config('app.fallback_locale'), 'ar', $production),
            $this->productionEquals('DB_CONNECTION', config('database.default'), 'mysql', $production),
            $this->productionEquals('CACHE_STORE', config('cache.default'), 'redis', $production),
            $this->productionEquals('QUEUE_CONNECTION', config('queue.default'), 'redis', $production),
            $this->productionEquals('SESSION_DRIVER', config('session.driver'), 'redis', $production),
            $this->productionEquals('SESSION_SECURE_COOKIE', config('session.secure'), true, $production),
            $this->productionEquals('SESSION_ENCRYPT', config('session.encrypt'), true, $production),
            $this->productionNotIn('MAIL_MAILER', config('mail.default'), ['array', 'log'], $production),
            $this->required(config('mail.from.address'), 'MAIL_FROM_ADDRESS', 'Mail sender address is configured.', 'MAIL_FROM_ADDRESS is missing.', $production),
            $this->productionEquals('maintenance driver', config('app.maintenance.driver'), 'cache', $production),
            $this->routeClosureCheck(),
            $this->assetManifestCheck($production),
            $this->writablePath('storage', storage_path()),
            $this->writablePath('bootstrap/cache', base_path('bootstrap/cache')),
        ];
    }

    /** @return array<int, array{name:string,status:string,message:string}> */
    private function phpExtensionChecks(bool $production): array
    {
        $required = ['bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip'];
        $productionOnly = ['pcntl', 'pdo_mysql', 'redis'];

        return collect([
            $this->required(
                PHP_VERSION_ID >= 80300,
                'PHP version',
                'PHP 8.3 or newer is running.',
                'Production target requires PHP 8.3 or newer.',
                $production,
            ),
        ])->merge(collect($required)
            ->map(fn (string $extension): array => $this->check(
                extension_loaded($extension),
                "php extension: {$extension}",
                'Required PHP extension is loaded.',
                'Required PHP extension is missing.',
            ))
            ->merge(collect($productionOnly)->map(fn (string $extension): array => $this->required(
                extension_loaded($extension),
                "php extension: {$extension}",
                'Production PHP extension is loaded.',
                'Production PHP extension is missing.',
                $production,
            )))
        )
            ->all();
    }

    /** @return array<int, array{name:string,status:string,message:string}> */
    private function backupChecks(bool $production): array
    {
        return [
            $this->required(
                (new ExecutableFinder)->find('mysqldump') !== null,
                'executable: mysqldump',
                'mysqldump is available for database backups.',
                'mysqldump is missing; scheduled backups will fail.',
                $production,
            ),
            $this->writableDirectoryOrParent('storage/app/backups', storage_path('app/backups'), $production),
        ];
    }

    /** @return array<int, array{name:string,status:string,message:string}> */
    private function scheduleChecks(): array
    {
        $commands = array_keys(Artisan::all());
        $schedule = app(Schedule::class);
        $scheduled = collect($schedule->events())->map(fn ($event): string => (string) $event->command);

        return collect(['factory:overdue-alerts', 'factory:low-stock-check', 'factory:backup'])
            ->flatMap(fn (string $command): array => [
                $this->check(in_array($command, $commands, true), "command: {$command}", 'Artisan command is registered.', 'Artisan command is missing.'),
                $this->check($scheduled->contains(fn (string $value): bool => str_contains($value, $command)), "schedule: {$command}", 'Scheduled command is registered.', 'Scheduled command is missing.'),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{name:string,status:string,message:string}> */
    private function runtimeChecks(): array
    {
        return [
            $this->tryCheck('database connection', fn (): bool => (bool) DB::connection()->getPdo(), 'Database connection succeeded.'),
            $this->tryCheck('cache write/read', function (): bool {
                Cache::put('factory_preflight', 'ok', 30);
                $ok = Cache::get('factory_preflight') === 'ok';
                Cache::forget('factory_preflight');

                return $ok;
            }, 'Cache write/read succeeded.'),
            $this->tryCheck('redis ping', fn (): bool => (string) Redis::connection()->ping() !== '', 'Redis ping succeeded.'),
        ];
    }

    private function routeClosureCheck(): array
    {
        $closureRoutes = collect(Route::getRoutes())
            ->reject(fn (\Illuminate\Routing\Route $route): bool => $route->uri() === 'up')
            ->filter(fn (\Illuminate\Routing\Route $route): bool => ($route->getAction('uses') ?? null) instanceof Closure);

        return $this->check(
            $closureRoutes->isEmpty(),
            'route cache safety',
            'No closure routes detected.',
            'Closure routes detected; route cache will fail.',
        );
    }

    private function assetManifestCheck(bool $production): array
    {
        return $this->required(
            File::exists(public_path('build/manifest.json')),
            'frontend manifest',
            'Vite manifest exists.',
            'Vite manifest is missing. Run npm run build.',
            $production,
        );
    }

    private function httpsUrl(bool $production): array
    {
        $url = (string) config('app.url');

        return $this->required(
            str_starts_with($url, 'https://'),
            'APP_URL',
            'APP_URL uses HTTPS.',
            'APP_URL must use HTTPS for production.',
            $production,
        );
    }

    private function writablePath(string $name, string $path): array
    {
        return $this->check(
            File::isDirectory($path) && File::isWritable($path),
            "writable: {$name}",
            'Path is writable.',
            'Path is not writable.',
        );
    }

    private function writableDirectoryOrParent(string $name, string $path, bool $production): array
    {
        $parent = dirname($path);
        $writable = File::isDirectory($path)
            ? File::isWritable($path)
            : File::isDirectory($parent) && File::isWritable($parent);

        return $this->required(
            $writable,
            "writable: {$name}",
            'Backup directory exists or can be created.',
            'Backup directory is not writable and cannot be created.',
            $production,
        );
    }

    private function productionEquals(string $name, mixed $actual, mixed $expected, bool $production): array
    {
        return $this->required(
            $actual === $expected,
            $name,
            "Configured as {$this->stringValue($expected)}.",
            "Expected {$this->stringValue($expected)}, got {$this->stringValue($actual)}.",
            $production,
        );
    }

    /** @param array<int, mixed> $blocked */
    private function productionNotIn(string $name, mixed $actual, array $blocked, bool $production): array
    {
        return $this->required(
            ! in_array($actual, $blocked, true),
            $name,
            'Configured for real delivery.',
            "Production cannot use {$this->stringValue($actual)}.",
            $production,
        );
    }

    private function required(mixed $value, string $name, string $pass, string $fail, bool $required): array
    {
        $passed = (bool) $value;

        if ($passed) {
            return $this->result($name, 'pass', $pass);
        }

        return $this->result($name, $required ? 'fail' : 'warn', $fail);
    }

    private function tryCheck(string $name, callable $callback, string $pass): array
    {
        try {
            return $this->check($callback(), $name, $pass, 'Runtime check failed.');
        } catch (Throwable $e) {
            return $this->result($name, 'fail', $e->getMessage());
        }
    }

    private function check(bool $passed, string $name, string $pass, string $fail): array
    {
        return $this->result($name, $passed ? 'pass' : 'fail', $passed ? $pass : $fail);
    }

    private function result(string $name, string $status, string $message): array
    {
        return compact('name', 'status', 'message');
    }

    private function stringValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };
    }
}
