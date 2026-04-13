<?php

declare(strict_types=1);

namespace Iriven\WorldDatasets\Tests;

use Iriven\WorldDatasets\Infrastructure\Persistence\SqliteStatementExecutor;
use PDO;
use PHPUnit\Framework\TestCase;

final class SqliteStatementExecutorTest extends TestCase
{
    private function makePdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec('CREATE TABLE countries (alpha2 TEXT, country_name TEXT, region_name TEXT, currency_code TEXT)');
        $pdo->exec("INSERT INTO countries (alpha2, country_name, region_name, currency_code) VALUES ('FR', 'France', 'Europe', 'EUR')");
        $pdo->exec("INSERT INTO countries (alpha2, country_name, region_name, currency_code) VALUES ('US', 'United States', 'Americas', 'USD')");
        $pdo->exec("INSERT INTO countries (alpha2, country_name, region_name, currency_code) VALUES ('DE', 'Germany', 'Europe', 'EUR')");

        return $pdo;
    }

    public function testCountAndFetchMethods(): void
    {
        $executor = new SqliteStatementExecutor($this->makePdo());

        self::assertSame(3, $executor->count('SELECT COUNT(*) FROM countries'));

        $all = $executor->fetchAllRows('SELECT alpha2, country_name FROM countries ORDER BY alpha2 ASC');
        self::assertCount(3, $all);
        self::assertSame('DE', $all[0]['alpha2']);

        $prepared = $executor->fetchAllRowsPrepared(
            'SELECT alpha2, country_name FROM countries WHERE region_name = :region ORDER BY alpha2 ASC',
            [':region' => 'Europe']
        );
        self::assertCount(2, $prepared);

        $one = $executor->fetchOneRowPrepared(
            'SELECT alpha2, country_name FROM countries WHERE alpha2 = :code',
            [':code' => 'US']
        );
        self::assertIsArray($one);
        self::assertArrayHasKey('country_name', $one);
        self::assertContains('United States', $one);
    }

    public function testQueryMapAndGrouped(): void
    {
        $executor = new SqliteStatementExecutor($this->makePdo());

        $map = $executor->queryMap(
            'SELECT alpha2, country_name FROM countries ORDER BY alpha2 ASC',
            'alpha2',
            'country_name'
        );
        self::assertArrayHasKey('FR', $map);
        self::assertContains('France', $map);

        $grouped = $executor->queryGrouped(
            'SELECT region_name, alpha2, country_name FROM countries ORDER BY region_name ASC, country_name ASC',
            'region_name',
            'alpha2',
            'country_name'
        );
        self::assertArrayHasKey('Europe', $grouped);
        self::assertArrayHasKey('Americas', $grouped);
        self::assertArrayHasKey('FR', $grouped['Europe']);
        self::assertArrayHasKey('US', $grouped['Americas']);
        self::assertContains('France', $grouped['Europe']);
        self::assertContains('United States', $grouped['Americas']);
    }
}
