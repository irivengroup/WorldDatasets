<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class CountriesCollectionAggregator
{
    /**
     * @param array<int, Country> $countries
     */
    public function stats(array $countries): WorldDatasetsStats
    {
        $regions = [];
        $currencies = [];
        foreach ($countries as $country) {
            if ($country->region()->name() !== '') {
                $regions[$country->region()->name()] = true;
            }
            if ($country->currency()->code() !== '') {
                $currencies[$country->currency()->code()] = true;
            }
        }

        return new WorldDatasetsStats(count($countries), count($regions), count($currencies));
    }

    /**
     * @param array<int, Country> $countries
     * @return array<string, array<string, string>>
     */
    public function groupByRegion(array $countries): array
    {
        $result = [];
        foreach ($countries as $country) {
            $result[$country->region()->name()][$country->alpha2()] = $country->name();
        }
        ksort($result);

        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<string, array<string, string>>
     */
    public function groupByCurrency(array $countries): array
    {
        $result = [];
        foreach ($countries as $country) {
            $result[$country->currency()->code()][$country->alpha2()] = $country->name();
        }
        ksort($result);

        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, string>
     */
    public function pluckNames(array $countries): array
    {
        return array_values(array_map(static fn(Country $country): string => $country->name(), $countries));
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, string>
     */
    public function pluckCodes(array $countries, CountryCodeFormat $format): array
    {
        return array_values(array_map(static function (Country $country) use ($format): string {
            return match ($format) {
                CountryCodeFormat::ALPHA2 => $country->alpha2(),
                CountryCodeFormat::ALPHA3 => $country->alpha3(),
                CountryCodeFormat::NUMERIC => $country->numeric(),
            };
        }, $countries));
    }

    /**
     * @param array<int, Country> $countries
     * @return array<string, string>
     */
    public function list(array $countries, CountryCodeFormat $format): array
    {
        $result = [];
        foreach ($countries as $country) {
            $key = match ($format) {
                CountryCodeFormat::ALPHA2 => $country->alpha2(),
                CountryCodeFormat::ALPHA3 => $country->alpha3(),
                CountryCodeFormat::NUMERIC => $country->numeric(),
            };
            $result[$key] = $country->name();
        }
        asort($result);

        return $result;
    }
}
