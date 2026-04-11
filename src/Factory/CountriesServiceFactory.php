<?php

declare(strict_types=1);

namespace Iriven\Factory;

use Iriven\Countries;
use Iriven\CountriesServiceFactory as BaseCountriesServiceFactory;

final class CountriesServiceFactory
{
    public static function create(?string $path = null): Countries
    {
        return BaseCountriesServiceFactory::make($path);
    }

    public static function createFromJson(?string $path = null): Countries
    {
        return BaseCountriesServiceFactory::make($path);
    }

    public static function createFromCsv(string $path): Countries
    {
        return BaseCountriesServiceFactory::make($path);
    }

    public static function createFromSqlite(?string $path = null): Countries
    {
        return BaseCountriesServiceFactory::make($path);
    }
}
