<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\SimpleCacheInterface;

final class Psr16FilesystemCache implements SimpleCacheInterface
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
        $path = $this->path($key);

        if (!is_file($path)) {
            return $default;
        }

        $content = file_get_contents($path);

        return $content === false ? $default : unserialize($content);
    }

    public function set(string $key, mixed $value, null|int $ttl = null): bool
    {
        return file_put_contents($this->path($key), serialize($value)) !== false;
    }

    public function delete(string $key): bool
    {
        $path = $this->path($key);

        return !is_file($path) || unlink($path);
    }

    public function clear(): bool
    {
        $ok = true;
        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            $ok = @unlink($file) && $ok;
        }

        return $ok;
    }

    public function has(string $key): bool
    {
        return is_file($this->path($key));
    }

    private function path(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.cache';
    }
}
