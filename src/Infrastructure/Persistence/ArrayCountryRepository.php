<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Infrastructure\Persistence;
use Iriven\WorldDatasets\Domain\CountryInfo;
use Iriven\WorldDatasets\Domain\CurrencyCollection;
use Iriven\WorldDatasets\Domain\RegionCollection;
use Iriven\WorldDatasets\Application\WorldDatasets;
use Iriven\WorldDatasets\Application\Support\PhoneCodeNormalizer;
use Iriven\WorldDatasets\Application\Support\TldNormalizer;


use Iriven\WorldDatasets\Contract\CountryRepositoryInterface;

final class ArrayCountryRepository implements CountryRepositoryInterface
{
    /** @var array<int, CountryInfo> */
    private array $countries;

    /** @var array<string,CountryInfo> */
    private array $byAlpha2 = [];

    /** @var array<string,CountryInfo> */
    private array $byAlpha3 = [];

    /** @var array<string,CountryInfo> */
    private array $byNumeric = [];

    /** @var array<string,CountryInfo> */
    private array $byName = [];

    /**
     * @param array<int, CountryInfo> $countries
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

    /** @return array<int, CountryInfo> */
    public function findAll(): array
    {
        return $this->countries;
    }

    public function findOneByAlpha2(string $alpha2): ?CountryInfo
    {
        return $this->byAlpha2[strtoupper(trim($alpha2))] ?? null;
    }

    public function findOneByAlpha3(string $alpha3): ?CountryInfo
    {
        return $this->byAlpha3[strtoupper(trim($alpha3))] ?? null;
    }

    public function findOneByNumeric(string $numeric): ?CountryInfo
    {
        return $this->byNumeric[trim($numeric)] ?? null;
    }

    public function findOneByName(string $name): ?CountryInfo
    {
        return $this->byName[mb_strtolower(trim($name))] ?? null;
    }

    /** @return array<int, CountryInfo> */
    public function findByName(string $name): array
    {
        $country = $this->findOneByName($name);
        return $country === null ? [] : [$country];
    }

    /** @return array<int, CountryInfo> */
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

    /** @return array<int, CountryInfo> */
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

    /** @return array<int, CountryInfo> */
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

    /** @return array<int, CountryInfo> */
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

    /** @return array<int, CountryInfo> */
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

    /** @return array<string, string> */
    public function getAllCurrenciesCodeAndName(): array
    {
        return (new CurrencyCollection($this->countries))->list();
    }

    /** @return array<string, string> */
    public function getAllRegionsCodeAndName(): array
    {
        return (new RegionCollection($this->countries))->list();
    }

    /** @return array<string, array<string, string>> */
    public function getAllCountriesGroupedByRegions(): array
    {
        return (new RegionCollection($this->countries))->countries();
    }

    /** @return array<string, array<string, string>> */
    public function getAllCountriesGroupedByCurrencies(): array
    {
        return (new CurrencyCollection($this->countries))->countries();
    }
}
