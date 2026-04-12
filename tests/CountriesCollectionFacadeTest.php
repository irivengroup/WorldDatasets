<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;

use Iriven\WorldDatasets\Domain\CountriesCollection;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionFacadeTest extends TestCase
{
    use CountryFactoryTrait;

    public function testFacadeDelegatesCorrectly(): void
    {
        $collection = new CountriesCollection($this->makeCountries());

        self::assertSame(['FR', 'DE', 'US', 'JP'], $collection->pluckCodes());
        self::assertSame(['France', 'Germany', 'United States', 'Japan'], $collection->pluckNames());
        self::assertSame(['FRA', 'DEU', 'JPN', 'USA'], $collection->alpha3()->codes());
        self::assertSame([250, 276, 392, 840], $collection->numeric()->codes());

        self::assertCount(2, $collection->inRegion('Europe')->values());
        self::assertCount(1, $collection->withCurrency('USD')->values());
        self::assertCount(1, $collection->withTld('.jp')->values());
        self::assertCount(1, $collection->matching('united')->values());

        self::assertTrue($collection->contains('FRA'));
        self::assertFalse($collection->contains('ZZ'));

        self::assertSame('France', $collection->first()?->name());
        self::assertSame('Japan', $collection->last()?->name());

        self::assertSame(['DE', 'FR', 'JP', 'US'], $collection->sortByCode()->pluckCodes());
        self::assertCount(2, $collection->paginate(1, 2)->values());
        self::assertCount(2, $collection->chunk(2));

        self::assertArrayHasKey('Europe', $collection->groupByRegion());
        self::assertArrayHasKey('EUR', $collection->groupByCurrency());

        self::assertCount(4, $collection->exportArray());
        self::assertCount(4, $collection->toStorageArray());
        self::assertJson($collection->toJson());
        self::assertNotSame('', $collection->toCsv());
    }
}
