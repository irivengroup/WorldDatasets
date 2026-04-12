<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Domain\CountryInfoFactoryTrait;

use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionCache;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionReadModel;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountryCodeFormat;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionReadModelTest extends TestCase
{
    use CountryFactoryTrait;

    public function testReadModelMethods(): void
    {
        $reader = new CountriesCollectionReadModel();
        $cache = new CountriesCollectionCache();
        $countries = $this->makeCountries();

        self::assertSame('France', $reader->first($countries)?->name());
        self::assertSame('Japan', $reader->last($countries)?->name());
        self::assertCount(4, $reader->values($countries));
        self::assertSame(4, $reader->count($countries));
        self::assertFalse($reader->isEmpty($countries));
        self::assertTrue($reader->isNotEmpty($countries));
        self::assertTrue($reader->isEmpty([]));

        $names = $reader->names($countries, CountryCodeFormat::ALPHA2, $cache);
        self::assertArrayHasKey('FR', $names);
        self::assertContains('France', $names);

        $codes = $reader->codes($countries, CountryCodeFormat::ALPHA3, new CountriesCollectionCache());
        self::assertSame(['FRA', 'DEU', 'JPN', 'USA'], $codes);

        $list = $reader->list($countries, CountryCodeFormat::NUMERIC, new CountriesCollectionCache());
        self::assertArrayHasKey('392', $list);
        self::assertContains('Japan', $list);

        self::assertSame(['France', 'Germany', 'United States', 'Japan'], $reader->pluckNames($countries));
        self::assertSame(['FR', 'DE', 'US', 'JP'], $reader->pluckCodes($countries, CountryCodeFormat::ALPHA2));

        self::assertTrue($reader->containsCountry($countries, 'FR'));
        self::assertTrue($reader->containsCountry($countries, $countries[0]));
        self::assertTrue($reader->containsCountry($countries, static fn($country): bool => $country->currency()->code() === 'JPY'));
        self::assertFalse($reader->containsCountry($countries, static fn($country): bool => $country->alpha2() === 'ZZ'));
    }
}
