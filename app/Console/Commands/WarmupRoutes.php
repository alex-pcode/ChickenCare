<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:warmup-routes {--base=http://127.0.0.1:8000}')]
#[Description('Hit the hot routes over HTTP so PHP opcache, view cache, and query plans are primed.')]
class WarmupRoutes extends Command
{
    /** @var array<int, string> */
    private array $routes = [
        '/app',
        '/app/eggs',
        '/app/flock',
        '/app/batches',
        '/app/expenses',
        '/app/feed',
        '/app/customers',
        '/app/sales',
    ];

    public function handle(): int
    {
        $base = rtrim($this->option('base'), '/');
        $slowest = 0;

        foreach ($this->routes as $path) {
            $start = microtime(true);

            $response = Http::withHeaders([
                'HX-Request' => 'true',
                'HX-Boosted' => 'true',
                'User-Agent' => 'ChickenCare-Warmup/1.0',
            ])->timeout(10)->get($base.$path);

            $ms = (int) ((microtime(true) - $start) * 1000);
            $slowest = max($slowest, $ms);

            $this->line(sprintf('  %-24s %4d ms  %s', $path, $ms, $response->status()));
        }

        $this->info(sprintf('Warmup complete. Slowest: %d ms', $slowest));

        return self::SUCCESS;
    }
}
