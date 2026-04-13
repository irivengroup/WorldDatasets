<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Domain;
use Iriven\WorldDatasets\Application\Stats\WorldDatasetsStats;
use Iriven\WorldDatasets\Contract\Arrayable;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionAggregator;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionCache;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionExporter;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionFilter;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionReadModel;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionSequence;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountriesCollectionSorter;
use Iriven\WorldDatasets\Domain\CountriesCollection\CountryCodeFormat;
use Iriven\WorldDatasets\Domain\CountryInfo;
use Iriven\WorldDatasets\Exporter\CsvExporter;
use Iriven\WorldDatasets\Exporter\JsonExporter;





final class CountriesCollection implements Arrayable, \JsonSerializable
{
    private CountriesCollectionCache $cache;
    private ?CountriesCollectionFilter $filtererInstance = null;
    private ?CountriesCollectionSorter $sorterInstance = null;
    private ?CountriesCollectionAggregator $aggregatorInstance = null;
    private ?CountriesCollectionExporter $exporterInstance = null;
    private ?CountriesCollectionReadModel $readerInstance = null;
    private ?CountriesCollectionSequence $sequenceInstance = null;

    /**
     * @param array<int, CountryInfo> $countries
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

    public function first(): ?CountryInfo { return $this->reader()->first($this->countries); }
    public function last(): ?CountryInfo { return $this->reader()->last($this->countries); }
    /** @return array<int, CountryInfo> */
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

    private function filterer(): CountriesCollectionFilter { return $this->filtererInstance ??= new CountriesCollectionFilter(); }
    private function sorter(): CountriesCollectionSorter { return $this->sorterInstance ??= new CountriesCollectionSorter(); }
    private function aggregator(): CountriesCollectionAggregator { return $this->aggregatorInstance ??= new CountriesCollectionAggregator(); }
    private function exporter(): CountriesCollectionExporter { return $this->exporterInstance ??= new CountriesCollectionExporter(); }
    private function reader(): CountriesCollectionReadModel { return $this->readerInstance ??= new CountriesCollectionReadModel(); }
    private function sequence(): CountriesCollectionSequence { return $this->sequenceInstance ??= new CountriesCollectionSequence(); }
}
