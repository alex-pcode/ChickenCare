<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Predis\Client;
use Throwable;

#[Signature('redis:check {--host=127.0.0.1} {--port=6379}')]
#[Description('Check whether Redis is available for this app (extension, package, and connectivity)')]
class RedisCheck extends Command
{
    public function handle(): int
    {
        $host = (string) $this->option('host');
        $port = (int) $this->option('port');

        $hasPhpRedis = extension_loaded('redis');
        $hasPredis = class_exists(Client::class);

        $this->line('phpredis extension : '.($hasPhpRedis ? '<info>loaded</info>' : '<comment>not loaded</comment>'));
        $this->line('predis package     : '.($hasPredis ? '<info>installed</info>' : '<comment>not installed</comment>'));

        if (! $hasPhpRedis && ! $hasPredis) {
            $this->newLine();
            $this->warn('No Redis client available. Install one first:');
            $this->line('  • phpredis: enable the "redis" PHP extension in Plesk PHP settings, or');
            $this->line('  • predis:   run  composer require predis/predis');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("Connecting to redis://{$host}:{$port} ...");

        $reachable = $hasPhpRedis
            ? $this->pingWithPhpRedis($host, $port)
            : $this->pingWithPredis($host, $port);

        if ($reachable) {
            $client = $hasPhpRedis ? 'phpredis' : 'predis';
            $this->info("✓ Redis server responded to PING (client: {$client}).");
            $this->newLine();
            $this->line('You can switch sessions/cache to Redis. Suggested .env:');
            $this->line('  REDIS_CLIENT='.$client);
            $this->line("  REDIS_HOST={$host}");
            $this->line("  REDIS_PORT={$port}");
            $this->line('  SESSION_DRIVER=redis');
            $this->line('  CACHE_STORE=redis');

            return self::SUCCESS;
        }

        $this->error("✗ Could not reach a Redis server at {$host}:{$port}.");
        $this->line('A client is available, but no Redis server is running there.');
        $this->line('Ask your host whether Redis is offered, try a different host/port, or use a hosted Redis.');

        return self::FAILURE;
    }

    private function pingWithPhpRedis(string $host, int $port): bool
    {
        try {
            $redis = new \Redis;
            $redis->connect($host, $port, 1.0);

            return (bool) $redis->ping();
        } catch (Throwable $e) {
            $this->line('  '.$e->getMessage());

            return false;
        }
    }

    private function pingWithPredis(string $host, int $port): bool
    {
        try {
            $client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'timeout' => 1.0,
            ]);
            $client->ping();

            return true;
        } catch (Throwable $e) {
            $this->line('  '.$e->getMessage());

            return false;
        }
    }
}
