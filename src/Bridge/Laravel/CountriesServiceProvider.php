<?php

declare(strict_types=1);

namespace Iriven\Bridge\Laravel;

use Illuminate\Support\ServiceProvider;
use Iriven\Countries;
use Iriven\CountriesServiceFactory;

final class CountriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Countries::class, function () {
            $path = base_path('vendor/iriven/php-countries-data/src/data/.countriesRepository.sqlite');
            return CountriesServiceFactory::make($path);
        });
    }
}
