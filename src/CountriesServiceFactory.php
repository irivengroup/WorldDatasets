<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\CountryRepositoryInterface;
use Iriven\Contract\SimpleCacheInterface;
use Iriven\Infrastructure\Cache\ArrayCache;
use Iriven\Infrastructure\Persistence\SqliteCountryRepository;
use Iriven\Support\NullLogger;
use InvalidArgumentException;
use RuntimeException;

final class CountriesServiceFactory
{
    public static function make(?string $sourcePath = null): Countries
    {
        return self::fromConfig(new CountriesRuntimeConfig(sourcePath: $sourcePath));
    }

    public static function fromConfig(CountriesRuntimeConfig $config, ?SimpleCacheInterface $cache = null): Countries
    {
        $path = $config->sourcePath() ?? self::defaultSqlitePath();

        if ($config->verifyChecksum()) {
            self::assertChecksum($path);
        }

        $repository = self::makeRepository($path, $cache);

        $service = new Countries(
            $repository,
            datasetSource: self::detectSourceName($path),
            datasetVersion: self::datasetVersion(),
            datasetChecksum: self::checksumFor($path),
            datasetBuiltAt: self::builtAt(),
        );

        if ($config->strictValidation()) {
            (new DatasetValidator())->validate($service->countries()->values(), true);
        }

        return $service;
    }

    public static function makeWithValidation(?string $sourcePath = null): Countries
    {
        return self::fromConfig(new CountriesRuntimeConfig(
            sourcePath: $sourcePath,
            verifyChecksum: true,
            strictValidation: true,
        ));
    }

    public static function makeRepository(string $path, ?SimpleCacheInterface $cache = null): CountryRepositoryInterface
    {
        $filename = basename($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'json',
            $extension === 'jsonn',
            str_ends_with($filename, '.json'),
            str_ends_with($filename, '.jsonn') => new JsonCountryRepository($path),

            $extension === 'csv',
            str_ends_with($filename, '.csv') => new CsvCountryRepository($path),

            $extension === 'sqlite',
            $extension === 'db',
            str_ends_with($filename, '.sqlite'),
            str_ends_with($filename, '.db') => SqliteCountryRepository::fromSqliteFile(
                $path,
                new ArrayCache(),
                new NullLogger()
            ),

            default => throw new InvalidArgumentException(sprintf('Unsupported data source extension: %s', $extension)),
        };
    }

    public static function defaultSqlitePath(): string
    {
        return __DIR__ . '/data/' . DataSource::SQLITE;
    }

    public static function defaultJsonPath(): string
    {
        return __DIR__ . '/data/' . DataSource::JSON;
    }

    public static function defaultCsvPath(): string
    {
        return __DIR__ . '/data/' . DataSource::CSV;
    }

    public static function datasetMetaPath(): string
    {
        return __DIR__ . '/data/.countriesRepository.meta.json';
    }

    public static function datasetShaPath(): string
    {
        return __DIR__ . '/data/.countriesRepository.sha256';
    }

    public static function datasetVersion(): string
    {
        $metaPath = self::datasetMetaPath();
        if (!is_file($metaPath)) {
            return 'unknown';
        }

        $data = json_decode((string) file_get_contents($metaPath), true);

        return is_array($data) ? (string) ($data['dataset_version'] ?? 'unknown') : 'unknown';
    }

    public static function builtAt(): ?string
    {
        $metaPath = self::datasetMetaPath();
        if (!is_file($metaPath)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($metaPath), true);

        return is_array($data) ? ($data['built_at'] ?? null) : null;
    }

    public static function checksumFor(string $path): ?string
    {
        return is_file($path) ? hash_file('sha256', $path) ?: null : null;
    }

    public static function assertChecksum(string $path): void
    {
        $metaPath = self::datasetMetaPath();
        if (!is_file($metaPath) || !is_file($path)) {
            throw new RuntimeException('Missing dataset metadata or source file.');
        }

        $data = json_decode((string) file_get_contents($metaPath), true);
        if (!is_array($data) || !isset($data['checksums'][basename($path)])) {
            throw new RuntimeException('Missing checksum entry for source file.');
        }

        $expected = (string) $data['checksums'][basename($path)];
        $actual = self::checksumFor($path);

        if ($actual !== $expected) {
            throw new RuntimeException(sprintf('Checksum mismatch for %s.', basename($path)));
        }
    }

    private static function detectSourceName(string $path): string
    {
        $filename = basename($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'json',
            $extension === 'jsonn',
            str_ends_with($filename, '.json'),
            str_ends_with($filename, '.jsonn') => 'json',

            $extension === 'csv',
            str_ends_with($filename, '.csv') => 'csv',

            $extension === 'sqlite',
            $extension === 'db',
            str_ends_with($filename, '.sqlite'),
            str_ends_with($filename, '.db') => 'sqlite',

            default => 'unknown',
        };
    }
}
