<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Iriven\WorldDatasets\Contract\Arrayable;
use Iriven\WorldDatasets\Exporter\CsvExporter;
use Iriven\WorldDatasets\Exporter\JsonExporter;

final class CountriesCollection implements Arrayable, \JsonSerializable
{
    /** @var array<string, string>|null */
    private ?array $cachedList = null;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $cachedExportArray = null;
    /** @var array<int, array<int, string>>|null */
    private ?array $cachedStorageArray = null;
    /** @var array<int, array<string, mixed>>|null */
    private ?array $cachedApiArray = null;
    /** @var array<int, string>|null */
    private ?array $cachedCodes = null;
    /** @var array<string, string>|null */
    private ?array $cachedNames = null;
    private ?WorldDatasetsStats $cachedStats = null;
    /** @var array<string, array<string, string>>|null */
    private ?array $cachedGroupByRegion = null;
    /** @var array<string, array<string, string>>|null */
    private ?array $cachedGroupByCurrency = null;

    /**
     * @param array<int, Country> $countries
     */
    public function __construct(
        private readonly array $countries,
        private CountryCodeFormat $format = CountryCodeFormat::ALPHA2,
    ) {
    }

    public function alpha2(): self { return new self($this->countries, CountryCodeFormat::ALPHA2); }
    public function alpha3(): self { return new self($this->countries, CountryCodeFormat::ALPHA3); }
    public function numeric(): self { return new self($this->countries, CountryCodeFormat::NUMERIC); }

    public function inRegion(string $name): self
    {
        return new self($this->filterer()->inRegion($this->countries, $name), $this->format);
    }

    public function inSubRegion(string $name): self
    {
        return new self($this->filterer()->inSubRegion($this->countries, $name), $this->format);
    }

    public function withCurrency(string $code): self
    {
        return new self($this->filterer()->withCurrency($this->countries, $code), $this->format);
    }

    public function withPhoneCode(string $code): self
    {
        return new self($this->filterer()->withPhoneCode($this->countries, $code), $this->format);
    }

    public function withTld(string $tld): self
    {
        return new self($this->filterer()->withTld($this->countries, $tld), $this->format);
    }

    public function named(string $name): self
    {
        return new self($this->filterer()->named($this->countries, $name), $this->format);
    }

    public function matching(string $term): self
    {
        return new self($this->filterer()->matching($this->countries, $term), $this->format);
    }

    public function sortByName(): self
    {
        return new self($this->sorter()->sortByName($this->countries), $this->format);
    }

    public function sortByCode(): self
    {
        return new self($this->sorter()->sortByCode($this->countries, $this->format), $this->format);
    }

    public function sortByNumeric(): self
    {
        return new self($this->sorter()->sortByNumeric($this->countries), $this->format);
    }

    public function paginate(int $offset, int $limit): self
    {
        return new self(array_slice($this->countries, max(0, $offset), max(0, $limit)), $this->format);
    }

    public function first(): ?Country { return $this->countries[0] ?? null; }
    public function last(): ?Country { return $this->countries === [] ? null : $this->countries[array_key_last($this->countries)]; }
    /** @return array<int, Country> */
    public function values(): array { return array_values($this->countries); }
    /** @return array<string, string> */
    public function names(): array { return $this->cachedNames ??= $this->list(); }
    /** @return array<int, string> */
    public function codes(): array
    {
        if ($this->cachedCodes === null) {
            $this->cachedCodes = array_values(array_keys($this->list()));
        }

        /** @var array<int, string> $codes */
        $codes = $this->cachedCodes;

        return array_values($codes);
    }
    public function count(): int { return count($this->countries); }
    public function isEmpty(): bool { return $this->countries === []; }
    public function isNotEmpty(): bool { return !$this->isEmpty(); }

    public function contains(string $code): bool
    {
        return $this->filterer()->contains($this->countries, $code);
    }

    public function containsCountry(callable|Country|string $value): bool
    {
        if ($value instanceof Country) {
            return $this->contains($value->alpha2());
        }

        if (is_string($value)) {
            return $this->contains($value);
        }

        foreach ($this->countries as $country) {
            if ($value($country) === true) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, self> */
    public function chunk(int $size): array
    {
        return array_values(array_map(
            fn(array $chunk): self => new self($chunk, $this->format),
            array_chunk($this->countries, max(1, $size))
        ));
    }

    public function stats(): WorldDatasetsStats
    {
        return $this->cachedStats ??= $this->aggregator()->stats($this->countries);
    }

    /** @return array<string, array<string, string>> */
    public function groupByRegion(): array
    {
        return $this->cachedGroupByRegion ??= $this->aggregator()->groupByRegion($this->countries);
    }

    /** @return array<string, array<string, string>> */
    public function groupByCurrency(): array
    {
        return $this->cachedGroupByCurrency ??= $this->aggregator()->groupByCurrency($this->countries);
    }

    /** @return array<int, string> */
    public function pluckNames(): array
    {
        return $this->aggregator()->pluckNames($this->countries);
    }

    /** @return array<int, string> */
    public function pluckCodes(): array
    {
        return $this->aggregator()->pluckCodes($this->countries, $this->format);
    }

    /** @return array<mixed> */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->countries);
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->countries, $callback)), $this->format);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->countries, $callback, $initial);
    }

    /** @return array<string, string> */
    public function list(): array
    {
        return $this->cachedList ??= $this->aggregator()->list($this->countries, $this->format);
    }

    /** @return array<int, array<string, mixed>> */
    public function exportArray(): array
    {
        return $this->cachedExportArray ??= $this->exporter()->toApiArray($this->countries);
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return (new JsonExporter())->export($this->exportArray(), $flags);
    }

    public function toCsv(): string
    {
        return (new CsvExporter())->export($this->exportArray());
    }

    public function exportJsonFile(string $path): void
    {
        (new JsonExporter())->exportFile($path, $this->exportArray());
    }

    public function exportCsvFile(string $path): void
    {
        (new CsvExporter())->exportFile($path, $this->exportArray());
    }

    /** @return array<int, array<int, string>> */
    public function toStorageArray(): array
    {
        return $this->cachedStorageArray ??= $this->exporter()->toStorageArray($this->countries);
    }

    /** @return array<int, array<string, mixed>> */
    public function toApiArray(): array
    {
        return $this->cachedApiArray ??= $this->exportArray();
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array { return $this->exportArray(); }
    /** @return array<int, array<string, mixed>> */
    public function jsonSerialize(): array { return $this->exportArray(); }

    private function filterer(): CountriesCollectionFilter
    {
        return new CountriesCollectionFilter();
    }

    private function sorter(): CountriesCollectionSorter
    {
        return new CountriesCollectionSorter();
    }

    private function aggregator(): CountriesCollectionAggregator
    {
        return new CountriesCollectionAggregator();
    }

    private function exporter(): CountriesCollectionExporter
    {
        return new CountriesCollectionExporter();
    }
}
