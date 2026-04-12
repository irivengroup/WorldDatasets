<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests\Support;

use Iriven\WorldDatasets\Domain\CountryInfo;

trait CountryFactoryTrait
{
    protected function makeCountry(
        string $alpha2,
        string $alpha3,
        string $numeric,
        string $name,
        string $region = 'Europe',
        string $subRegion = 'Western Europe',
        string $currencyCode = 'EUR',
        string $currencyName = 'Euro',
        string $phoneCode = '33',
        string $tld = '.fr'
    ): CountryInfoInfo {
        return CountryInfo::fromDatabaseRow([
            'alpha2' => $alpha2,
            'alpha3' => $alpha3,
            'numeric_code' => $numeric,
            'country_name' => $name,
            'capital' => 'Capital',
            'tld' => $tld,
            'region_alpha_code' => 'EU',
            'region_num_code' => '150',
            'region_name' => $region,
            'sub_region_code' => '155',
            'sub_region_name' => $subRegion,
            'language' => 'fr',
            'currency_code' => $currencyCode,
            'currency_name' => $currencyName,
            'postal_code_pattern' => '',
            'phone_code' => $phoneCode,
            'intl_dialing_prefix' => '00',
            'natl_dialing_prefix' => '0',
            'subscriber_phone_pattern' => '',
        ]);
    }

    /**
     * @return array<int, CountryInfo>
     */
    protected function makeCountries(): array
    {
        return [
            $this->makeCountry('FR', 'FRA', '250', 'France', 'Europe', 'Western Europe', 'EUR', 'Euro', '33', '.fr'),
            $this->makeCountry('DE', 'DEU', '276', 'Germany', 'Europe', 'Western Europe', 'EUR', 'Euro', '49', '.de'),
            $this->makeCountry('US', 'USA', '840', 'United States', 'Americas', 'Northern America', 'USD', 'US Dollar', '1', '.us'),
            $this->makeCountry('JP', 'JPN', '392', 'Japan', 'Asia', 'Eastern Asia', 'JPY', 'Yen', '81', '.jp'),
        ];
    }
}
