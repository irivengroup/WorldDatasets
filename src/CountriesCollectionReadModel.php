<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class CountriesCollectionReadModel
{
    public function __construct(
        private readonly CountriesCollectionAggregator $aggregator = new CountriesCollectionAggregator(),
        private readonly CountriesCollectionFilter $filterer = new CountriesCollectionFilter(),
    ) {
    }

    /** @param array<int, Country> $countries */
    public function first(array $countries): ?Country
    {
        return $countries[0] ?? null;
    }

    /** @param array<int, Country> $countries */
    public function last(array $countries): ?Country
    {
        return $countries === [] ? null : $countries[array_key_last($countries)];
    }

    /** @param array<int, Country> $countries
     *  @return array<int, Country>
     */
    public function values(array $countries): array
    {
        return array_values($countries);
    }

    /** @param array<int, Country> $countries */
    public function count(array $countries): int
    {
        return count($countries);
    }

    /** @param array<int, Country> $countries */
    public function isEmpty(array $countries): bool
    {
        return $countries === [];
    }

    /** @param array<int, Country> $countries */
    public function isNotEmpty(array $countries): bool
    {
        return !$this->isEmpty($countries);
    }

    /** @param array<int, Country> $countries */
    public function contains(array $countries, string $code): bool
    {
        return $this->filterer->contains($countries, $code);
    }

    /**
     * @param array<int, Country> $countries
     * @return array<string, string>
     */
    public function names(array $countries, CountryCodeFormat $format, CountriesCollectionCache $cache): array
    {
        return $cache->names ??= $this->list($countries, $format, $cache);
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, string>
     */
    public function codes(array $countries, CountryCodeFormat $format, CountriesCollectionCache $cache): array
    {
        if ($cache->codes === null) {
            $cache->codes = array_values(array_keys($this->list($countries, $format, $cache)));
        }

        /** @var array<int, string> $codes */
        $codes = $cache->codes;

        return array_values($codes);
    }

    /** @param array<int, Country> $countries
     *  @return array<int, string>
     */
    public function pluckNames(array $countries): array
    {
        return $this->aggregator->pluckNames($countries);
    }

    /**
     * @param array<int, Country> $countries
     * @return array<int, string>
     */
    public function pluckCodes(array $countries, CountryCodeFormat $format): array
    {
        return $this->aggregator->pluckCodes($countries, $format);
    }

    /**
     * @param array<int, Country> $countries
     * @return array<string, string>
     */
    public function list(array $countries, CountryCodeFormat $format, CountriesCollectionCache $cache): array
    {
        return $cache->list ??= $this->aggregator->list($countries, $format);
    }

    /** @param array<int, Country> $countries */
    public function containsCountry(array $countries, callable|Country|string $value): bool
    {
        if ($value instanceof Country) {
            return $this->contains($countries, $value->alpha2());
        }

        if (is_string($value)) {
            return $this->contains($countries, $value);
        }

        foreach ($countries as $country) {
            if ($value($country) === true) {
                return true;
            }
        }

        return false;
    }
}
