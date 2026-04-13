<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests\Support;

use Iriven\WorldDatasets\Domain\CountryInfo;
use PHPUnit\Framework\TestCase;

final class CountryFactoryTraitTest extends TestCase
{
    use CountryFactoryTrait;


    public function testMakeCountryReturnsCountryInfo(): void
    {
        $country = $this->makeCountry('FR', 'FRA', '250', 'France');

        self::assertInstanceOf(CountryInfo::class, $country);
        self::assertSame('FR', $country->alpha2());
        self::assertSame('FRA', $country->alpha3());
        self::assertSame('250', $country->numeric());
        self::assertSame('France', $country->name());
    }

    public function testMakeCountriesReturnsExpectedFixtureSet(): void
    {
        $countries = $this->makeCountries();
        $countriesList = $countries;

        self::assertCount(4, $countriesList);
        self::assertContainsOnlyInstancesOf(CountryInfo::class, $countriesList);
        self::assertSame(['FR', 'DE', 'US', 'JP'], array_map(static fn (CountryInfo $country): string => $country->alpha2(), $countriesList));
    }
}
