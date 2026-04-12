<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Countable;
use Generator;
use InvalidArgumentException;
use Iriven\WorldDatasets\Contract\CountriesDataInterface;
use Iriven\WorldDatasets\Contract\CountryRepositoryInterface;
use Iriven\WorldDatasets\Contract\ReadonlyWorldDatasetsServiceInterface;
use Iriven\WorldDatasets\Exception\CountryNotFoundException;
use Iriven\WorldDatasets\Exception\InvalidCountryCodeException;
use Iriven\WorldDatasets\Exception\InvalidFormatException;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Country>
 */
class WorldDatasets implements CountriesDataInterface, ReadonlyWorldDatasetsServiceInterface, Countable, IteratorAggregate
{
    public const ALPHA2 = 0;
    public const ALPHA3 = 1;
    public const NUMERIC = 2;

    public function __construct(
        private readonly CountryRepositoryInterface $repository,
        private readonly CountryCodeNormalizer $countryCodeNormalizer = new CountryCodeNormalizer(),
        private readonly PhoneCodeNormalizer $phoneCodeNormalizer = new PhoneCodeNormalizer(),
        private readonly TldNormalizer $tldNormalizer = new TldNormalizer(),
        private readonly string $datasetSource = 'json',
        private readonly string $datasetVersion = '2026.04.11',
        private readonly ?string $datasetChecksum = null,
        private readonly ?string $datasetBuiltAt = null,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return array_map(
            static fn(Country $country): array => $country->toArray(),
            $this->repository->findAll()
        );
    }

    public function iterator(int|string|CountryCodeFormat $format = CountryCodeFormat::ALPHA2): Generator
    {
        $formatEnum = $this->normalizeFormat($format);

        foreach ($this->repository->findAll() as $country) {
            $key = match ($formatEnum) {
                CountryCodeFormat::ALPHA2 => $country->alpha2(),
                CountryCodeFormat::ALPHA3 => $country->alpha3(),
                CountryCodeFormat::NUMERIC => $country->numeric(),
            };
            yield $key => $country;
        }
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    public function getIterator(): Traversable
    {
        foreach ($this->repository->findAll() as $country) {
            yield $country;
        }
    }

    public function country(string $code): Country
    {
        return $this->resolveCountry($code);
    }

    public function findCountry(string $code): ?Country
    {
        $code = trim($code);

        if ($code === '') {
            return null;
        }

        $normalized = $this->countryCodeNormalizer->normalizePreservingNumeric($code);

        if (preg_match('/^\d{3}$/', $normalized) === 1) {
            return $this->repository->findOneByNumeric($normalized);
        }

        if (preg_match('/^[A-Z]{2}$/', $normalized) === 1) {
            return $this->repository->findOneByAlpha2($normalized);
        }

        if (preg_match('/^[A-Z]{3}$/', $normalized) === 1) {
            return $this->repository->findOneByAlpha3($normalized);
        }

        return null;
    }

    public function countries(int|string|CountryCodeFormat $format = CountryCodeFormat::ALPHA2): CountriesCollection
    {
        return new CountriesCollection($this->repository->findAll(), $this->normalizeFormat($format));
    }

    public function currencies(): CurrencyCollection
    {
        return new CurrencyCollection($this->repository->findAll());
    }

    public function regions(): RegionCollection
    {
        return new RegionCollection($this->repository->findAll());
    }

    public function meta(): MetaInfo
    {
        return new MetaInfo(
            count: $this->count(),
            source: $this->datasetSource,
            version: $this->datasetVersion,
            lastUpdatedAt: null,
            packageVersion: '1.0.0',
            datasetVersion: $this->datasetVersion,
            checksum: $this->datasetChecksum,
            builtAt: $this->datasetBuiltAt,
        );
    }

    public function query(): WorldDatasetsQuery
    {
        return new WorldDatasetsQuery($this->countries());
    }

    /** @return array<int, Country> */
    public function findByName(string $name): array
    {
        return $this->repository->findByName($name);
    }

    /** @return array<int, Country> */
    public function searchCountries(string $term): array
    {
        return $this->repository->search($term);
    }

    /** @return array<int, Country> */
    public function findByCurrencyCode(string $currencyCode): array
    {
        return $this->repository->findByCurrencyCode($currencyCode);
    }

    /** @return array<int, Country> */
    public function findByRegion(string $region): array
    {
        return $this->repository->findByRegion($region);
    }

    /** @return array<int, Country> */
    public function findByPhoneCode(string $phoneCode): array
    {
        return $this->repository->findByPhoneCode($this->phoneCodeNormalizer->normalize($phoneCode));
    }

    /** @return array<int, Country> */
    public function findByTld(string $tld): array
    {
        return $this->repository->findByTld($this->tldNormalizer->normalize($tld));
    }

    private function resolveCountry(string $code): Country
    {
        $code = trim($code);

        if ($code === '') {
            throw InvalidCountryCodeException::forEmptyValue();
        }

        $country = $this->findCountry($code);

        if ($country === null) {
            $normalized = $this->countryCodeNormalizer->normalizePreservingNumeric($code);

            if (
                preg_match('/^\d{3}$/', $normalized) !== 1
                && preg_match('/^[A-Z]{2}$/', $normalized) !== 1
                && preg_match('/^[A-Z]{3}$/', $normalized) !== 1
            ) {
                throw InvalidCountryCodeException::forValue($code);
            }

            throw CountryNotFoundException::forKey($code);
        }

        return $country;
    }

    private function normalizeFormat(int|string|CountryCodeFormat $format): CountryCodeFormat
    {
        if ($format instanceof CountryCodeFormat) {
            return $format;
        }

        if (is_int($format) || ctype_digit((string) $format)) {
            return match ((int) $format) {
                self::ALPHA2 => CountryCodeFormat::ALPHA2,
                self::ALPHA3 => CountryCodeFormat::ALPHA3,
                self::NUMERIC => CountryCodeFormat::NUMERIC,
                default => throw new InvalidFormatException(sprintf('Unsupported format: %s', (string) $format)),
            };
        }

        return match (strtolower(trim((string) $format))) {
            'alpha-2', 'alpha2', 'iso-2', 'iso2' => CountryCodeFormat::ALPHA2,
            'alpha-3', 'alpha3', 'iso-3', 'iso3' => CountryCodeFormat::ALPHA3,
            'numeric', 'num' => CountryCodeFormat::NUMERIC,
            default => CountryCodeFormat::ALPHA2,
        };
    }
}
