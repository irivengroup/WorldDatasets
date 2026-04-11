<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Iriven\WorldDatasets\Infrastructure\Cache\CacheInterface;

final class FilesystemCache implements CacheInterface
{
    public function __construct(
        private readonly string $directory,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertKey($key);
        $path = $this->path($key);
        if (!is_file($path)) {
            return $default;
        }

        $payload = @unserialize((string) file_get_contents($path));
        if (!is_array($payload)) {
            return $default;
        }

        $expiresAt = $payload['expires_at'] ?? null;
        if (is_int($expiresAt) && $expiresAt < time()) {
            $this->delete($key);
            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertKey($key);
        $payload = [
            'expires_at' => $this->normalizeTtl($ttl),
            'value' => $value,
        ];

        return file_put_contents($this->path($key), serialize($payload)) !== false;
    }

    public function delete(string $key): bool
    {
        $this->assertKey($key);
        $path = $this->path($key);

        return !is_file($path) || unlink($path);
    }

    public function clear(): bool
    {
        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            if (is_file($file) && !@unlink($file)) {
                return false;
            }
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[(string) $key] = $this->get((string) $key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!$this->set((string) $key, $value, $ttl)) {
                return false;
            }
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete((string) $key)) {
                return false;
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->assertKey($key);
        $path = $this->path($key);

        if (!is_file($path)) {
            return false;
        }

        $payload = @unserialize((string) file_get_contents($path));
        if (!is_array($payload)) {
            return false;
        }

        $expiresAt = $payload['expires_at'] ?? null;
        if (is_int($expiresAt) && $expiresAt < time()) {
            $this->delete($key);
            return false;
        }

        return array_key_exists('value', $payload);
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.cache';
    }

    private function assertKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key cannot be empty.');
        }
    }

    private function normalizeTtl(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return time() + $ttl;
        }

        return (new DateTimeImmutable())->add($ttl)->getTimestamp();
    }
}
