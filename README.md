# PHP Countries Data

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
- des `Country` value objects
- des collections immutables et chaînables
- plusieurs formats de stockage, avec SQLite comme défaut

Le nom principal de la classe de service est désormais :

```php
Iriven\WorldDatasets
```

L’alias de compatibilité historique `WorldCountriesDatas` est conservé, mais déprécié.

---

# 2. Architecture

## 2.1 Composants principaux

- `WorldDatasets` : service central
- `Countries` : alias concret prêt à l’emploi
- `CountriesServiceFactory` : point d’entrée canonique
- `Country` : représentation d’un pays
- `CurrencyInfo`, `RegionInfo`, `SubRegionInfo`, `PhoneInfo` : value objects
- `CountriesCollection`, `CurrenciesCollection`, `RegionsCollection`
- `CountriesQuery`
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
use Iriven\CountriesServiceFactory;

require_once __DIR__ . '/vendor/autoload.php';

$countries = CountriesServiceFactory::make();
```

Cela charge :

```text
src/data/.countriesRepository.sqlite
```

## 3.2 Chargement explicite

```php
CountriesServiceFactory::make(Iriven\CountriesServiceFactory::defaultSqlitePath());
CountriesServiceFactory::make(Iriven\CountriesServiceFactory::defaultJsonPath());
CountriesServiceFactory::make(Iriven\CountriesServiceFactory::defaultCsvPath());
```

## 3.3 Configuration runtime

```php
use Iriven\CountriesRuntimeConfig;
use Iriven\CountriesServiceFactory;

$config = new CountriesRuntimeConfig(
    sourcePath: Iriven\CountriesServiceFactory::defaultSqlitePath(),
    verifyChecksum: true,
    strictValidation: true,
);

$countries = CountriesServiceFactory::fromConfig($config);
```

## 3.4 Vérification stricte au bootstrap

```php
$countries = CountriesServiceFactory::makeWithValidation();
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
use Iriven\CountriesServiceFactory;

$countries = CountriesServiceFactory::make();

echo $countries->country('FR')->name();
echo $countries->country('250')->tld();
echo $countries->country('FRA')->currency()->code();

print_r($countries->country('FRA')->data());
print_r($countries->currencies()->list());
print_r($countries->countries()->alpha3()->list());
```

---

# 6. Inventaire complet des méthodes publiques

## 6.1 `Iriven\CountriesServiceFactory`

| Méthode | Retour | Description |
|---|---:|---|
| `make(?string $sourcePath = null)` | `Countries` | Construit le service principal |
| `fromConfig(CountriesRuntimeConfig $config, ?SimpleCacheInterface $cache = null)` | `Countries` | Construit depuis une config runtime |
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
| `currencies()` | `CurrenciesCollection` | Collection de devises |
| `regions()` | `RegionsCollection` | Collection de régions |
| `meta()` | `MetaInfo` | Métadonnées package/dataset |
| `query()` | `CountriesQuery` | Query builder fluide |
| `findByName(string $name)` | `array<Country>` | Recherche exacte par nom |
| `searchCountries(string $term)` | `array<Country>` | Recherche partielle |
| `findByCurrencyCode(string $currencyCode)` | `array<Country>` | Filtre par devise |
| `findByRegion(string $region)` | `array<Country>` | Filtre par région |
| `findByPhoneCode(string $phoneCode)` | `array<Country>` | Filtre par indicatif |
| `findByTld(string $tld)` | `array<Country>` | Filtre par TLD |

## 6.3 `Iriven\Country`

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

## 6.4 `Iriven\CountriesCollection`

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
| `stats()` | `CountriesStats` | Statistiques |
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

## 6.5 `Iriven\CurrenciesCollection`

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

## 6.6 `Iriven\RegionsCollection`

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

## 6.7 `Iriven\CountriesQuery`

| Méthode | Retour |
|---|---:|
| `inRegion(string $name)` | `CountriesQuery` |
| `inSubRegion(string $name)` | `CountriesQuery` |
| `withCurrency(string $code)` | `CountriesQuery` |
| `withPhoneCode(string $code)` | `CountriesQuery` |
| `withTld(string $tld)` | `CountriesQuery` |
| `matching(string $term)` | `CountriesQuery` |
| `sortByName()` | `CountriesQuery` |
| `sortByCode()` | `CountriesQuery` |
| `sortByNumeric()` | `CountriesQuery` |
| `limit(int $limit)` | `CountriesQuery` |
| `offset(int $offset, int $limit = PHP_INT_MAX)` | `CountriesQuery` |
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

### `Iriven\CountriesStats`
- `total(): int`
- `regions(): int`
- `currencies(): int`
- `toArray(): array`
- `jsonSerialize(): array`

## 6.9 Validation et configuration

### `Iriven\DatasetValidator`
- `validate(array $countries, bool $strict = true): DatasetValidationReport`

### `Iriven\DatasetValidationReport`
- `duplicates(): array`
- `invalidCodes(): array`
- `warnings(): array`
- `strict(): bool`
- `isValid(): bool`
- `toArray(): array`
- `jsonSerialize(): array`

### `Iriven\CountriesRuntimeConfig`
- `sourcePath(): ?string`
- `verifyChecksum(): bool`
- `strictValidation(): bool`
- `usePsr16Cache(): bool`

### `Iriven\CountryCodeNormalizer`
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
$countries->countries()->alpha2()->list();
$countries->countries()->alpha3()->list();
$countries->countries()->numeric()->list();

$countries->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->paginate(0, 25)
    ->values();
```

## 7.2 Exemples query builder

```php
$result = $countries->query()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->limit(20)
    ->get();
```

## 7.3 Exemples fonctionnels

```php
$names = $countries->countries()->map(fn (Iriven\Country $country) => $country->name());

$eurCountries = $countries->countries()->filter(
    fn (Iriven\Country $country) => $country->hasCurrency('EUR')
);

$total = $countries->countries()->reduce(
    fn (int $carry, Iriven\Country $country) => $carry + 1,
    0
);
```

---

# 8. Exports

```php
$json = $countries->countries()->toJson();
$csv = $countries->countries()->toCsv();

$countries->countries()->exportJsonFile('/tmp/countries.json');
$countries->countries()->exportCsvFile('/tmp/countries.csv');

$countries->currencies()->exportJsonFile('/tmp/currencies.json');
$countries->regions()->exportCsvFile('/tmp/regions.csv');
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
$countries = Iriven\CountriesServiceFactory::makeWithValidation();
```

ou

```php
$config = new Iriven\CountriesRuntimeConfig(
    verifyChecksum: true,
    strictValidation: true,
);

$countries = Iriven\CountriesServiceFactory::fromConfig($config);
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
  Iriven\Countries:
    factory: ['Iriven\CountriesServiceFactory', 'make']
```

## 11.2 Avec validation stricte

```yaml
services:
  Iriven\CountriesRuntimeConfig:
    arguments:
      $sourcePath: '%kernel.project_dir%/vendor/iriven/php-countries-data/src/data/.countriesRepository.sqlite'
      $verifyChecksum: true
      $strictValidation: true
      $usePsr16Cache: false

  Iriven\Countries:
    factory: ['Iriven\CountriesServiceFactory', 'fromConfig']
    arguments:
      $config: '@Iriven\CountriesRuntimeConfig'
```

## 11.3 Exemple de contrôleur

```php
<?php

namespace App\Controller;

use Iriven\Countries;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class CountryController extends AbstractController
{
    #[Route('/countries/{code}', methods: ['GET'])]
    public function show(Countries $countries, string $code): JsonResponse
    {
        $country = $countries->country($code);

        return $this->json([
            'country' => $country->toArray(),
            'currency' => $country->currency()->toArray(),
            'region' => $country->region()->toArray(),
            'phone' => $country->phone()->toArray(),
        ]);
    }

    #[Route('/countries/europe/eur', methods: ['GET'])]
    public function euroCountries(Countries $countries): JsonResponse
    {
        return $this->json(
            $countries->countries()
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

use Iriven\Countries;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:countries:doctor')]
final class CountriesDoctorCommand extends Command
{
    public function __construct(
        private readonly Countries $countries,
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
src/Bridge/Laravel/CountriesServiceProvider.php
```

## 12.2 Enregistrement

Dans `config/app.php` ou auto-discovery selon votre packaging :

```php
Iriven\Bridge\Laravel\CountriesServiceProvider::class,
```

## 12.3 Utilisation dans un contrôleur

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Iriven\Countries;

final class CountryController
{
    public function show(Countries $countries, string $code): JsonResponse
    {
        $country = $countries->country($code);

        return response()->json([
            'country' => $country->toArray(),
            'currency' => $country->currency()->toArray(),
            'region' => $country->region()->toArray(),
            'phone' => $country->phone()->toArray(),
        ]);
    }

    public function euroCountries(Countries $countries): JsonResponse
    {
        return response()->json(
            $countries->countries()
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

use Iriven\Countries;

final class ShippingService
{
    public function __construct(
        private readonly Countries $countries,
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
$countries = app(Iriven\Countries::class);

$france = $countries->country('FR');
$list = $countries->countries()->alpha2()->list();
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
- factory canonique : `CountriesServiceFactory`
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
