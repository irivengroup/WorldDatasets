<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\Infrastructure\Persistence\CsvCountryRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsvCountryRepositoryTest extends TestCase
{
    public function testConstructThrowsWhenFileMissing(): void
    {
        $this->expectException(RuntimeException::class);
        new CsvCountryRepository(__DIR__ . '/missing.csv');
    }

    public function testConstructLoadsCountriesFromCsv(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'wd_csv_');
        if ($path === false) {
            self::fail('Unable to create temp csv file.');
        }

        $csv = implode(",", [
            'alpha2','alpha3','numeric_code','country_name','capital','tld',
            'region_alpha_code','region_num_code','region_name','sub_region_code',
            'sub_region_name','language','currency_code','currency_name',
            'postal_code_pattern','phone_code','intl_dialing_prefix',
            'natl_dialing_prefix','subscriber_phone_pattern'
        ]) . PHP_EOL;

        $csv .= implode(",", [
            'FR','FRA','250','France','Paris','.fr','EU','150','Europe','155',
            'Western Europe','fr','EUR','Euro','','33','00','0',''
        ]) . PHP_EOL;

        file_put_contents($path, $csv);

        try {
            $repo = new CsvCountryRepository($path);
            self::assertSame(1, $repo->count());
            self::assertSame('France', $repo->findOneByAlpha2('FR')?->name());
        } finally {
            if (is_file($path)) {
                self::assertTrue(unlink($path));
            }
        }
    }
}