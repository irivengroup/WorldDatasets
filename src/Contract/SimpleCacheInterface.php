<?php

declare(strict_types=1);

namespace Iriven\Contract;

/**
 * Minimal PSR-16 compatible surface for optional runtime cache integration.
 */
interface SimpleCacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, null|int $ttl = null): bool;

    public function delete(string $key): bool;

    public function clear(): bool;

    public function has(string $key): bool;
}
