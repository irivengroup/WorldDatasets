<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\CountriesCollectionAggregator;
use Iriven\WorldDatasets\CountryCodeFormat;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;
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
        self::assertSame('France', $groupedByRegion['Europe']['FR']);

        $groupedByCurrency = $aggregator->groupByCurrency($countries);
        self::assertSame('Japan', $groupedByCurrency['JPY']['JP']);

        self::assertSame(['France', 'Germany', 'United States', 'Japan'], $aggregator->pluckNames($countries));
        self::assertSame(['FR', 'DE', 'US', 'JP'], $aggregator->pluckCodes($countries, CountryCodeFormat::ALPHA2));
        self::assertSame(['FRA', 'DEU', 'USA', 'JPN'], $aggregator->pluckCodes($countries, CountryCodeFormat::ALPHA3));
        self::assertSame(['250', '276', '840', '392'], $aggregator->pluckCodes($countries, CountryCodeFormat::NUMERIC));

        self::assertSame([
            'FR' => 'France',
            'DE' => 'Germany',
            'JP' => 'Japan',
            'US' => 'United States',
        ], $aggregator->list($countries, CountryCodeFormat::ALPHA2));
    }
}
