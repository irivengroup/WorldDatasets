<?php

declare(strict_types=1);

namespace Iriven;

use Iriven\Contract\CountryRepositoryInterface;

final class ArrayCountryRepository implements CountryRepositoryInterface
{
    /** @var list<Country> */
    private array $countries;

    /** @var array<string,Country> */
    private array $byAlpha2 = [];

    /** @var array<string,Country> */
    private array $byAlpha3 = [];

    /** @var array<string,Country> */
    private array $byNumeric = [];

    /** @var array<string,Country> */
    private array $byName = [];

    /**
     * @param list<Country> $countries
     */
    public function __construct(array $countries)
    {
        $this->countries = array_values($countries);

        foreach ($this->countries as $country) {
            $this->byAlpha2[$country->alpha2()] = $country;
            $this->byAlpha3[$country->alpha3()] = $country;
            $this->byNumeric[$country->numeric()] = $country;
            $this->byName[mb_strtolower($country->name())] = $country;
        }
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
        return $this->byAlpha2[strtoupper(trim($alpha2))] ?? null;
    }

    public function findOneByAlpha3(string $alpha3): ?Country
    {
        return $this->byAlpha3[strtoupper(trim($alpha3))] ?? null;
    }

    public function findOneByNumeric(string $numeric): ?Country
    {
        return $this->byNumeric[trim($numeric)] ?? null;
    }

    public function findOneByName(string $name): ?Country
    {
        return $this->byName[mb_strtolower(trim($name))] ?? null;
    }

    public function findByName(string $name): array
    {
        $country = $this->findOneByName($name);
        return $country === null ? [] : [$country];
    }

    public function search(string $term): array
    {
        $needle = mb_strtolower(trim($term));
        $result = [];

        foreach ($this->countries as $country) {
            if (
                str_contains(mb_strtolower($country->name()), $needle)
                || str_contains(mb_strtolower($country->alpha2()), $needle)
                || str_contains(mb_strtolower($country->alpha3()), $needle)
                || str_contains(mb_strtolower($country->numeric()), $needle)
                || str_contains(mb_strtolower($country->currency()->code()), $needle)
                || str_contains(mb_strtolower($country->currency()->name()), $needle)
            ) {
                $result[] = $country;
            }
        }

        return $result;
    }

    public function findByCurrencyCode(string $currencyCode): array
    {
        $needle = strtoupper(trim($currencyCode));
        $result = [];

        foreach ($this->countries as $country) {
            if (strtoupper($country->currency()->code()) === $needle) {
                $result[] = $country;
            }
        }

        return $result;
    }

    public function findByRegion(string $region): array
    {
        $needle = mb_strtolower(trim($region));
        $result = [];

        foreach ($this->countries as $country) {
            if (mb_strtolower($country->region()->name()) === $needle) {
                $result[] = $country;
            }
        }

        return $result;
    }

    public function findByPhoneCode(string $phoneCode): array
    {
        $normalizer = new PhoneCodeNormalizer();
        $needle = $normalizer->normalize($phoneCode);
        $result = [];

        foreach ($this->countries as $country) {
            if ($normalizer->normalize($country->phone()->code()) === $needle) {
                $result[] = $country;
            }
        }

        return $result;
    }

    public function findByTld(string $tld): array
    {
        $normalizer = new TldNormalizer();
        $needle = $normalizer->normalize($tld);
        $result = [];

        foreach ($this->countries as $country) {
            if ($normalizer->normalize($country->tld()) === $needle) {
                $result[] = $country;
            }
        }

        return $result;
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
