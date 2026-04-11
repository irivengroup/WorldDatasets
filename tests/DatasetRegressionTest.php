<?php

declare(strict_types=1);

namespace Iriven\Tests;

use Iriven\CountriesServiceFactory;
use PHPUnit\Framework\TestCase;

final class DatasetRegressionTest extends TestCase
{
    public function testCoreCountriesAreStable(): void
    {
        $service = CountriesServiceFactory::make();

        self::assertSame('FR', $service->country('FR')->alpha2());
        self::assertSame('FRA', $service->country('FR')->alpha3());
        self::assertSame('250', $service->country('FR')->numeric());

        self::assertSame('US', $service->country('US')->alpha2());
        self::assertSame('USA', $service->country('US')->alpha3());
        self::assertSame('840', $service->country('US')->numeric());

        self::assertSame('JP', $service->country('JP')->alpha2());
        self::assertSame('JPN', $service->country('JP')->alpha3());
        self::assertSame('392', $service->country('JP')->numeric());
    }

    public function testDatasetCountsAndIntegrity(): void
    {
        $service = CountriesServiceFactory::make();

        self::assertGreaterThan(200, $service->count());
        self::assertFalse($service->countries()->isEmpty());
        self::assertTrue($service->countries()->contains('FR'));
    }
}
