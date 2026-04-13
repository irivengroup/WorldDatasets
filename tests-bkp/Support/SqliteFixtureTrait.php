<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests\Support;

use PDO;

trait SqliteFixtureTrait
{
    protected function makeSqliteFixturePath(): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'wd_sqlite_fixture_');
        if ($tempPath === false) {
            self::fail('Unable to create sqlite temp file.');
        }

        $path = $tempPath . '.sqlite';

        if (!rename($tempPath, $path)) {
            self::fail('Unable to rename sqlite temp file.');
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec('CREATE TABLE countries (
            alpha2 TEXT,
            alpha3 TEXT,
            numeric_code TEXT,
            country_name TEXT,
            capital TEXT,
            tld TEXT,
            region_alpha_code TEXT,
            region_num_code TEXT,
            region_name TEXT,
            sub_region_code TEXT,
            sub_region_name TEXT,
            language TEXT,
            currency_code TEXT,
            currency_name TEXT,
            postal_code_pattern TEXT,
            phone_code TEXT,
            intl_dialing_prefix TEXT,
            natl_dialing_prefix TEXT,
            subscriber_phone_pattern TEXT
        )');

        $stmt = $pdo->prepare('INSERT INTO countries (
            alpha2, alpha3, numeric_code, country_name, capital, tld,
            region_alpha_code, region_num_code, region_name, sub_region_code, sub_region_name,
            language, currency_code, currency_name, postal_code_pattern, phone_code,
            intl_dialing_prefix, natl_dialing_prefix, subscriber_phone_pattern
        ) VALUES (
            :alpha2, :alpha3, :numeric_code, :country_name, :capital, :tld,
            :region_alpha_code, :region_num_code, :region_name, :sub_region_code, :sub_region_name,
            :language, :currency_code, :currency_name, :postal_code_pattern, :phone_code,
            :intl_dialing_prefix, :natl_dialing_prefix, :subscriber_phone_pattern
        )');

        $rows = [
            [
                'alpha2' => 'FR', 'alpha3' => 'FRA', 'numeric_code' => '250', 'country_name' => 'France',
                'capital' => 'Paris', 'tld' => '.fr', 'region_alpha_code' => 'EU', 'region_num_code' => '150',
                'region_name' => 'Europe', 'sub_region_code' => '155', 'sub_region_name' => 'Western Europe',
                'language' => 'fr', 'currency_code' => 'EUR', 'currency_name' => 'Euro', 'postal_code_pattern' => '',
                'phone_code' => '33', 'intl_dialing_prefix' => '00', 'natl_dialing_prefix' => '0', 'subscriber_phone_pattern' => '',
            ],
            [
                'alpha2' => 'US', 'alpha3' => 'USA', 'numeric_code' => '840', 'country_name' => 'United States',
                'capital' => 'Washington', 'tld' => '.us', 'region_alpha_code' => 'NA', 'region_num_code' => '019',
                'region_name' => 'Americas', 'sub_region_code' => '021', 'sub_region_name' => 'Northern America',
                'language' => 'en', 'currency_code' => 'USD', 'currency_name' => 'US Dollar', 'postal_code_pattern' => '',
                'phone_code' => '1', 'intl_dialing_prefix' => '011', 'natl_dialing_prefix' => '1', 'subscriber_phone_pattern' => '',
            ],
        ];

        foreach ($rows as $row) {
            $stmt->execute($row);
        }

        return $path;
    }

    protected function cleanupFile(string $path): void
    {
        if (is_file($path)) {
            self::assertTrue(unlink($path));
        }
    }
}