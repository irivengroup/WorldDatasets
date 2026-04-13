<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;

use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionSequence;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountryCodeFormat;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionSequenceTest extends TestCase
{
    use CountryFactoryTrait;


    public function testSequenceMethods(): void
    {
        $sequence = new CountriesCollectionSequence();
        $countries = $this->makeCountries();

        $chunks = $sequence->chunk($countries, 2, CountryCodeFormat::ALPHA2);
        self::assertCount(2, $chunks);
        self::assertSame(['FR', 'DE'], $chunks[0]->pluckCodes());

        self::assertSame(
            ['France', 'Germany', 'United States', 'Japan'],
            $sequence->map($countries, static fn($country) => $country->name())
        );

        $filtered = $sequence->filter($countries, static fn($country) => $country->region()->name() === 'Europe', CountryCodeFormat::ALPHA2);
        self::assertCount(2, $filtered->values());

        $reduced = $sequence->reduce($countries, static fn(int $carry, $country): int => $carry + strlen($country->alpha2()), 0);
        self::assertSame(8, $reduced);

        $paginated = $sequence->paginate($countries, 1, 2, CountryCodeFormat::ALPHA2);
        self::assertSame(['DE', 'US'], $paginated->pluckCodes());
    }
}
