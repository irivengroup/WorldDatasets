<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\CountriesCollectionSorter;
use Iriven\WorldDatasets\CountryCodeFormat;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionSorterTest extends TestCase
{
    use CountryFactoryTrait;

    public function testSortByNameCodeAndNumeric(): void
    {
        $sorter = new CountriesCollectionSorter();
        $countries = array_reverse($this->makeCountries());

        $byName = $sorter->sortByName($countries);
        self::assertSame(['France', 'Germany', 'Japan', 'United States'], array_map(static fn($c) => $c->name(), $byName));

        $byAlpha2 = $sorter->sortByCode($countries, CountryCodeFormat::ALPHA2);
        self::assertSame(['DE', 'FR', 'JP', 'US'], array_map(static fn($c) => $c->alpha2(), $byAlpha2));

        $byAlpha3 = $sorter->sortByCode($countries, CountryCodeFormat::ALPHA3);
        self::assertSame(['DEU', 'FRA', 'JPN', 'USA'], array_map(static fn($c) => $c->alpha3(), $byAlpha3));

        $byNumeric = $sorter->sortByCode($countries, CountryCodeFormat::NUMERIC);
        self::assertSame(['250', '276', '392', '840'], array_map('strval', array_map(static fn($c) => $c->numeric(), $byNumeric)));

        $numeric = $sorter->sortByNumeric($countries);
        self::assertSame(['250', '276', '392', '840'], array_map('strval', array_map(static fn($c) => $c->numeric(), $numeric)));
    }
}
