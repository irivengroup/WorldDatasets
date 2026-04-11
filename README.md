
# PHP World Datasets

[![Build Status](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/badges/build.png?b=master)](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/irivengroup/WorldDatasets/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)
[![GitHub license](https://img.shields.io/badge/license-AGPL-blue.svg)](https://github.com/irivengroup/WorldDatasets/blob/master/LICENSE)

Bibliothèque PHP orientée entreprise pour consulter, filtrer, exporter et valider des données pays avec source principale SQLite, sources dérivées JSON/CSV, collections fluentes, value objects, pipeline de build et intégrations Symfony/Laravel.

---

# Sommaire

1. Présentation
2. Architecture
3. Sources de données
4. Installation
5. Démarrage rapide
6. Inventaire complet des méthodes publiques
7. Collections et query builder
8. Exports
9. Validation, checksums et pipeline data
10. CLI et health check
11. Intégration Symfony
12. Intégration Laravel
13. Tests, CI et qualité
14. Conventions de nommage
15. Fichiers du projet

---

# 1. Présentation

Le projet expose une API moderne autour de quatre idées :
- un service principal de consultation
- des value objects
- des collections immutables et chaînables
- plusieurs formats de stockage, avec SQLite comme défaut

Le nom principal de la classe de service est désormais :

```php
Iriven\WorldDatasets
```
---

# 2. Architecture

## 2.1 Composants principaux

- `WorldDatasets` : service central
- `Countries` : alias concret prêt à l’emploi
- `WorldDatasetsFactory` : point d’entrée canonique
- `Country` : représentation d’un pays
- `CurrencyInfo`, `RegionInfo`, `SubRegionInfo`, `PhoneInfo` : value objects
- `CountriesCollection`, `CurrencyCollection`, `RegionCollection`
- `WorldDatasetsQuery`
- `DatasetValidator`
- `JsonCountryRepository`, `CsvCountryRepository`, `SqliteCountryRepository`, `ArrayCountryRepository`

## 2.2 Source de vérité

Les fichiers présents dans `src/data` sont la seule source de vérité du projet.

| Type | Fichier | Rôle |
|---|---|---|
| SQLite | `src/data/.countriesRepository.sqlite` | source principale par défaut |
| JSON | `src/data/.countriesRepository.json` | interopérabilité, debug |
| CSV | `src/data/countriesRepository.csv` | export tableur |
| Metadata | `src/data/.countriesRepository.meta.json` | version de dataset, date de build, checksums |
| SHA256 | `src/data/.countriesRepository.sha256` | empreintes de contrôle |

---

# 3. Sources de données

## 3.1 Chargement par défaut

```php
use Iriven\WorldDatasets\WorldDatasetsFactory;

require_once __DIR__ . '/vendor/autoload.php';

$worldDatasets = WorldDatasetsFactory::make();
```

Cela charge :

```text
src/data/.countriesRepository.sqlite
```

## 3.2 Chargement explicite

```php
WorldDatasetsFactory::make(Iriven\WorldDatasets\WorldDatasetsFactory::defaultSqlitePath());
WorldDatasetsFactory::make(Iriven\WorldDatasets\WorldDatasetsFactory::defaultJsonPath());
WorldDatasetsFactory::make(Iriven\WorldDatasets\WorldDatasetsFactory::defaultCsvPath());
```

## 3.3 Configuration runtime

```php
use Iriven\WorldDatasets\WorldDatasetsRuntimeConfig;
use Iriven\WorldDatasets\WorldDatasetsFactory;

$config = new WorldDatasetsRuntimeConfig(
    sourcePath: Iriven\WorldDatasets\WorldDatasetsFactory::defaultSqlitePath(),
    verifyChecksum: true,
    strictValidation: true,
);

$worldDatasets = WorldDatasetsFactory::fromConfig($config);
```

## 3.4 Vérification stricte au bootstrap

```php
$worldDatasets = WorldDatasetsFactory::makeWithValidation();
```

---

# 4. Installation

```bash
composer install
composer run build-data
composer run check-data
composer run doctor
composer run analyse
composer test
```

---

# 5. Démarrage rapide

```php
use Iriven\WorldDatasets\WorldDatasetsFactory;

$worldDatasets = WorldDatasetsFactory::make(); //SQLite

echo $worldDatasets->country('FR')->name();
echo $worldDatasets->country('250')->tld();
echo $worldDatasets->country('FRA')->currency()->code();

print_r($worldDatasets->country('FRA')->data());
print_r($worldDatasets->currencies()->list());
print_r($worldDatasets->countries()->alpha3()->list());
```

---

# 6. Inventaire complet des méthodes publiques

## 6.1 `Iriven\WorldDatasets\WorldDatasetsFactory`

| Méthode | Retour | Description |
|---|---:|---|
| `make(?string $sourcePath = null)` | `Countries` | Construit le service principal |
| `fromConfig(WorldDatasetsRuntimeConfig $config, ?SimpleCacheInterface $cache = null)` | `Countries` | Construit depuis une config runtime |
| `makeWithValidation(?string $sourcePath = null)` | `Countries` | Construit avec checksum + validation stricte |
| `makeRepository(string $path, ?SimpleCacheInterface $cache = null)` | `CountryRepositoryInterface` | Résout le repository selon la source |
| `defaultSqlitePath()` | `string` | Chemin SQLite par défaut |
| `defaultJsonPath()` | `string` | Chemin JSON par défaut |
| `defaultCsvPath()` | `string` | Chemin CSV par défaut |
| `datasetMetaPath()` | `string` | Fichier metadata dataset |
| `datasetShaPath()` | `string` | Fichier sha256 dataset |
| `datasetVersion()` | `string` | Version du dataset |
| `builtAt()` | `?string` | Date de build du dataset |
| `checksumFor(string $path)` | `?string` | SHA256 d’une source |
| `assertChecksum(string $path)` | `void` | Vérifie l’intégrité d’une source |

## 6.2 `Iriven\WorldDatasets`

| Méthode | Retour | Description |
|---|---:|---|
| `all()` | `array` | Tous les pays au format associatif |
| `iterator(int|string|CountryCodeFormat $format = CountryCodeFormat::ALPHA2)` | `Generator` | Itérateur indexé |
| `count()` | `int` | Nombre total de pays |
| `getIterator()` | `Traversable` | Support natif `foreach` |
| `country(string $code)` | `Country` | Résolution stricte d’un pays |
| `findCountry(string $code)` | `?Country` | Résolution tolérante |
| `countries(int|string|CountryCodeFormat $format = CountryCodeFormat::ALPHA2)` | `CountriesCollection` | Collection de pays |
| `currencies()` | `CurrencyCollection` | Collection de devises |
| `regions()` | `RegionCollection` | Collection de régions |
| `meta()` | `MetaInfo` | Métadonnées package/dataset |
| `query()` | `WorldDatasetsQuery` | Query builder fluide |
| `findByName(string $name)` | `array<Country>` | Recherche exacte par nom |
| `searchCountries(string $term)` | `array<Country>` | Recherche partielle |
| `findByCurrencyCode(string $currencyCode)` | `array<Country>` | Filtre par devise |
| `findByRegion(string $region)` | `array<Country>` | Filtre par région |
| `findByPhoneCode(string $phoneCode)` | `array<Country>` | Filtre par indicatif |
| `findByTld(string $tld)` | `array<Country>` | Filtre par TLD |

## 6.3 `Iriven\WorldDatasets\Country`

| Méthode | Retour |
|---|---:|
| `alpha2()` | `string` |
| `alpha3()` | `string` |
| `numeric()` | `string` |
| `name()` | `string` |
| `capital()` | `string` |
| `tld()` | `string` |
| `language()` | `string` |
| `languages()` | `string` |
| `postalCodePattern()` | `string` |
| `currency()` | `CurrencyInfo` |
| `region()` | `RegionInfo` |
| `phone()` | `PhoneInfo` |
| `isInRegion(string $region)` | `bool` |
| `hasCurrency(string $code)` | `bool` |
| `exists()` | `bool` |
| `data()` | `array` |
| `toArray()` | `array` |
| `toIndexedArray()` | `array` |
| `all()` | `array` |
| `jsonSerialize()` | `array` |
| `__toString()` | `string` |

## 6.4 `Iriven\WorldDatasets\CountriesCollection`

| Méthode | Retour | Description |
|---|---:|---|
| `alpha2()` | `CountriesCollection` | Format alpha2 |
| `alpha3()` | `CountriesCollection` | Format alpha3 |
| `numeric()` | `CountriesCollection` | Format numeric |
| `inRegion(string $name)` | `CountriesCollection` | Filtre région |
| `inSubRegion(string $name)` | `CountriesCollection` | Filtre sous-région |
| `withCurrency(string $code)` | `CountriesCollection` | Filtre devise |
| `withPhoneCode(string $code)` | `CountriesCollection` | Filtre indicatif |
| `withTld(string $tld)` | `CountriesCollection` | Filtre TLD |
| `named(string $name)` | `CountriesCollection` | Filtre exact nom |
| `matching(string $term)` | `CountriesCollection` | Recherche partielle |
| `sortByName()` | `CountriesCollection` | Tri par nom |
| `sortByCode()` | `CountriesCollection` | Tri par code courant |
| `sortByNumeric()` | `CountriesCollection` | Tri par numeric |
| `paginate(int $offset, int $limit)` | `CountriesCollection` | Pagination |
| `first()` | `?Country` | Premier pays |
| `last()` | `?Country` | Dernier pays |
| `values()` | `array<Country>` | Liste d’objets |
| `names()` | `array<string,string>` | Alias de list |
| `codes()` | `array<int,string>` | Liste des codes |
| `count()` | `int` | Taille collection |
| `isEmpty()` | `bool` | Collection vide |
| `isNotEmpty()` | `bool` | Collection non vide |
| `contains(string $code)` | `bool` | Contient un pays par code |
| `containsCountry(callable|Country|string $value)` | `bool` | Vérification avancée |
| `chunk(int $size)` | `array<CountriesCollection>` | Découpage par paquets |
| `stats()` | `WorldDatasetsStats` | Statistiques |
| `groupByRegion()` | `array` | Groupement région |
| `groupByCurrency()` | `array` | Groupement devise |
| `pluckNames()` | `array` | Liste simple des noms |
| `pluckCodes()` | `array` | Liste simple des codes |
| `map(callable $callback)` | `array` | Transformation fonctionnelle |
| `filter(callable $callback)` | `CountriesCollection` | Filtrage fonctionnel |
| `reduce(callable $callback, mixed $initial = null)` | `mixed` | Réduction fonctionnelle |
| `list()` | `array<string,string>` | Code => nom |
| `exportArray()` | `array` | Export tabulaire |
| `toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)` | `string` | Export JSON |
| `toCsv()` | `string` | Export CSV |
| `exportJsonFile(string $path)` | `void` | Écrit un fichier JSON |
| `exportCsvFile(string $path)` | `void` | Écrit un fichier CSV |
| `toStorageArray()` | `array` | Export technique |
| `toApiArray()` | `array` | Export API |
| `toArray()` | `array` | Alias export |
| `jsonSerialize()` | `array` | JSON serializable |

## 6.5 `Iriven\CurrencyCollection`

| Méthode | Retour |
|---|---:|
| `values()` | `array<CurrencyInfo>` |
| `list()` | `array<string,string>` |
| `countries()` | `array<string,array<string,string>>` |
| `exportArray()` | `array` |
| `toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)` | `string` |
| `toCsv()` | `string` |
| `exportJsonFile(string $path)` | `void` |
| `exportCsvFile(string $path)` | `void` |
| `toArray()` | `array` |
| `jsonSerialize()` | `array` |

## 6.6 `Iriven\RegionCollection`

| Méthode | Retour |
|---|---:|
| `values()` | `array<RegionInfo>` |
| `list()` | `array<string,string>` |
| `countries()` | `array<string,array<string,string>>` |
| `exportArray()` | `array` |
| `toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)` | `string` |
| `toCsv()` | `string` |
| `exportJsonFile(string $path)` | `void` |
| `exportCsvFile(string $path)` | `void` |
| `toArray()` | `array` |
| `jsonSerialize()` | `array` |

## 6.7 `Iriven\WorldDatasets\WorldDatasetsQuery`

| Méthode | Retour |
|---|---:|
| `inRegion(string $name)` | `WorldDatasetsQuery` |
| `inSubRegion(string $name)` | `WorldDatasetsQuery` |
| `withCurrency(string $code)` | `WorldDatasetsQuery` |
| `withPhoneCode(string $code)` | `WorldDatasetsQuery` |
| `withTld(string $tld)` | `WorldDatasetsQuery` |
| `matching(string $term)` | `WorldDatasetsQuery` |
| `sortByName()` | `WorldDatasetsQuery` |
| `sortByCode()` | `WorldDatasetsQuery` |
| `sortByNumeric()` | `WorldDatasetsQuery` |
| `limit(int $limit)` | `WorldDatasetsQuery` |
| `offset(int $offset, int $limit = PHP_INT_MAX)` | `WorldDatasetsQuery` |
| `get()` | `array<Country>` |
| `list()` | `array<string,string>` |

## 6.8 Value objects

### `Iriven\CurrencyInfo`
- `code(): string`
- `name(): string`
- `toArray(): array`
- `jsonSerialize(): array`
- `__toString(): string`

### `Iriven\RegionInfo`
- `alphaCode(): string`
- `numericCode(): string`
- `name(): string`
- `subRegion(): SubRegionInfo`
- `toArray(): array`
- `jsonSerialize(): array`
- `__toString(): string`

### `Iriven\SubRegionInfo`
- `code(): string`
- `Code(): string`
- `name(): string`
- `Name(): string`
- `toArray(): array`
- `jsonSerialize(): array`
- `__toString(): string`

### `Iriven\PhoneInfo`
- `code(): string`
- `internationalPrefix(): string`
- `nationalPrefix(): string`
- `subscriberPattern(): string`
- `pattern(): string`
- `toArray(): array`
- `jsonSerialize(): array`
- `__toString(): string`

### `Iriven\MetaInfo`
- `count(): int`
- `source(): string`
- `version(): string`
- `lastUpdatedAt(): ?string`
- `packageVersion(): string`
- `datasetVersion(): string`
- `checksum(): ?string`
- `builtAt(): ?string`
- `toArray(): array`
- `jsonSerialize(): array`

### `Iriven\WorldDatasets\WorldDatasetsStats`
- `total(): int`
- `regions(): int`
- `currencies(): int`
- `toArray(): array`
- `jsonSerialize(): array`

## 6.9 Validation et configuration

### `Iriven\DatasetValidator`
- `validate(array $worldDatasets, bool $strict = true): DatasetValidationReport`

### `Iriven\DatasetValidationReport`
- `duplicates(): array`
- `invalidCodes(): array`
- `warnings(): array`
- `strict(): bool`
- `isValid(): bool`
- `toArray(): array`
- `jsonSerialize(): array`

### `Iriven\WorldDatasets\WorldDatasetsRuntimeConfig`
- `sourcePath(): ?string`
- `verifyChecksum(): bool`
- `strictValidation(): bool`
- `usePsr16Cache(): bool`

### `Iriven\WorldDatasets\CountryCodeNormalizer`
- `normalize(string $code): string`
- `normalizeAlpha(string $code): string`
- `normalizeNumeric(string $code): string`
- `normalizePreservingNumeric(string $code): string`
- `normalizeTld(string $tld): string`

### `Iriven\PhoneCodeNormalizer`
- `normalize(string $code): string`

### `Iriven\TldNormalizer`
- `normalize(string $tld): string`

---

# 7. Collections et query builder

## 7.1 Exemples de collections

```php
$worldDatasets->countries()->alpha2()->list();
$worldDatasets->countries()->alpha3()->list();
$worldDatasets->countries()->numeric()->list();

$worldDatasets->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->paginate(0, 25)
    ->values();
```

## 7.2 Exemples query builder

```php
$result = $worldDatasets->query()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->limit(20)
    ->get();
```

## 7.3 Exemples fonctionnels

```php
$names = $worldDatasets->countries()->map(fn (Iriven\WorldDatasets\Country $country) => $country->name());

$eurCountries = $worldDatasets->countries()->filter(
    fn (Iriven\WorldDatasets\Country $country) => $country->hasCurrency('EUR')
);

$total = $worldDatasets->countries()->reduce(
    fn (int $carry, Iriven\WorldDatasets\Country $country) => $carry + 1,
    0
);
```

---

# 8. Exports

```php
$json = $worldDatasets->countries()->toJson();
$csv = $worldDatasets->countries()->toCsv();

$worldDatasets->countries()->exportJsonFile('/tmp/countries.json');
$worldDatasets->countries()->exportCsvFile('/tmp/countries.csv');

$worldDatasets->currencies()->exportJsonFile('/tmp/currencies.json');
$worldDatasets->regions()->exportCsvFile('/tmp/regions.csv');
```

---

# 9. Validation, checksums et pipeline data

## 9.1 Build complet des données

```bash
composer run build-data
```

Cette commande :
- relit la source courante
- normalise les enregistrements
- régénère SQLite, JSON, CSV
- génère un rapport de validation
- génère les checksums
- régénère les métadonnées

## 9.2 Validation

```bash
composer run check-data
composer run validate-data -- --strict
```

## 9.3 Checksum

```php
$worldDatasets = Iriven\WorldDatasets\WorldDatasetsFactory::makeWithValidation();
```

ou

```php
$config = new Iriven\WorldDatasets\WorldDatasetsRuntimeConfig(
    verifyChecksum: true,
    strictValidation: true,
);

$worldDatasets = Iriven\WorldDatasets\WorldDatasetsFactory::fromConfig($config);
```

---

# 10. CLI et health check

## 10.1 CLI métier

```bash
composer run countries -- list alpha2
composer run countries -- show FR
composer run countries -- search france
composer run countries -- export countries json
composer run countries -- validate --strict
```

## 10.2 Doctor

```bash
composer run doctor
```

Cette commande vérifie :
- la présence des fichiers de données
- la capacité à charger le service
- la résolution de pays de référence

---

# 11. Intégration Symfony

## 11.1 Déclaration de service simple

```yaml
services:
  Iriven\WorldDatasets\WorldDatasetsService:
    factory: ['Iriven\WorldDatasets\WorldDatasetsFactory', 'make']
```

## 11.2 Avec validation stricte

```yaml
services:
  Iriven\WorldDatasets\WorldDatasetsRuntimeConfig:
    arguments:
      $sourcePath: '%kernel.project_dir%/vendor/iriven/php-world-datasets/src/data/.countriesRepository.sqlite'
      $verifyChecksum: true
      $strictValidation: true
      $usePsr16Cache: false

  Iriven\WorldDatasets\WorldDatasetsService:
    factory: ['Iriven\WorldDatasets\WorldDatasetsFactory', 'fromConfig']
    arguments:
      $config: '@Iriven\WorldDatasets\WorldDatasetsRuntimeConfig'
```

## 11.3 Exemple de contrôleur

```php
<?php

namespace App\Controller;

use Iriven\WorldDatasets\WorldDatasetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class CountryController extends AbstractController
{
    #[Route('/countries/{code}', methods: ['GET'])]
    public function show(Countries $worldDatasets, string $code): JsonResponse
    {
        $country = $worldDatasets->country($code);

        return $this->json([
            'country' => $country->toArray(),
            'currency' => $country->currency()->toArray(),
            'region' => $country->region()->toArray(),
            'phone' => $country->phone()->toArray(),
        ]);
    }

    #[Route('/countries/europe/eur', methods: ['GET'])]
    public function euroCountries(Countries $worldDatasets): JsonResponse
    {
        return $this->json(
            $worldDatasets->countries()
                ->inRegion('Europe')
                ->withCurrency('EUR')
                ->alpha2()
                ->list()
        );
    }
}
```

## 11.4 Exemple de commande Symfony

```php
<?php

namespace App\Command;

use Iriven\WorldDatasets\WorldDatasetsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:countries:doctor')]
final class CountriesDoctorCommand extends Command
{
    public function __construct(
        private readonly Countries $worldDatasets,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Countries count: ' . $this->countries->count());
        $output->writeln('Source: ' . $this->countries->meta()->source());

        return Command::SUCCESS;
    }
}
```

---

# 12. Intégration Laravel

## 12.1 Service Provider

Le provider fourni se trouve dans :

```text
src/Bridge/Laravel/WorldDatasetsServiceProvider.php
```

## 12.2 Enregistrement

Dans `config/app.php` ou auto-discovery selon votre packaging :

```php
Iriven\WorldDatasets\Bridge\Laravel\WorldDatasetsServiceProvider::class,
```

## 12.3 Utilisation dans un contrôleur

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Iriven\WorldDatasets\WorldDatasetsService;

final class CountryController
{
    public function show(Countries $worldDatasets, string $code): JsonResponse
    {
        $country = $worldDatasets->country($code);

        return response()->json([
            'country' => $country->toArray(),
            'currency' => $country->currency()->toArray(),
            'region' => $country->region()->toArray(),
            'phone' => $country->phone()->toArray(),
        ]);
    }

    public function euroCountries(Countries $worldDatasets): JsonResponse
    {
        return response()->json(
            $worldDatasets->countries()
                ->inRegion('Europe')
                ->withCurrency('EUR')
                ->alpha3()
                ->list()
        );
    }
}
```

## 12.4 Utilisation dans un job / service

```php
<?php

namespace App\Services;

use Iriven\WorldDatasets\WorldDatasetsService;

final class ShippingService
{
    public function __construct(
        private readonly Countries $worldDatasets,
    ) {
    }

    public function supportedDestinations(): array
    {
        return $this->countries
            ->countries()
            ->withTld('.fr')
            ->values();
    }
}
```

## 12.5 Utilisation directe via le container

```php
$worldDatasets = app(Iriven\WorldDatasets\WorldDatasetsService::class);

$france = $worldDatasets->country('FR');
$list = $worldDatasets->countries()->alpha2()->list();
```

---

# 13. Tests, CI et qualité

## 13.1 Tests

```bash
composer test
```

Les tests couvrent :
- résolution de pays de référence
- stabilité des codes
- régression de dataset
- exports
- query builder
- validation

## 13.2 Analyse statique

```bash
composer run analyse
```

## 13.3 CI

Le workflow GitHub Actions :
- installe les dépendances
- reconstruit les données
- vérifie les checksums
- lance le doctor
- exécute PHPStan
- exécute PHPUnit
- échoue si des fichiers générés diffèrent du repo

---

# 14. Conventions de nommage

- classe de service principale : `WorldDatasets`
- alias concret recommandé : `Countries`
- factory canonique : `WorldDatasetsFactory`
- source par défaut : SQLite
- source JSON et CSV : dérivées, mais maintenues

---

# 15. Fichiers du projet

## 15.1 Documentation
- `README.md`
- `CHANGELOG.md`
- `CONTRIBUTING.md`
- `docs/compatibility.md`

## 15.2 Scripts
- `bin/build_data.php`
- `bin/check_data.php`
- `bin/import_countries.php`
- `bin/validate_countries.php`
- `bin/countries`
- `bin/countries-doctor`

## 15.3 Données
- `src/data/.countriesRepository.sqlite`
- `src/data/.countriesRepository.json`
- `src/data/countriesRepository.csv`
- `src/data/.countriesRepository.meta.json`
- `src/data/.countriesRepository.sha256`

## 15.4 Schémas
- `src/data/schema/country.schema.json`
- `src/data/schema/countries.sql`

---

# Commandes utiles

```bash
composer install
composer run build-data
composer run check-data
composer run doctor
composer run analyse
composer test
```


---

# Utilisation avancée & chainage

## Exemple complet

```php
$worldDatasets->countries()
    ->inRegion('Europe')
    ->inSubRegion('Western Europe')
    ->withCurrency('EUR')
    ->matching('fr')
    ->sortByName()
    ->paginate(0, 10)
    ->values();
```

## Pipeline

```bash
composer run build-data
composer run check-data
composer run doctor
```

## Patterns

- Query fluente
- immutabilité
- séparation data/build/runtime
- source SQLite par défaut


---

# Addendum — inventaire des chainages possibles

Cette section complète le README avec un inventaire explicite des enchaînements autorisés sur les **value objects**, les **filtres**, les **collections** et le **query builder**.

> Convention utilisée ci-dessous :
>
> - `$worldDatasets` est une instance de `Iriven\WorldDatasets\WorldDatasetsService`
> - `country()` retourne un `Country`
> - `countries()` retourne une `CountriesCollection`
> - `currencies()` retourne une `CurrencyCollection`
> - `regions()` retourne une `RegionCollection`
> - `query()` retourne une `WorldDatasetsQuery`

## 1. Chainages depuis le service principal

### Point d’entrée pays

```php
$worldDatasets->country('FR');
$worldDatasets->findCountry('FR');
```

### Point d’entrée collections

```php
$worldDatasets->countries();
$worldDatasets->countries('alpha2');
$worldDatasets->countries('alpha3');
$worldDatasets->countries('numeric');

$worldDatasets->currencies();
$worldDatasets->regions();
$worldDatasets->query();
$worldDatasets->meta();
```

---

## 2. Chainages depuis `Country`

## 2.1 Accès direct aux propriétés scalaires

```php
$worldDatasets->country('FR')->alpha2();
$worldDatasets->country('FR')->alpha3();
$worldDatasets->country('FR')->numeric();
$worldDatasets->country('FR')->name();
$worldDatasets->country('FR')->capital();
$worldDatasets->country('FR')->tld();
$worldDatasets->country('FR')->language();
$worldDatasets->country('FR')->languages();
$worldDatasets->country('FR')->postalCodePattern();
$worldDatasets->country('FR')->exists();
$worldDatasets->country('FR')->data();
$worldDatasets->country('FR')->all();
$worldDatasets->country('FR')->toArray();
$worldDatasets->country('FR')->toIndexedArray();
$worldDatasets->country('FR')->jsonSerialize();
```

## 2.2 Chainages vers les value objects

```php
$worldDatasets->country('FR')->currency();
$worldDatasets->country('FR')->region();
$worldDatasets->country('FR')->phone();
```

## 2.3 Chainages métier

```php
$worldDatasets->country('FR')->hasCurrency('EUR');
$worldDatasets->country('FR')->isInRegion('Europe');
```

---

## 3. Chainages depuis `CurrencyInfo`

Point d’entrée :

```php
$currency = $worldDatasets->country('FR')->currency();
$currency->code();
$currency->name();
$currency->toArray();
$currency->jsonSerialize();
(string) $currency;
```

### Chainages complets

```php
$worldDatasets->country('FR')->currency()->code();
$worldDatasets->country('FR')->currency()->name();
$worldDatasets->country('FR')->currency()->toArray();
$worldDatasets->country('FR')->currency()->jsonSerialize();
```

---

## 4. Chainages depuis `RegionInfo`

Point d’entrée :

```php
$region = $worldDatasets->country('FR')->region();
$region->alphaCode();
$region->numericCode();
$region->name();
$region->subRegion();
$region->toArray();
$region->jsonSerialize();
(string) $region;
```

### Chainages complets

```php
$worldDatasets->country('FR')->region()->alphaCode();
$worldDatasets->country('FR')->region()->numericCode();
$worldDatasets->country('FR')->region()->name();
$worldDatasets->country('FR')->region()->toArray();
$worldDatasets->country('FR')->region()->jsonSerialize();
```

### Passage vers `SubRegionInfo`

```php
$worldDatasets->country('FR')->region()->subRegion();
$worldDatasets->country('FR')->region()->subRegion()->code();
$worldDatasets->country('FR')->region()->subRegion()->code();
$worldDatasets->country('FR')->region()->subRegion()->name();
$worldDatasets->country('FR')->region()->subRegion()->name();
$worldDatasets->country('FR')->region()->subRegion()->toArray();
$worldDatasets->country('FR')->region()->subRegion()->jsonSerialize();
```

---

## 5. Chainages possibles depuis `SubRegionInfo`

Point d’entrée :

```php
$subRegion = $worldDatasets->country('FR')->region()->subRegion();
$subRegion->code();
$subRegion->code();
$subRegion->name();
$subRegion->name();
$subRegion->toArray();
$subRegion->jsonSerialize();
(string) $subRegion;
```

---

## 6. Chainages possibles depuis `PhoneInfo`

Point d’entrée :

```php
$phone = $worldDatasets->country('FR')->phone();
$phone->code();
$phone->internationalPrefix();
$phone->nationalPrefix();
$phone->subscriberPattern();
$phone->pattern();
$phone->toArray();
$phone->jsonSerialize();
(string) $phone;
```

### Chainages complets

```php
$worldDatasets->country('FR')->phone()->code();
$worldDatasets->country('FR')->phone()->internationalPrefix();
$worldDatasets->country('FR')->phone()->nationalPrefix();
$worldDatasets->country('FR')->phone()->subscriberPattern();
$worldDatasets->country('FR')->phone()->pattern();
$worldDatasets->country('FR')->phone()->toArray();
```

---

## 7. Chainages depuis `CountriesCollection`

Point d’entrée :

```php
$collection = $worldDatasets->countries();
```

## 7.1 Sélection du format de code

```php
$worldDatasets->countries()->alpha2();
$worldDatasets->countries()->alpha3();
$worldDatasets->countries()->numeric();
```

Chainages usuels :

```php
$worldDatasets->countries()->alpha2()->list();
$worldDatasets->countries()->alpha3()->list();
$worldDatasets->countries()->numeric()->list();

$worldDatasets->countries()->alpha2()->codes();
$worldDatasets->countries()->alpha3()->codes();
$worldDatasets->countries()->numeric()->codes();
```

## 7.2 Filtres chaînables

Tous ces filtres peuvent s’enchaîner librement entre eux, puis avec les méthodes de tri, pagination, extraction ou export.

```php
$worldDatasets->countries()->inRegion('Europe');
$worldDatasets->countries()->inSubRegion('Western Europe');
$worldDatasets->countries()->withCurrency('EUR');
$worldDatasets->countries()->withPhoneCode('+33');
$worldDatasets->countries()->withTld('.fr');
$worldDatasets->countries()->named('France');
$worldDatasets->countries()->matching('fr');
```

## 7.3 Tri et pagination

```php
$worldDatasets->countries()->sortByName();
$worldDatasets->countries()->sortByCode();
$worldDatasets->countries()->sortByNumeric();
$worldDatasets->countries()->paginate(0, 10);
```

## 7.4 Accès ponctuel

```php
$worldDatasets->countries()->first();
$worldDatasets->countries()->last();
$worldDatasets->countries()->count();
$worldDatasets->countries()->isEmpty();
$worldDatasets->countries()->isNotEmpty();
$worldDatasets->countries()->contains('FR');
$worldDatasets->countries()->containsCountry('FR');
$worldDatasets->countries()->containsCountry($worldDatasets->country('FR'));
$worldDatasets->countries()->containsCountry(fn ($country) => $country->hasCurrency('EUR'));
$worldDatasets->countries()->chunk(50);
```

## 7.5 Extraction / restitution

```php
$worldDatasets->countries()->values();
$worldDatasets->countries()->names();
$worldDatasets->countries()->codes();
$worldDatasets->countries()->list();
$worldDatasets->countries()->exportArray();
$worldDatasets->countries()->toStorageArray();
$worldDatasets->countries()->toApiArray();
$worldDatasets->countries()->toArray();
$worldDatasets->countries()->jsonSerialize();
```

## 7.6 Agrégations

```php
$worldDatasets->countries()->stats();
$worldDatasets->countries()->groupByRegion();
$worldDatasets->countries()->groupByCurrency();
$worldDatasets->countries()->pluckNames();
$worldDatasets->countries()->pluckCodes();
```

## 7.7 Fonctionnel

```php
$worldDatasets->countries()->map(fn ($country) => $country->name());
$worldDatasets->countries()->filter(fn ($country) => $country->hasCurrency('EUR'));
$worldDatasets->countries()->reduce(fn ($carry, $country) => $carry + 1, 0);
```

## 7.8 Export

```php
$worldDatasets->countries()->toJson();
$worldDatasets->countries()->toCsv();
$worldDatasets->countries()->exportJsonFile('/tmp/countries.json');
$worldDatasets->countries()->exportCsvFile('/tmp/countries.csv');
```

## 7.9 Exemples de chainages libres

### Exemple 1

```php
$worldDatasets->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->alpha2()
    ->sortByName()
    ->list();
```

### Exemple 2

```php
$worldDatasets->countries()
    ->inRegion('Europe')
    ->inSubRegion('Western Europe')
    ->withCurrency('EUR')
    ->matching('fr')
    ->sortByCode()
    ->paginate(0, 20)
    ->values();
```

### Exemple 3

```php
$worldDatasets->countries()
    ->withTld('.fr')
    ->withPhoneCode('+33')
    ->alpha3()
    ->codes();
```

### Exemple 4

```php
$worldDatasets->countries()
    ->matching('united')
    ->sortByNumeric()
    ->pluckNames();
```

### Exemple 5

```php
$worldDatasets->countries()
    ->filter(fn ($country) => $country->region()->name() === 'Europe')
    ->map(fn ($country) => [
        'code' => $country->alpha2(),
        'name' => $country->name(),
        'currency' => $country->currency()->code(),
    ]);
```

### Exemple 6

```php
$worldDatasets->countries()
    ->inRegion('Asia')
    ->sortByName()
    ->exportJsonFile('/tmp/asia.json');
```

### Exemple 7

```php
$worldDatasets->countries()
    ->inRegion('Americas')
    ->groupByCurrency();
```

---

## 8. Chainages possibles depuis `CurrencyCollection`

Point d’entrée :

```php
$currencies = $worldDatasets->currencies();
$currencies->values();
$currencies->list();
$currencies->countries();
$currencies->exportArray();
$currencies->toJson();
$currencies->toCsv();
$currencies->exportJsonFile('/tmp/currencies.json');
$currencies->exportCsvFile('/tmp/currencies.csv');
$currencies->toArray();
$currencies->jsonSerialize();
```


### Chainages complets

```php
$worldDatasets->currencies()->list();
$worldDatasets->currencies()->values();
$worldDatasets->currencies()->countries();
$worldDatasets->currencies()->toJson();
$worldDatasets->currencies()->exportCsvFile('/tmp/currencies.csv');
```

---

## 9. Chainages possibles depuis `RegionCollection`

Point d’entrée :

```php
$regions = $worldDatasets->regions();
$regions->values();
$regions->list();
$regions->countries();
$regions->exportArray();
$regions->toJson();
$regions->toCsv();
$regions->exportJsonFile('/tmp/regions.json');
$regions->exportCsvFile('/tmp/regions.csv');
$regions->toArray();
$regions->jsonSerialize();
```

### Chainages complets

```php
$worldDatasets->regions()->list();
$worldDatasets->regions()->values();
$worldDatasets->regions()->countries();
$worldDatasets->regions()->toJson();
$worldDatasets->regions()->exportCsvFile('/tmp/regions.csv');
```

---

## 10. Chainages possibles depuis `WorldDatasetsQuery`

Point d’entrée :

```php
$query = $worldDatasets->query();
```

### Filtres / tri / pagination

```php
$query->inRegion('Europe');
$query->inSubRegion('Western Europe');
$query->withCurrency('EUR');
$query->withPhoneCode('+33');
$query->withTld('.fr');
$query->matching('fr');
$query->sortByName();
$query->sortByCode();
$query->sortByNumeric();
$query->limit(20);
$query->offset(0, 20);
```

### Résolution finale

```php
$query->get();
$query->list();
```

### Exemples complets

```php
$worldDatasets->query()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->limit(20)
    ->get();
```

```php
$worldDatasets->query()
    ->inRegion('Europe')
    ->inSubRegion('Western Europe')
    ->matching('fr')
    ->sortByCode()
    ->offset(0, 10)
    ->list();
```

```php
$worldDatasets->query()
    ->withTld('.fr')
    ->withPhoneCode('+33')
    ->get();
```

---

## 11. Chainages possibles depuis `MetaInfo`

Point d’entrée :

```php
$meta = $worldDatasets->meta();
$meta->count();
$meta->source();
$meta->version();
$meta->lastUpdatedAt();
$meta->packageVersion();
$meta->datasetVersion();
$meta->checksum();
$meta->builtAt();
$meta->toArray();
$meta->jsonSerialize();
```


### Chainage Direct

```php
$worldDatasets->meta()->source();
$worldDatasets->meta()->datasetVersion();
$worldDatasets->meta()->checksum();
$worldDatasets->meta()->builtAt();
$worldDatasets->meta()->toArray();
```

---

## 12. Chainages liés au factory et à la config runtime

### Factory directe

```php
Iriven\WorldDatasets\WorldDatasetsFactory::make();
Iriven\WorldDatasets\WorldDatasetsFactory::make(Iriven\WorldDatasets\WorldDatasetsFactory::defaultSqlitePath());
Iriven\WorldDatasets\WorldDatasetsFactory::make(Iriven\WorldDatasets\WorldDatasetsFactory::defaultJsonPath());
Iriven\WorldDatasets\WorldDatasetsFactory::make(Iriven\WorldDatasets\WorldDatasetsFactory::defaultCsvPath());
```

### Factory avec config

```php
$config = new Iriven\WorldDatasets\WorldDatasetsRuntimeConfig(
    sourcePath: Iriven\WorldDatasets\WorldDatasetsFactory::defaultSqlitePath(),
    verifyChecksum: true,
    strictValidation: true,
);

$worldDatasets = Iriven\WorldDatasets\WorldDatasetsFactory::fromConfig($config);
```

### Validation stricte

```php
Iriven\WorldDatasets\WorldDatasetsFactory::makeWithValidation();
```

### Checksum

```php
Iriven\WorldDatasets\WorldDatasetsFactory::assertChecksum(
    Iriven\WorldDatasets\WorldDatasetsFactory::defaultSqlitePath()
);
```

---

## 13. Résumé des familles de chainage

### 13.1 Service -> Country -> Value Object -> propriété

```php
$worldDatasets->country('FR')->region()->subRegion()->name();
$worldDatasets->country('FR')->currency()->code();
$worldDatasets->country('FR')->phone()->pattern();
```

### 13.2 Service -> CountriesCollection -> filtres -> tri -> sortie

```php
$worldDatasets->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->list();
```

### 13.3 Service -> CountriesCollection -> fonctionnel -> sortie

```php
$worldDatasets->countries()
    ->filter(fn ($country) => $country->hasCurrency('EUR'))
    ->map(fn ($country) => $country->name());
```

### 13.4 Service -> Query -> résultat

```php
$worldDatasets->query()
    ->matching('fr')
    ->limit(10)
    ->get();
```

### 13.5 Service -> collection spécialisée -> export

```php
$worldDatasets->currencies()->exportJsonFile('/tmp/currencies.json');
$worldDatasets->regions()->exportCsvFile('/tmp/regions.csv');
```

---

## 14. Important

Les chainages ci-dessus sont donnés **sans restriction artificielle** :  
tout ce qui est exposé publiquement par les objets peut être combiné dans l’ordre logique du type retourné.

En pratique :

- un `Country` peut chaîner vers ses value objects
- une `CountriesCollection` peut enchaîner filtres, tri, pagination, extraction, agrégation, export
- un `WorldDatasetsQuery` peut enchaîner filtres, tri et pagination avant `get()` ou `list()`
- `CurrencyCollection` et `RegionCollection` peuvent aller jusqu’à l’export final


---

# Optimisations internes appliquées

Sans modification de la logique métier ni de l’API publique, cette version ajoute :

- index mémoire pour les lookups `alpha2`, `alpha3`, `numeric`
- cache interne des résultats de collections :
  - `list()`
  - `codes()`
  - `names()`
  - `exportArray()`
  - `stats()`
  - `groupByRegion()`
  - `groupByCurrency()`
- réduction des instanciations répétées des normalizers et value objects
- factorisation des transformations de tableaux via `CountryArrayTransformer`
- lecture unique des métadonnées et checksums dans `WorldDatasetsFactory`
- optimisation des index SQLite
- support de requêtes partielles côté repository SQLite via :
  - `iterateAllLazy(int $limit = 500)`
  - `iterateByRegionLazy(string $region, int $limit = 500)`

## Notes sur le lazy loading SQLite

Ces méthodes sont internes au repository SQLite et permettent de parcourir les données par lots, sans charger l’intégralité du dataset d’un coup.

Exemple conceptuel :

```php
$repository = Iriven\WorldDatasets\WorldDatasetsFactory::makeRepository(
    Iriven\WorldDatasets\WorldDatasetsFactory::defaultSqlitePath()
);

if ($repository instanceof Iriven\Infrastructure\Persistence\SqliteCountryRepository) {
    foreach ($repository->iterateAllLazy(100) as $country) {
        // traitement batch
    }
}
```

## GitHub Actions

Le workflow CI active maintenant explicitement Node 24 pour anticiper la dépréciation Node 20 :

```yaml
env:
  FORCE_JAVASCRIPT_ACTIONS_TO_NODE24: true
```


## Build determinism

Le script `bin/build_data.php` produit désormais des artefacts déterministes :

- ordre stable des enregistrements
- JSON avec fin de ligne normalisée
- métadonnées `built_at` conservées si les checksums n’ont pas changé

Cela évite les diffs parasites lors du contrôle `git diff --exit-code` après génération.



## Build idempotent

Le script `bin/build_data.php` est maintenant idempotent pour la CI :

- les fichiers texte ne sont réécrits que si leur contenu change
- la base SQLite n’est pas reconstruite quand elle est déjà la source par défaut
- `built_at` reste stable si les checksums restent identiques

Cela évite les échecs sur `git diff --exit-code` après `composer run build-data`.

