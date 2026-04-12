<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class CountriesCollectionSequence
{
    /**
     * @param array<int, Country> $countries
     * @return array<int, CountriesCollection>
     */
    public function chunk(array $countries, int $size, CountryCodeFormat $format): array
    {
        return array_values(array_map(
            fn(array $chunk): CountriesCollection => new CountriesCollection($chunk, $format),
            array_chunk($countries, max(1, $size))
        ));
    }

    /**
     * @param array<int, Country> $countries
     * @return array<mixed>
     */
    public function map(array $countries, callable $callback): array
    {
        return array_map($callback, $countries);
    }

    /** @param array<int, Country> $countries */
    public function filter(array $countries, callable $callback, CountryCodeFormat $format): CountriesCollection
    {
        return new CountriesCollection(array_values(array_filter($countries, $callback)), $format);
    }

    /** @param array<int, Country> $countries */
    public function reduce(array $countries, callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($countries, $callback, $initial);
    }

    /** @param array<int, Country> $countries */
    public function paginate(array $countries, int $offset, int $limit, CountryCodeFormat $format): CountriesCollection
    {
        return new CountriesCollection(array_slice($countries, max(0, $offset), max(0, $limit)), $format);
    }
}
