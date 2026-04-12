<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets;

final class WorldDatasetsQuery
{
    public function __construct(
        private readonly CountriesCollection $collection,
    ) {
    }

    public function inRegion(string $name): self { return new self($this->collection->inRegion($name)); }
    public function inSubRegion(string $name): self { return new self($this->collection->inSubRegion($name)); }
    public function withCurrency(string $code): self { return new self($this->collection->withCurrency($code)); }
    public function withPhoneCode(string $code): self { return new self($this->collection->withPhoneCode($code)); }
    public function withTld(string $tld): self { return new self($this->collection->withTld($tld)); }
    public function matching(string $term): self { return new self($this->collection->matching($term)); }
    public function sortByName(): self { return new self($this->collection->sortByName()); }
    public function sortByCode(): self { return new self($this->collection->sortByCode()); }
    public function sortByNumeric(): self { return new self($this->collection->sortByNumeric()); }
    public function limit(int $limit): self { return new self($this->collection->paginate(0, $limit)); }
    public function offset(int $offset, int $limit = PHP_INT_MAX): self { return new self($this->collection->paginate($offset, $limit)); }

    /** @return array<int, Country> */
    public function get(): array
    {
        return $this->collection->values();
    }

    /** @return array<string, string> */
    public function list(): array
    {
        return $this->collection->list();
    }
}
