<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

use Iriven\WorldDatasets\Contract\Arrayable;
use Iriven\WorldDatasets\Exporter\CsvExporter;
use Iriven\WorldDatasets\Exporter\JsonExporter;

final class CountriesCollection implements Arrayable, \JsonSerializable
{
    private CountriesCollectionCache $cache;

    /**
     * @param array<int, Country> $countries
     */
    public function __construct(
        private readonly array $countries,
        private CountryCodeFormat $format = CountryCodeFormat::ALPHA2,
    ) {
        $this->cache = new CountriesCollectionCache();
    }

    public function alpha2(): self { return new self($this->countries, CountryCodeFormat::ALPHA2); }
    public function alpha3(): self { return new self($this->countries, CountryCodeFormat::ALPHA3); }
    public function numeric(): self { return new self($this->countries, CountryCodeFormat::NUMERIC); }

    public function inRegion(string $name): self { return new self($this->filterer()->inRegion($this->countries, $name), $this->format); }
    public function inSubRegion(string $name): self { return new self($this->filterer()->inSubRegion($this->countries, $name), $this->format); }
    public function withCurrency(string $code): self { return new self($this->filterer()->withCurrency($this->countries, $code), $this->format); }
    public function withPhoneCode(string $code): self { return new self($this->filterer()->withPhoneCode($this->countries, $code), $this->format); }
    public function withTld(string $tld): self { return new self($this->filterer()->withTld($this->countries, $tld), $this->format); }
    public function named(string $name): self { return new self($this->filterer()->named($this->countries, $name), $this->format); }
    public function matching(string $term): self { return new self($this->filterer()->matching($this->countries, $term), $this->format); }

    public function sortByName(): self { return new self($this->sorter()->sortByName($this->countries), $this->format); }
    public function sortByCode(): self { return new self($this->sorter()->sortByCode($this->countries, $this->format), $this->format); }
    public function sortByNumeric(): self { return new self($this->sorter()->sortByNumeric($this->countries), $this->format); }

    public function paginate(int $offset, int $limit): self { return $this->sequence()->paginate($this->countries, $offset, $limit, $this->format); }

    public function first(): ?Country { return $this->reader()->first($this->countries); }
    public function last(): ?Country { return $this->reader()->last($this->countries); }
    /** @return array<int, Country> */
    public function values(): array { return $this->reader()->values($this->countries); }
    /** @return array<string, string> */
    public function names(): array { return $this->reader()->names($this->countries, $this->format, $this->cache); }
    /** @return array<int, string> */
    public function codes(): array { return $this->reader()->codes($this->countries, $this->format, $this->cache); }
    public function count(): int { return $this->reader()->count($this->countries); }
    public function isEmpty(): bool { return $this->reader()->isEmpty($this->countries); }
    public function isNotEmpty(): bool { return $this->reader()->isNotEmpty($this->countries); }

    public function contains(string $code): bool { return $this->reader()->contains($this->countries, $code); }
    public function containsCountry(callable|Country|string $value): bool { return $this->reader()->containsCountry($this->countries, $value); }

    /** @return array<int, self> */
    public function chunk(int $size): array { return $this->sequence()->chunk($this->countries, $size, $this->format); }

    public function stats(): WorldDatasetsStats { return $this->cache->stats ??= $this->aggregator()->stats($this->countries); }

    /** @return array<string, array<string, string>> */
    public function groupByRegion(): array { return $this->cache->groupByRegion ??= $this->aggregator()->groupByRegion($this->countries); }
    /** @return array<string, array<string, string>> */
    public function groupByCurrency(): array { return $this->cache->groupByCurrency ??= $this->aggregator()->groupByCurrency($this->countries); }

    /** @return array<int, string> */
    public function pluckNames(): array { return $this->reader()->pluckNames($this->countries); }
    /** @return array<int, string> */
    public function pluckCodes(): array { return $this->reader()->pluckCodes($this->countries, $this->format); }

    /** @return array<mixed> */
    public function map(callable $callback): array { return $this->sequence()->map($this->countries, $callback); }
    public function filter(callable $callback): self { return $this->sequence()->filter($this->countries, $callback, $this->format); }
    public function reduce(callable $callback, mixed $initial = null): mixed { return $this->sequence()->reduce($this->countries, $callback, $initial); }

    /** @return array<string, string> */
    public function list(): array { return $this->reader()->list($this->countries, $this->format, $this->cache); }

    /** @return array<int, array<string, mixed>> */
    public function exportArray(): array { return $this->cache->exportArray ??= $this->exporter()->toApiArray($this->countries); }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string { return (new JsonExporter())->export($this->exportArray(), $flags); }
    public function toCsv(): string { return (new CsvExporter())->export($this->exportArray()); }
    public function exportJsonFile(string $path): void { (new JsonExporter())->exportFile($path, $this->exportArray()); }
    public function exportCsvFile(string $path): void { (new CsvExporter())->exportFile($path, $this->exportArray()); }

    /** @return array<int, array<int, string>> */
    public function toStorageArray(): array { return $this->cache->storageArray ??= $this->exporter()->toStorageArray($this->countries); }
    /** @return array<int, array<string, mixed>> */
    public function toApiArray(): array { return $this->cache->apiArray ??= $this->exportArray(); }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array { return $this->exportArray(); }
    /** @return array<int, array<string, mixed>> */
    public function jsonSerialize(): array { return $this->exportArray(); }

    private function filterer(): CountriesCollectionFilter { return new CountriesCollectionFilter(); }
    private function sorter(): CountriesCollectionSorter { return new CountriesCollectionSorter(); }
    private function aggregator(): CountriesCollectionAggregator { return new CountriesCollectionAggregator(); }
    private function exporter(): CountriesCollectionExporter { return new CountriesCollectionExporter(); }
    private function reader(): CountriesCollectionReadModel { return new CountriesCollectionReadModel(); }
    private function sequence(): CountriesCollectionSequence { return new CountriesCollectionSequence(); }
}
