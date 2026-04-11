<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\CountryRepositoryInterface;
use Iriven\Infrastructure\Cache\ArrayCache;
use Iriven\Infrastructure\Persistence\SqliteCountryRepository;
use Iriven\Support\NullLogger;
use InvalidArgumentException;

final class CountriesServiceFactory
{
    public static function make(?string $sourceFilePath = null): Countries
    {
        $path = $sourceFilePath ?? __DIR__ . '/data/.countriesRepository.sqlite';

        $repository = self::makeRepository($path);

        return new Countries($repository, datasetSource: self::detectSourceName($path));
    }

    public static function makeRepository(string $path): CountryRepositoryInterface
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
