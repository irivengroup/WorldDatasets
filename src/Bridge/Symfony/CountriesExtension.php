<?php

declare(strict_types=1);

namespace Iriven\Bridge\Symfony;

use Iriven\CountriesServiceFactory;

final class CountriesExtension
{
    public static function create(?string $path = null): \Iriven\Countries
    {
        return CountriesServiceFactory::make($path);
    }
}
