<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\CountriesCollectionExporter;
use Iriven\WorldDatasets\Tests\Support\CountryFactoryTrait;
use PHPUnit\Framework\TestCase;

final class CountriesCollectionExporterTest extends TestCase
{
    use CountryFactoryTrait;

    public function testToApiArrayAndToStorageArray(): void
    {
        $exporter = new CountriesCollectionExporter();
        $countries = $this->makeCountries();

        $api = $exporter->toApiArray($countries);
        self::assertCount(4, $api);
        self::assertSame('FR', $api[0]['alpha2']);
        self::assertSame('France', $api[0]['country']);

        $storage = $exporter->toStorageArray($countries);
        self::assertCount(4, $storage);
        self::assertSame('FR', $storage[0][0]);
        self::assertSame('France', $storage[0][3]);
    }
}
