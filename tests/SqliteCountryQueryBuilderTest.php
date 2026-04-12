<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\Infrastructure\Persistence\SqliteCountryQueryBuilder;
use PHPUnit\Framework\TestCase;

final class SqliteCountryQueryBuilderTest extends TestCase
{
    public function testBuildsExpectedQueries(): void
    {
        $builder = new SqliteCountryQueryBuilder();

        self::assertStringContainsString('SELECT COUNT(*) FROM countries', $builder->countAll());
        self::assertStringContainsString('ORDER BY country_name ASC', $builder->selectAllOrdered());
        self::assertStringContainsString('WHERE LOWER(country_name) = LOWER(:name)', $builder->findByName());
        self::assertStringContainsString('LIKE :term', $builder->search());
        self::assertStringContainsString('LIMIT :limit OFFSET :offset', $builder->iterateAllLazy());
        self::assertStringContainsString('WHERE region_name = :region', $builder->iterateByRegionLazy());
        self::assertStringContainsString('WHERE alpha2 = :value', $builder->findByExactColumn('alpha2'));
        self::assertStringContainsString('LIMIT 1', $builder->findOneByColumn('alpha2'));
        self::assertStringContainsString('GROUP BY currency_code, currency_name', $builder->currenciesMap());
        self::assertStringContainsString('GROUP BY region_num_code, region_name', $builder->regionsMap());
        self::assertStringContainsString('SELECT region_name, alpha2, country_name', $builder->countriesGroupedByRegions());
        self::assertStringContainsString('SELECT currency_code, alpha2, country_name', $builder->countriesGroupedByCurrencies());
    }
}
