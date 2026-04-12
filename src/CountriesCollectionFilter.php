<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class CountriesCollectionFilter
{
    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function inRegion(array $countries, string $name): array
    {
        $needle = mb_strtolower(trim($name));
        $result = [];
        foreach ($countries as $country) {
            if (mb_strtolower($country->region()->name()) === $needle) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function inSubRegion(array $countries, string $name): array
    {
        $needle = mb_strtolower(trim($name));
        $result = [];
        foreach ($countries as $country) {
            if (mb_strtolower($country->region()->subRegion()->name()) === $needle) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function withCurrency(array $countries, string $code): array
    {
        $needle = strtoupper(trim($code));
        $result = [];
        foreach ($countries as $country) {
            if (strtoupper($country->currency()->code()) === $needle) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function withPhoneCode(array $countries, string $code): array
    {
        $normalizer = new PhoneCodeNormalizer();
        $needle = $normalizer->normalize($code);
        $result = [];
        foreach ($countries as $country) {
            if ($normalizer->normalize($country->phone()->code()) === $needle) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function withTld(array $countries, string $tld): array
    {
        $normalizer = new TldNormalizer();
        $needle = $normalizer->normalize($tld);
        $result = [];
        foreach ($countries as $country) {
            if ($normalizer->normalize($country->tld()) === $needle) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function named(array $countries, string $name): array
    {
        $needle = mb_strtolower(trim($name));
        $result = [];
        foreach ($countries as $country) {
            if (mb_strtolower($country->name()) === $needle) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, Country>
     */
    public function matching(array $countries, string $term): array
    {
        $needle = mb_strtolower(trim($term));
        $result = [];
        foreach ($countries as $country) {
            if (
                str_contains(mb_strtolower($country->name()), $needle)
                || str_contains(mb_strtolower($country->alpha2()), $needle)
                || str_contains(mb_strtolower($country->alpha3()), $needle)
                || str_contains(mb_strtolower($country->numeric()), $needle)
            ) {
                $result[] = $country;
            }
        }
        return $result;
    }

    /**
     * @param array<int, Country> $countries
     */
    public function contains(array $countries, string $code): bool
    {
        $code = trim($code);
        foreach ($countries as $country) {
            if ($country->alpha2() === strtoupper($code) || $country->alpha3() === strtoupper($code) || $country->numeric() === $code) {
                return true;
            }
        }
        return false;
    }
}
