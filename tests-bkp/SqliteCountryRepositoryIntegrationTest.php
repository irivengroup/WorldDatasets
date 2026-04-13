<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\SqliteFixtureTrait;

use Iriven\WorldDatasets\Infrastructure\Cache\ArrayCache;
use Iriven\WorldDatasets\Infrastructure\Persistence\SqliteCountryRepository;
use PHPUnit\Framework\TestCase;

final class SqliteCountryRepositoryIntegrationTest extends TestCase
{
    use SqliteFixtureTrait;

    public function testRepositoryQueriesWorkAgainstSqliteFixture(): void
    {
        $path = $this->makeSqliteFixturePath();

        try {
            $repo = SqliteCountryRepository::fromSqliteFile($path, new ArrayCache());

            self::assertSame(2, $repo->count());
            self::assertSame('France', $repo->findOneByAlpha2('FR')?->name());
            self::assertSame('France', $repo->findOneByAlpha3('FRA')?->name());
            self::assertSame('France', $repo->findOneByNumeric('250')?->name());
            self::assertSame('France', $repo->findOneByName('France')?->name());

            self::assertCount(1, $repo->findByName('France'));
            self::assertCount(1, $repo->search('united'));
            self::assertCount(1, $repo->findByCurrencyCode('usd'));
            self::assertCount(1, $repo->findByRegion('Europe'));
            self::assertCount(1, $repo->findByPhoneCode('33'));
            self::assertCount(1, $repo->findByTld('.us'));

            $currencies = $repo->getAllCurrenciesCodeAndName();
            self::assertArrayHasKey('EUR', $currencies);

            $regions = $repo->getAllRegionsCodeAndName();
            self::assertArrayHasKey('150', $regions);

            self::assertArrayHasKey('Europe', $repo->getAllCountriesGroupedByRegions());
            self::assertArrayHasKey('USD', $repo->getAllCountriesGroupedByCurrencies());

            $lazyAll = iterator_to_array($repo->iterateAllLazy(1), false);
            self::assertCount(2, $lazyAll);

            $lazyRegion = iterator_to_array($repo->iterateByRegionLazy('Europe', 1), false);
            self::assertCount(1, $lazyRegion);
        } finally {
            $this->cleanupFile($path);
        }
    }
}