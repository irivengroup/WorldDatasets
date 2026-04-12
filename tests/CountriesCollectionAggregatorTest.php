<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;

use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionAggregator;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountryCodeFormat;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionAggregatorTest extends TestCase
{
    use CountryFactoryTrait;

    public function testAggregationMethods(): void
    {
        $aggregator = new CountriesCollectionAggregator();
        $countries = $this->makeCountries();

        $stats = $aggregator->stats($countries);
        self::assertSame(4, $stats->total());
        self::assertSame(3, $stats->regions());
        self::assertSame(3, $stats->currencies());

        $groupedByRegion = $aggregator->groupByRegion($countries);
        self::assertArrayHasKey('Europe', $groupedByRegion);
        self::assertArrayHasKey('FR', $groupedByRegion['Europe']);
        self::assertContains('France', $groupedByRegion['Europe']);

        $groupedByCurrency = $aggregator->groupByCurrency($countries);
        self::assertArrayHasKey('JPY', $groupedByCurrency);
        self::assertArrayHasKey('JP', $groupedByCurrency['JPY']);
        self::assertContains('Japan', $groupedByCurrency['JPY']);

        self::assertSame(['France', 'Germany', 'United States', 'Japan'], $aggregator->pluckNames($countries));
        self::assertSame(['FR', 'DE', 'US', 'JP'], $aggregator->pluckCodes($countries, CountryCodeFormat::ALPHA2));
        self::assertSame(['FRA', 'DEU', 'USA', 'JPN'], $aggregator->pluckCodes($countries, CountryCodeFormat::ALPHA3));
        self::assertSame(['250', '276', '840', '392'], array_map('strval', $aggregator->pluckCodes($countries, CountryCodeFormat::NUMERIC)));

        self::assertSame([
            'FR' => 'France',
            'DE' => 'Germany',
            'JP' => 'Japan',
            'US' => 'United States',
        ], $aggregator->list($countries, CountryCodeFormat::ALPHA2));
    }
}
