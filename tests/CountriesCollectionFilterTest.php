<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;

use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionFilter;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionFilterTest extends TestCase
{

    public function testFilteringMethods(): void
    {
        $filter = new CountriesCollectionFilter();
        $countries = $this->makeCountries();

        self::assertCount(2, $filter->inRegion($countries, 'Europe'));
        self::assertCount(1, $filter->inSubRegion($countries, 'Eastern Asia'));
        self::assertCount(1, $filter->withCurrency($countries, 'usd'));
        self::assertCount(1, $filter->withPhoneCode($countries, '+33'));
        self::assertCount(1, $filter->withTld($countries, 'JP'));
        self::assertCount(1, $filter->named($countries, 'france'));
        self::assertCount(1, $filter->matching($countries, 'fra'));
        self::assertTrue($filter->contains($countries, 'FR'));
        self::assertTrue($filter->contains($countries, 'fra'));
        self::assertTrue($filter->contains($countries, '250'));
        self::assertFalse($filter->contains($countries, 'ZZ'));
    }
}
