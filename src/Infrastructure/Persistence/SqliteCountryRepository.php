<?php

declare(strict_types=1);

namespace Iriven\Infrastructure\Persistence;

use Iriven\Contract\CountryRepositoryInterface;
use Iriven\Country;
use Iriven\CountryCodeNormalizer;
use Iriven\Exception\RepositoryException;
use Iriven\Infrastructure\Cache\CacheInterface;
use PDO;
use PDOException;
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

    public function __construct(
        private readonly PDO $pdo,
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CountryCodeNormalizer $normalizer = null,
    ) {
        $this->configurePdo($this->pdo);
    }

    public static function fromSqliteFile(
        string $sqliteFilePath,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        ?CountryCodeNormalizer $normalizer = null,
    ): self {
        if ($sqliteFilePath === '' || !is_file($sqliteFilePath)) {
            throw new RepositoryException(sprintf('SQLite file not found: %s', $sqliteFilePath));
        }

        try {
            $pdo = new PDO('sqlite:' . $sqliteFilePath);
        } catch (PDOException $e) {
            throw new RepositoryException('Unable to open SQLite database.', 0, $e);
        }

        return new self($pdo, $cache, $logger, $normalizer);
    }

    public function count(): int
    {
        return (int) $this->remember('countries.count', function (): int {
            try {
                $stmt = $this->pdo->query('SELECT COUNT(*) FROM countries');
                return (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                $this->logger?->error('Failed to count countries.', ['exception' => $e]);
                throw new RepositoryException('Failed to count countries.', 0, $e);
            }
        });
    }

    public function findAll(): array
    {
        /** @var list<Country> */
        return $this->remember('countries.all', function (): array {
            return $this->fetchCountries(
                'SELECT
                    alpha2,
                    alpha3,
                    numeric_code,
                    country_name,
                    capital,
                    tld,
                    region_alpha_code,
                    region_num_code,
                    region_name,
                    sub_region_code,
                    sub_region_name,
                    language,
                    currency_code,
                    currency_name,
                    postal_code_pattern,
                    phone_code,
                    intl_dialing_prefix,
                    natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 ORDER BY country_name ASC'
            );
        });
    }

    public function findOneByAlpha2(string $alpha2): ?Country
    {
        $value = $this->normalizer()->normalizeAlpha($alpha2);
        $this->ensureLookupIndexes();

        return $this->byAlpha2[$value] ?? $this->findOneByColumn('alpha2', $value);
    }

    public function findOneByAlpha3(string $alpha3): ?Country
    {
        $value = $this->normalizer()->normalizeAlpha($alpha3);
        $this->ensureLookupIndexes();

        return $this->byAlpha3[$value] ?? $this->findOneByColumn('alpha3', $value);
    }

    public function findOneByNumeric(string $numeric): ?Country
    {
        $value = $this->normalizer()->normalizeNumeric($numeric);
        $this->ensureLookupIndexes();

        return $this->byNumeric[$value] ?? $this->findOneByColumn('numeric_code', $value);
    }

    public function findOneByName(string $name): ?Country
    {
        $results = $this->findByName($name);
        return $results[0] ?? null;
    }

    public function findByName(string $name): array
    {
        $normalized = trim($name);
        $cacheKey = 'countries.find_by_name.' . strtolower($normalized);

        /** @var list<Country> */
        return $this->remember($cacheKey, function () use ($normalized): array {
            return $this->fetchCountriesPrepared(
                'SELECT
                    alpha2, alpha3, numeric_code, country_name, capital, tld,
                    region_alpha_code, region_num_code, region_name,
                    sub_region_code, sub_region_name, language,
                    currency_code, currency_name, postal_code_pattern,
                    phone_code, intl_dialing_prefix, natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 WHERE LOWER(country_name) = LOWER(:name)
                 ORDER BY country_name ASC',
                [':name' => $normalized]
            );
        });
    }

    public function search(string $term): array
    {
        $normalized = '%' . strtolower(trim($term)) . '%';
        $cacheKey = 'countries.search.' . md5($normalized);

        /** @var list<Country> */
        return $this->remember($cacheKey, function () use ($normalized): array {
            return $this->fetchCountriesPrepared(
                'SELECT
                    alpha2, alpha3, numeric_code, country_name, capital, tld,
                    region_alpha_code, region_num_code, region_name,
                    sub_region_code, sub_region_name, language,
                    currency_code, currency_name, postal_code_pattern,
                    phone_code, intl_dialing_prefix, natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 WHERE LOWER(country_name) LIKE :term
                    OR LOWER(alpha2) LIKE :term
                    OR LOWER(alpha3) LIKE :term
                    OR LOWER(numeric_code) LIKE :term
                    OR LOWER(currency_code) LIKE :term
                    OR LOWER(region_name) LIKE :term
                    OR LOWER(tld) LIKE :term
                 ORDER BY country_name ASC',
                [':term' => $normalized]
            );
        });
    }

    public function findByCurrencyCode(string $currencyCode): array
    {
        return $this->fetchByExactColumn('currency_code', strtoupper(trim($currencyCode)), true);
    }

    public function findByRegion(string $region): array
    {
        return $this->fetchByExactColumn('region_name', trim($region), false);
    }

    public function findByPhoneCode(string $phoneCode): array
    {
        return $this->fetchByExactColumn('phone_code', trim($phoneCode), false);
    }

    public function findByTld(string $tld): array
    {
        return $this->fetchByExactColumn('tld', $this->normalizer()->normalizeTld($tld), false);
    }

    /** @return iterable<Country> */
    public function iterateAllLazy(int $limit = 500): iterable
    {
        $offset = 0;

        do {
            $batch = $this->fetchCountriesPrepared(
                'SELECT
                    alpha2, alpha3, numeric_code, country_name, capital, tld,
                    region_alpha_code, region_num_code, region_name,
                    sub_region_code, sub_region_name, language,
                    currency_code, currency_name, postal_code_pattern,
                    phone_code, intl_dialing_prefix, natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 ORDER BY country_name ASC
                 LIMIT :limit OFFSET :offset',
                [':limit' => $limit, ':offset' => $offset],
                [':limit' => PDO::PARAM_INT, ':offset' => PDO::PARAM_INT]
            );

            foreach ($batch as $country) {
                yield $country;
            }

            $offset += $limit;
        } while ($batch !== []);
    }

    /** @return iterable<Country> */
    public function iterateByRegionLazy(string $region, int $limit = 500): iterable
    {
        $offset = 0;
        $region = trim($region);

        do {
            $batch = $this->fetchCountriesPrepared(
                'SELECT
                    alpha2, alpha3, numeric_code, country_name, capital, tld,
                    region_alpha_code, region_num_code, region_name,
                    sub_region_code, sub_region_name, language,
                    currency_code, currency_name, postal_code_pattern,
                    phone_code, intl_dialing_prefix, natl_dialing_prefix,
                    subscriber_phone_pattern
                 FROM countries
                 WHERE region_name = :region
                 ORDER BY country_name ASC
                 LIMIT :limit OFFSET :offset',
                [':region' => $region, ':limit' => $limit, ':offset' => $offset],
                [':limit' => PDO::PARAM_INT, ':offset' => PDO::PARAM_INT]
            );

            foreach ($batch as $country) {
                yield $country;
            }

            $offset += $limit;
        } while ($batch !== []);
    }

    public function getAllCurrenciesCodeAndName(): array
    {
        /** @var array<string,string> */
        return $this->remember('countries.currencies', function (): array {
            return $this->queryMap(
                'SELECT currency_code, currency_name
                 FROM countries
                 WHERE currency_code IS NOT NULL
                   AND TRIM(currency_code) <> ""
                 GROUP BY currency_code, currency_name
                 ORDER BY currency_name ASC',
                'currency_code',
                'currency_name'
            );
        });
    }

    public function getAllRegionsCodeAndName(): array
    {
        /** @var array<string,string> */
        return $this->remember('countries.regions', function (): array {
            return $this->queryMap(
                'SELECT region_num_code, region_name
                 FROM countries
                 WHERE region_num_code IS NOT NULL
                   AND TRIM(region_num_code) <> ""
                 GROUP BY region_num_code, region_name
                 ORDER BY region_name ASC',
                'region_num_code',
                'region_name'
            );
        });
    }

    public function getAllCountriesGroupedByRegions(): array
    {
        /** @var array<string,array<string,string>> */
        return $this->remember('countries.grouped_by_regions', function (): array {
            return $this->queryGrouped(
                'SELECT region_name, alpha2, country_name
                 FROM countries
                 WHERE region_name IS NOT NULL
                   AND TRIM(region_name) <> ""
                 ORDER BY region_name ASC, country_name ASC',
                'region_name',
                'alpha2',
                'country_name'
            );
        });
    }

    public function getAllCountriesGroupedByCurrencies(): array
    {
        /** @var array<string,array<string,string>> */
        return $this->remember('countries.grouped_by_currencies', function (): array {
            return $this->queryGrouped(
                'SELECT currency_code, alpha2, country_name
                 FROM countries
                 WHERE currency_code IS NOT NULL
                   AND TRIM(currency_code) <> ""
                 ORDER BY currency_code ASC, country_name ASC',
                'currency_code',
                'alpha2',
                'country_name'
            );
        });
    }

    private function ensureLookupIndexes(): void
    {
        if ($this->indexesLoaded) {
            return;
        }

        try {
            $stmt = $this->pdo->query('SELECT
                alpha2, alpha3, numeric_code, country_name, capital, tld,
                region_alpha_code, region_num_code, region_name,
                sub_region_code, sub_region_name, language,
                currency_code, currency_name, postal_code_pattern,
                phone_code, intl_dialing_prefix, natl_dialing_prefix,
                subscriber_phone_pattern
             FROM countries');

            foreach ($stmt->fetchAll() as $row) {
                $country = Country::fromDatabaseRow($row);
                $this->byAlpha2[$country->alpha2()] = $country;
                $this->byAlpha3[$country->alpha3()] = $country;
                $this->byNumeric[$country->numeric()] = $country;
            }

            $this->indexesLoaded = true;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to build memory lookup indexes.', ['exception' => $e]);
        }
    }

    private function fetchByExactColumn(string $column, string $value, bool $normalizeAlpha = true): array
    {
        $allowedColumns = ['currency_code', 'region_name', 'phone_code', 'tld'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new RepositoryException(sprintf('Unsupported filter column: %s', $column));
        }

        $cacheKey = sprintf('countries.filter.%s.%s', $column, strtolower($value));

        /** @var list<Country> */
        return $this->remember($cacheKey, function () use ($column, $value, $normalizeAlpha): array {
            $sqlValue = $normalizeAlpha ? strtoupper($value) : $value;
            return $this->fetchCountriesPrepared(
                sprintf(
                    'SELECT
                        alpha2, alpha3, numeric_code, country_name, capital, tld,
                        region_alpha_code, region_num_code, region_name,
                        sub_region_code, sub_region_name, language,
                        currency_code, currency_name, postal_code_pattern,
                        phone_code, intl_dialing_prefix, natl_dialing_prefix,
                        subscriber_phone_pattern
                     FROM countries
                     WHERE %s = :value
                     ORDER BY country_name ASC',
                    $column
                ),
                [':value' => $sqlValue]
            );
        });
    }

    private function findOneByColumn(string $column, string $value): ?Country
    {
        $allowedColumns = ['alpha2', 'alpha3', 'numeric_code'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new RepositoryException(sprintf('Unsupported lookup column: %s', $column));
        }

        $cacheKey = sprintf('countries.lookup.%s.%s', $column, $value);

        /** @var ?Country */
        return $this->remember($cacheKey, function () use ($column, $value): ?Country {
            try {
                $stmt = $this->pdo->prepare(
                    sprintf(
                        'SELECT
                            alpha2, alpha3, numeric_code, country_name, capital, tld,
                            region_alpha_code, region_num_code, region_name,
                            sub_region_code, sub_region_name, language,
                            currency_code, currency_name, postal_code_pattern,
                            phone_code, intl_dialing_prefix, natl_dialing_prefix,
                            subscriber_phone_pattern
                         FROM countries
                         WHERE %s = :value
                         LIMIT 1',
                        $column
                    )
                );

                $stmt->bindValue(':value', $value);
                $stmt->execute();

                $row = $stmt->fetch();
                return $row === false ? null : Country::fromDatabaseRow($row);
            } catch (PDOException $e) {
                $this->logger?->error('Failed to lookup country.', [
                    'column' => $column,
                    'value' => $value,
                    'exception' => $e,
                ]);

                throw new RepositoryException('Failed to lookup country.', 0, $e);
            }
        });
    }

    /** @return list<Country> */
    private function fetchCountries(string $sql): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();
            $result = [];
            foreach ($rows as $row) {
                $result[] = Country::fromDatabaseRow($row);
            }
            return $result;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to fetch countries.', ['exception' => $e, 'sql' => $sql]);
            throw new RepositoryException('Failed to fetch countries.', 0, $e);
        }
    }

    /** @return list<Country> */
    private function fetchCountriesPrepared(string $sql, array $params, array $paramTypes = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $type = $paramTypes[$key] ?? PDO::PARAM_STR;
                $stmt->bindValue((string) $key, $value, $type);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $result[] = Country::fromDatabaseRow($row);
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to fetch countries with prepared query.', [
                'exception' => $e,
                'sql' => $sql,
                'params' => $params,
            ]);
            throw new RepositoryException('Failed to fetch countries.', 0, $e);
        }
    }

    /** @return array<string, string> */
    private function queryMap(string $sql, string $key, string $value): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $result[(string) $row[$key]] = (string) $row[$value];
            }

            return $result;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to execute map query.', ['exception' => $e]);
            throw new RepositoryException('Failed to execute map query.', 0, $e);
        }
    }

    /** @return array<string, array<string, string>> */
    private function queryGrouped(string $sql, string $groupKey, string $itemKey, string $itemValue): array
    {
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();

            $result = [];
            foreach ($rows as $row) {
                $result[(string) $row[$groupKey]][(string) $row[$itemKey]] = (string) $row[$itemValue];
            }

            ksort($result);
            return $result;
        } catch (PDOException $e) {
            $this->logger?->error('Failed to execute grouped query.', ['exception' => $e]);
            throw new RepositoryException('Failed to execute grouped query.', 0, $e);
        }
    }

    private function configurePdo(PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    private function normalizer(): CountryCodeNormalizer
    {
        return $this->resolvedNormalizer ??= ($this->normalizer ?? new CountryCodeNormalizer());
    }

    private function remember(string $cacheKey, callable $resolver): mixed
    {
        if ($this->cache?->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $value = $resolver();
        $this->cache?->set($cacheKey, $value);

        return $value;
    }
}
