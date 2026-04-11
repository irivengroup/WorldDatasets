<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Contract;

use Countable;
use IteratorAggregate;
use Iriven\WorldDatasets\CountriesCollection;
use Iriven\WorldDatasets\CurrencyCollection;
use Iriven\WorldDatasets\RegionCollection;
use Iriven\WorldDatasets\Country;

interface CountriesDataInterface extends Countable, IteratorAggregate
{
    public function all(): array;

    public function country(string $code): Country;

    public function findCountry(string $code): ?Country;

    public function countries(int|string|\Iriven\CountryCodeFormat $format = \Iriven\CountryCodeFormat::ALPHA2): CountriesCollection;

    public function currencies(): CurrencyCollection;

    public function regions(): RegionCollection;
}
