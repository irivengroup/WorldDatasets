<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;
use Iriven\WorldDatasets\Tests\Support\SqliteFixtureTrait;

use Iriven\WorldDatasets\Infrastructure\Persistence\CsvCountryRepository;
use Iriven\WorldDatasets\Infrastructure\Persistence\SqliteCountryRepository;
use Iriven\WorldDatasets\Infrastructure\Persistence\JsonCountryRepository;
use Iriven\WorldDatasets\Application\Factory\WorldDatasetsFactory;
use Iriven\WorldDatasets\Application\Config\WorldDatasetsRuntimeConfig;
use PHPUnit\Framework\TestCase;

final class WorldDatasetsFactoryIntegrationTest extends TestCase
{
    use SqliteFixtureTrait;

    public function testFactoryBuildsServiceFromSqlitePath(): void
    {
        $path = $this->makeSqliteFixturePath();

        try {
            $service = WorldDatasetsFactory::make($path);

            self::assertSame('France', $service->country('FR')->name());
            self::assertSame('sqlite', $service->meta()->source());
            self::assertSame(2, $service->count());
        } finally {
            $this->cleanupFile($path);
        }
    }

    public function testFactoryBuildsServiceFromConfig(): void
    {
        $path = $this->makeSqliteFixturePath();

        try {
            $service = WorldDatasetsFactory::fromConfig(new WorldDatasetsRuntimeConfig(sourcePath: $path));

            self::assertSame('United States', $service->country('USA')->name());
        } finally {
            $this->cleanupFile($path);
        }
    }

    public function testMakeRepositorySupportsKnownExtensions(): void
    {
        $sqlitePath = $this->makeSqliteFixturePath();
        $csvPath = tempnam(sys_get_temp_dir(), 'wd_csv_factory_');
        $jsonPath = tempnam(sys_get_temp_dir(), 'wd_json_factory_');

        if ($csvPath === false || $jsonPath === false) {
            self::fail('Unable to create temp files.');
        }

        file_put_contents($csvPath, implode(",", [
            'alpha2','alpha3','numeric_code','country_name','capital','tld',
            'region_alpha_code','region_num_code','region_name','sub_region_code',
            'sub_region_name','language','currency_code','currency_name',
            'postal_code_pattern','phone_code','intl_dialing_prefix',
            'natl_dialing_prefix','subscriber_phone_pattern'
        ]) . PHP_EOL . implode(",", [
            'FR','FRA','250','France','Paris','.fr','EU','150','Europe','155',
            'Western Europe','fr','EUR','Euro','','33','00','0',''
        ]) . PHP_EOL);

        file_put_contents($jsonPath, json_encode([[
            'alpha2' => 'FR',
            'alpha3' => 'FRA',
            'numeric_code' => '250',
            'country_name' => 'France',
            'capital' => 'Paris',
            'tld' => '.fr',
            'region_alpha_code' => 'EU',
            'region_num_code' => '150',
            'region_name' => 'Europe',
            'sub_region_code' => '155',
            'sub_region_name' => 'Western Europe',
            'language' => 'fr',
            'currency_code' => 'EUR',
            'currency_name' => 'Euro',
            'postal_code_pattern' => '',
            'phone_code' => '33',
            'intl_dialing_prefix' => '00',
            'natl_dialing_prefix' => '0',
            'subscriber_phone_pattern' => '',
        ]], JSON_THROW_ON_ERROR));

        $csvReal = $csvPath . '.csv';
        $jsonReal = $jsonPath . '.json';
        rename($csvPath, $csvReal);
        rename($jsonPath, $jsonReal);

        try {
            self::assertInstanceOf(SqliteCountryRepository::class, WorldDatasetsFactory::makeRepository($sqlitePath));
            self::assertInstanceOf(CsvCountryRepository::class, WorldDatasetsFactory::makeRepository($csvReal));
            self::assertInstanceOf(JsonCountryRepository::class, WorldDatasetsFactory::makeRepository($jsonReal));
        } finally {
            $this->cleanupFile($sqlitePath);
            $this->cleanupFile($csvReal);
            $this->cleanupFile($jsonReal);
        }
    }
}