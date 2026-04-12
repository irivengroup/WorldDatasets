<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class CountriesCollectionSorter
{
    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function sortByName(array $countries): array
    {
        usort($countries, static fn(Country $a, Country $b): int => strcmp($a->name(), $b->name()));
        return $countries;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function sortByCode(array $countries, CountryCodeFormat $format): array
    {
        usort($countries, static function (Country $a, Country $b) use ($format): int {
            $left = match ($format) {
                CountryCodeFormat::ALPHA2 => $a->alpha2(),
                CountryCodeFormat::ALPHA3 => $a->alpha3(),
                CountryCodeFormat::NUMERIC => $a->numeric(),
            };
            $right = match ($format) {
                CountryCodeFormat::ALPHA2 => $b->alpha2(),
                CountryCodeFormat::ALPHA3 => $b->alpha3(),
                CountryCodeFormat::NUMERIC => $b->numeric(),
            };
            return strcmp($left, $right);
        });
        return $countries;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function sortByNumeric(array $countries): array
    {
        usort($countries, static fn(Country $a, Country $b): int => strcmp($a->numeric(), $b->numeric()));
        return $countries;
    }
}
