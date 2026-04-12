<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Infrastructure\Persistence;

use Iriven\WorldDatasets\Contract\CountryRepositoryInterface;
use Iriven\WorldDatasets\Domain\CountryInfo;
use Iriven\WorldDatasets\Application\Support\CountryCodeNormalizer;
use Iriven\WorldDatasets\Exception\RepositoryException;
use Iriven\WorldDatasets\Infrastructure\Cache\CacheInterface;
use PDO;
use Psr\Log\LoggerInterface;

final class SqliteCountryRepository implements CountryRepositoryInterface
{
    /** @var array<string,Country> */
    private array $byAlpha2 = [];

    /** @var array<string,Country> */
    private array $byAlpha3 = [];

    /** @var array<string,Country> */
    private array $byNumeric = [];

    private bool $indexesLoaded = false;

    private ?CountryCodeNormalizer $resolvedNormalizer = null;

    private SqliteCountryHydrator $hydrator;
    private SqliteCountryQueryBuilder $queryBuilder;
    private SqliteStatementExecutor $executor;

    public function __construct(
        private readonly PDO $pdo,
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CountryCodeNormalizer $normalizer = null,
    ) {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->hydrator = new SqliteCountryHydrator();
        $this->queryBuilder = new SqliteCountryQueryBuilder();
        $this->executor = new SqliteStatementExecutor($this->pdo, $this->logger);
    }

    public static function fromSqliteFile(
        string $sqliteFilePath,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        ?CountryCodeNormalizer $normalizer = null,
    ): self {
        $pdo = (new SqliteConnectionFactory())->create($sqliteFilePath);

        return new self($pdo, $cache, $logger, $normalizer);
    }

    public function count(): int
    {
        return (int) $this->remember(
            'countries.count',
            fn(): int => $this->executor->count($this->queryBuilder->countAll())
        );
    }

    /** @return array<int, CountryInfo> */
    public function findAll(): array
    {
        /** @var array<int, CountryInfo> */
        return $this->remember('countries.all', function (): array {
            return $this->hydrator->hydrateMany(
                $this->executor->fetchAllRows($this->queryBuilder->selectAllOrdered())
            );
        });
    }

    public function findOneByAlpha2(string $alpha2): ?CountryInfo
    {
        $value = $this->normalizer()->normalizeAlpha($alpha2);
        $this->ensureLookupIndexes();

        return $this->byAlpha2[$value] ?? $this->findOneByColumn('alpha2', $value);
    }

    public function findOneByAlpha3(string $alpha3): ?CountryInfo
    {
        $value = $this->normalizer()->normalizeAlpha($alpha3);
        $this->ensureLookupIndexes();

        return $this->byAlpha3[$value] ?? $this->findOneByColumn('alpha3', $value);
    }

    public function findOneByNumeric(string $numeric): ?CountryInfo
    {
        $value = $this->normalizer()->normalizeNumeric($numeric);
        $this->ensureLookupIndexes();

        return $this->byNumeric[$value] ?? $this->findOneByColumn('numeric_code', $value);
    }

    public function findOneByName(string $name): ?CountryInfo
    {
        $results = $this->findByName($name);

        return $results[0] ?? null;
    }

    /** @return array<int, CountryInfo> */
    public function findByName(string $name): array
    {
        $normalized = trim($name);
        $cacheKey = 'countries.find_by_name.' . strtolower($normalized);

        /** @var array<int, CountryInfo> */
        return $this->remember($cacheKey, function () use ($normalized): array {
            return $this->hydrator->hydrateMany(
                $this->executor->fetchAllRowsPrepared(
                    $this->queryBuilder->findByName(),
                    [':name' => $normalized]
                )
            );
        });
    }

    /** @return array<int, CountryInfo> */
    public function search(string $term): array
    {
        $normalized = '%' . strtolower(trim($term)) . '%';
        $cacheKey = 'countries.search.' . md5($normalized);

        /** @var array<int, CountryInfo> */
        return $this->remember($cacheKey, function () use ($normalized): array {
            return $this->hydrator->hydrateMany(
                $this->executor->fetchAllRowsPrepared(
                    $this->queryBuilder->search(),
                    [':term' => $normalized]
                )
            );
        });
    }

    /** @return array<int, CountryInfo> */
    public function findByCurrencyCode(string $currencyCode): array
    {
        return $this->fetchByExactColumn('currency_code', strtoupper(trim($currencyCode)), true);
    }

    /** @return array<int, CountryInfo> */
    public function findByRegion(string $region): array
    {
        return $this->fetchByExactColumn('region_name', trim($region), false);
    }

    /** @return array<int, CountryInfo> */
    public function findByPhoneCode(string $phoneCode): array
    {
        return $this->fetchByExactColumn('phone_code', trim($phoneCode), false);
    }

    /** @return array<int, CountryInfo> */
    public function findByTld(string $tld): array
    {
        return $this->fetchByExactColumn('tld', $this->normalizer()->normalizeTld($tld), false);
    }

    /** @return iterable<CountryInfo> */
    public function iterateAllLazy(int $limit = 500): iterable
    {
        $offset = 0;

        do {
            $batch = $this->hydrator->hydrateMany(
                $this->executor->fetchAllRowsPrepared(
                    $this->queryBuilder->iterateAllLazy(),
                    [':limit' => $limit, ':offset' => $offset],
                    [':limit' => PDO::PARAM_INT, ':offset' => PDO::PARAM_INT]
                )
            );

            foreach ($batch as $country) {
                yield $country;
            }

            $offset += $limit;
        } while ($batch !== []);
    }

    /** @return iterable<CountryInfo> */
    public function iterateByRegionLazy(string $region, int $limit = 500): iterable
    {
        $offset = 0;
        $region = trim($region);

        do {
            $batch = $this->hydrator->hydrateMany(
                $this->executor->fetchAllRowsPrepared(
                    $this->queryBuilder->iterateByRegionLazy(),
                    [':region' => $region, ':limit' => $limit, ':offset' => $offset],
                    [':limit' => PDO::PARAM_INT, ':offset' => PDO::PARAM_INT]
                )
            );

            foreach ($batch as $country) {
                yield $country;
            }

            $offset += $limit;
        } while ($batch !== []);
    }

    /** @return array<string, string> */
    public function getAllCurrenciesCodeAndName(): array
    {
        /** @var array<string,string> */
        return $this->remember(
            'countries.currencies',
            fn(): array => $this->executor->queryMap(
                $this->queryBuilder->currenciesMap(),
                'currency_code',
                'currency_name'
            )
        );
    }

    /** @return array<string, string> */
    public function getAllRegionsCodeAndName(): array
    {
        /** @var array<string,string> */
        return $this->remember(
            'countries.regions',
            fn(): array => $this->executor->queryMap(
                $this->queryBuilder->regionsMap(),
                'region_num_code',
                'region_name'
            )
        );
    }

    /** @return array<string, array<string, string>> */
    public function getAllCountriesGroupedByRegions(): array
    {
        /** @var array<string,array<string,string>> */
        return $this->remember(
            'countries.grouped_by_regions',
            fn(): array => $this->executor->queryGrouped(
                $this->queryBuilder->countriesGroupedByRegions(),
                'region_name',
                'alpha2',
                'country_name'
            )
        );
    }

    /** @return array<string, array<string, string>> */
    public function getAllCountriesGroupedByCurrencies(): array
    {
        /** @var array<string,array<string,string>> */
        return $this->remember(
            'countries.grouped_by_currencies',
            fn(): array => $this->executor->queryGrouped(
                $this->queryBuilder->countriesGroupedByCurrencies(),
                'currency_code',
                'alpha2',
                'country_name'
            )
        );
    }

    private function ensureLookupIndexes(): void
    {
        if ($this->indexesLoaded) {
            return;
        }

        try {
            $rows = $this->executor->fetchAllRows($this->queryBuilder->selectAll());

            foreach ($this->hydrator->hydrateMany($rows) as $country) {
                $this->byAlpha2[$country->alpha2()] = $country;
                $this->byAlpha3[$country->alpha3()] = $country;
                $this->byNumeric[$country->numeric()] = $country;
            }

            $this->indexesLoaded = true;
        } catch (RepositoryException $e) {
            $this->logger?->error('Failed to build memory lookup indexes.', ['exception' => $e]);
        }
    }

    /** @return array<int, CountryInfo> */
    private function fetchByExactColumn(string $column, string $value, bool $normalizeAlpha = true): array
    {
        $allowedColumns = ['currency_code', 'region_name', 'phone_code', 'tld'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new RepositoryException(sprintf('Unsupported filter column: %s', $column));
        }

        $cacheKey = sprintf('countries.filter.%s.%s', $column, strtolower($value));

        /** @var array<int, CountryInfo> */
        return $this->remember($cacheKey, function () use ($column, $value, $normalizeAlpha): array {
            $sqlValue = $normalizeAlpha ? strtoupper($value) : $value;

            return $this->hydrator->hydrateMany(
                $this->executor->fetchAllRowsPrepared(
                    $this->queryBuilder->findByExactColumn($column),
                    [':value' => $sqlValue]
                )
            );
        });
    }

    private function findOneByColumn(string $column, string $value): ?CountryInfo
    {
        $allowedColumns = ['alpha2', 'alpha3', 'numeric_code'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new RepositoryException(sprintf('Unsupported lookup column: %s', $column));
        }

        $cacheKey = sprintf('countries.lookup.%s.%s', $column, $value);

        /** @var ?CountryInfo */
        return $this->remember($cacheKey, function () use ($column, $value): ?CountryInfo {
            $row = $this->executor->fetchOneRowPrepared(
                $this->queryBuilder->findOneByColumn($column),
                [':value' => $value]
            );

            return $row === null ? null : $this->hydrator->hydrate($row);
        });
    }

    private function normalizer(): CountryCodeNormalizer
    {
        return $this->resolvedNormalizer ??= ($this->normalizer ?? new CountryCodeNormalizer());
    }

    private function remember(string $cacheKey, callable $resolver): mixed
    {
        $cache = $this->cache;

        if ($cache !== null && $cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $value = $resolver();

        if ($cache !== null) {
            $cache->set($cacheKey, $value);
        }

        return $value;
    }
}
