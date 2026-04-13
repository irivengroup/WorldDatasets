<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\Infrastructure\Persistence\JsonCountryRepository;
use PHPUnit\Framework\TestCase;

final class JsonCountryRepositoryTest extends TestCase
{
    private JsonCountryRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new JsonCountryRepository(__DIR__ . '/../src/data/.countriesRepository.json');
    }

    public function testFindOneByAlpha2(): void
    {
        $country = $this->repository->findOneByAlpha2('FR');

        self::assertNotNull($country);
        self::assertSame('France', $country->name());
        self::assertSame('FRA', $country->alpha3());
    }

    public function testFindOneByAlpha3(): void
    {
        $country = $this->repository->findOneByAlpha3('FRA');

        self::assertNotNull($country);
        self::assertSame('FR', $country->alpha2());
    }

    public function testFindOneByNumeric(): void
    {
        $country = $this->repository->findOneByNumeric('250');

        self::assertNotNull($country);
        self::assertSame('France', $country->name());
    }

    public function testSearchMethods(): void
    {
        self::assertNotEmpty($this->repository->findByName('France'));
        self::assertNotEmpty($this->repository->search('euro'));
        self::assertNotEmpty($this->repository->findByCurrencyCode('EUR'));
        self::assertNotEmpty($this->repository->findByRegion('Europe'));
        self::assertNotEmpty($this->repository->findByPhoneCode('33'));
        self::assertNotEmpty($this->repository->findByTld('.fr'));
    }
}
