<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\CountryRepositoryInterface;

final class ArrayCountryRepository implements CountryRepositoryInterface
{
    /**
     * @param list<Country> $countries
     */
    public function __construct(
        private readonly array $countries,
    ) {
    }

    public function count(): int
    {
        return count($this->countries);
    }

    public function findAll(): array
    {
        return $this->countries;
    }

    public function findOneByAlpha2(string $alpha2): ?Country
    {
        $needle = strtoupper(trim($alpha2));
        foreach ($this->countries as $country) {
            if ($country->alpha2() === $needle) {
                return $country;
            }
        }

        return null;
    }

    public function findOneByAlpha3(string $alpha3): ?Country
    {
        $needle = strtoupper(trim($alpha3));
        foreach ($this->countries as $country) {
            if ($country->alpha3() === $needle) {
                return $country;
            }
        }

        return null;
    }

    public function findOneByNumeric(string $numeric): ?Country
    {
        $needle = trim($numeric);
        foreach ($this->countries as $country) {
            if ($country->numeric() === $needle) {
                return $country;
            }
        }

        return null;
    }

    public function findOneByName(string $name): ?Country
    {
        $needle = mb_strtolower(trim($name));
        foreach ($this->countries as $country) {
            if (mb_strtolower($country->name()) === $needle) {
                return $country;
            }
        }

        return null;
    }

    public function findByName(string $name): array
    {
        $needle = mb_strtolower(trim($name));

        return array_values(array_filter(
            $this->countries,
            static fn(Country $country): bool => mb_strtolower($country->name()) === $needle
        ));
    }

    public function search(string $term): array
    {
        $needle = mb_strtolower(trim($term));

        return array_values(array_filter(
            $this->countries,
            static fn(Country $country): bool =>
                str_contains(mb_strtolower($country->name()), $needle)
                || str_contains(mb_strtolower($country->alpha2()), $needle)
                || str_contains(mb_strtolower($country->alpha3()), $needle)
                || str_contains(mb_strtolower($country->numeric()), $needle)
                || str_contains(mb_strtolower($country->currency()->code()), $needle)
                || str_contains(mb_strtolower($country->currency()->name()), $needle)
        ));
    }

    public function findByCurrencyCode(string $currencyCode): array
    {
        $needle = strtoupper(trim($currencyCode));

        return array_values(array_filter(
            $this->countries,
            static fn(Country $country): bool => strtoupper($country->currency()->code()) === $needle
        ));
    }

    public function findByRegion(string $region): array
    {
        $needle = mb_strtolower(trim($region));

        return array_values(array_filter(
            $this->countries,
            static fn(Country $country): bool => mb_strtolower($country->region()->name()) === $needle
        ));
    }

    public function findByPhoneCode(string $phoneCode): array
    {
        $needle = (new PhoneCodeNormalizer())->normalize($phoneCode);

        return array_values(array_filter(
            $this->countries,
            static fn(Country $country): bool => (new PhoneCodeNormalizer())->normalize($country->phone()->code()) === $needle
        ));
    }

    public function findByTld(string $tld): array
    {
        $needle = (new TldNormalizer())->normalize($tld);

        return array_values(array_filter(
            $this->countries,
            static fn(Country $country): bool => (new TldNormalizer())->normalize($country->tld()) === $needle
        ));
    }

    public function getAllCurrenciesCodeAndName(): array
    {
        return (new CurrenciesCollection($this->countries))->list();
    }

    public function getAllRegionsCodeAndName(): array
    {
        return (new RegionsCollection($this->countries))->list();
    }

    public function getAllCountriesGroupedByRegions(): array
    {
        return (new RegionsCollection($this->countries))->countries();
    }

    public function getAllCountriesGroupedByCurrencies(): array
    {
        return (new CurrenciesCollection($this->countries))->countries();
    }
}
