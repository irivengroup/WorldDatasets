<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\Infrastructure\Persistence\SqliteCountryHydrator;
use PHPUnit\Framework\TestCase;

final class SqliteCountryHydratorTest extends TestCase
{
    public function testHydrateAndHydrateMany(): void
    {
        $hydrator = new SqliteCountryHydrator();

        $row = [
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
        ];

        $country = $hydrator->hydrate($row);
        self::assertSame('France', $country->name());
        self::assertSame('FR', $country->alpha2());

        $many = $hydrator->hydrateMany([$row, $row]);
        self::assertCount(2, $many);
        self::assertSame('FRA', $many[1]->alpha3());
    }
}