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

L’alias de compatibilité historique `WorldDatasets` est conservé, mais déprécié.

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


---

# Utilisation avancée & chainage

## Exemple complet

```php
$countries->countries()
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
> - `$countries` est une instance de `Iriven\Countries`
> - `country()` retourne un `Country`
> - `countries()` retourne une `CountriesCollection`
> - `currencies()` retourne une `CurrenciesCollection`
> - `regions()` retourne une `RegionsCollection`
> - `query()` retourne une `CountriesQuery`

## 1. Chainages depuis le service principal

### Point d’entrée pays

```php
$countries->country('FR');
$countries->findCountry('FR');
```

### Point d’entrée collections

```php
$countries->countries();
$countries->countries('alpha2');
$countries->countries('alpha3');
$countries->countries('numeric');

$countries->currencies();
$countries->regions();
$countries->query();
$countries->meta();
```

---

## 2. Chainages possibles depuis `Country`

## 2.1 Accès direct aux propriétés scalaires

```php
$countries->country('FR')->alpha2();
$countries->country('FR')->alpha3();
$countries->country('FR')->numeric();
$countries->country('FR')->name();
$countries->country('FR')->capital();
$countries->country('FR')->tld();
$countries->country('FR')->language();
$countries->country('FR')->languages();
$countries->country('FR')->postalCodePattern();
$countries->country('FR')->exists();
$countries->country('FR')->data();
$countries->country('FR')->all();
$countries->country('FR')->toArray();
$countries->country('FR')->toIndexedArray();
$countries->country('FR')->jsonSerialize();
```

## 2.2 Chainages vers les value objects

```php
$countries->country('FR')->currency();
$countries->country('FR')->region();
$countries->country('FR')->phone();
```

## 2.3 Chainages métier

```php
$countries->country('FR')->hasCurrency('EUR');
$countries->country('FR')->isInRegion('Europe');
```

---

## 3. Chainages possibles depuis `CurrencyInfo`

Point d’entrée :

```php
$currency = $countries->country('FR')->currency();
```

### Méthodes publiques

```php
$currency->code();
$currency->name();
$currency->toArray();
$currency->jsonSerialize();
(string) $currency;
```

### Exemples

```php
$countries->country('FR')->currency()->code();
$countries->country('FR')->currency()->name();
$countries->country('FR')->currency()->toArray();
```

---

## 4. Chainages possibles depuis `RegionInfo`

Point d’entrée :

```php
$region = $countries->country('FR')->region();
```

### Méthodes publiques

```php
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
$countries->country('FR')->region()->alphaCode();
$countries->country('FR')->region()->numericCode();
$countries->country('FR')->region()->name();
$countries->country('FR')->region()->toArray();
$countries->country('FR')->region()->jsonSerialize();
```

### Passage vers `SubRegionInfo`

```php
$countries->country('FR')->region()->subRegion();
$countries->country('FR')->region()->subRegion()->code();
$countries->country('FR')->region()->subRegion()->Code();
$countries->country('FR')->region()->subRegion()->name();
$countries->country('FR')->region()->subRegion()->Name();
$countries->country('FR')->region()->subRegion()->toArray();
$countries->country('FR')->region()->subRegion()->jsonSerialize();
```

---

## 5. Chainages possibles depuis `SubRegionInfo`

Point d’entrée :

```php
$subRegion = $countries->country('FR')->region()->subRegion();
```

### Méthodes publiques

```php
$subRegion->code();
$subRegion->Code();
$subRegion->name();
$subRegion->Name();
$subRegion->toArray();
$subRegion->jsonSerialize();
(string) $subRegion;
```

---

## 6. Chainages possibles depuis `PhoneInfo`

Point d’entrée :

```php
$phone = $countries->country('FR')->phone();
```

### Méthodes publiques

```php
$phone->code();
$phone->internationalPrefix();
$phone->nationalPrefix();
$phone->subscriberPattern();
$phone->pattern();
$phone->toArray();
$phone->jsonSerialize();
(string) $phone;
```

### Exemples détaillés

```php
$countries->country('FR')->phone()->code();
$countries->country('FR')->phone()->internationalPrefix();
$countries->country('FR')->phone()->nationalPrefix();
$countries->country('FR')->phone()->subscriberPattern();
$countries->country('FR')->phone()->pattern();
$countries->country('FR')->phone()->toArray();
```

---

## 7. Chainages possibles depuis `CountriesCollection`

Point d’entrée :

```php
$collection = $countries->countries();
```

## 7.1 Sélection du format de code

```php
$countries->countries()->alpha2();
$countries->countries()->alpha3();
$countries->countries()->numeric();
```

Chainages usuels :

```php
$countries->countries()->alpha2()->list();
$countries->countries()->alpha3()->list();
$countries->countries()->numeric()->list();

$countries->countries()->alpha2()->codes();
$countries->countries()->alpha3()->codes();
$countries->countries()->numeric()->codes();
```

## 7.2 Filtres chaînables

Tous ces filtres peuvent s’enchaîner librement entre eux, puis avec les méthodes de tri, pagination, extraction ou export.

```php
$countries->countries()->inRegion('Europe');
$countries->countries()->inSubRegion('Western Europe');
$countries->countries()->withCurrency('EUR');
$countries->countries()->withPhoneCode('+33');
$countries->countries()->withTld('.fr');
$countries->countries()->named('France');
$countries->countries()->matching('fr');
```

## 7.3 Tri et pagination

```php
$countries->countries()->sortByName();
$countries->countries()->sortByCode();
$countries->countries()->sortByNumeric();
$countries->countries()->paginate(0, 10);
```

## 7.4 Accès ponctuel

```php
$countries->countries()->first();
$countries->countries()->last();
$countries->countries()->count();
$countries->countries()->isEmpty();
$countries->countries()->isNotEmpty();
$countries->countries()->contains('FR');
$countries->countries()->containsCountry('FR');
$countries->countries()->containsCountry($countries->country('FR'));
$countries->countries()->containsCountry(fn ($country) => $country->hasCurrency('EUR'));
$countries->countries()->chunk(50);
```

## 7.5 Extraction / restitution

```php
$countries->countries()->values();
$countries->countries()->names();
$countries->countries()->codes();
$countries->countries()->list();
$countries->countries()->exportArray();
$countries->countries()->toStorageArray();
$countries->countries()->toApiArray();
$countries->countries()->toArray();
$countries->countries()->jsonSerialize();
```

## 7.6 Agrégations

```php
$countries->countries()->stats();
$countries->countries()->groupByRegion();
$countries->countries()->groupByCurrency();
$countries->countries()->pluckNames();
$countries->countries()->pluckCodes();
```

## 7.7 Fonctionnel

```php
$countries->countries()->map(fn ($country) => $country->name());
$countries->countries()->filter(fn ($country) => $country->hasCurrency('EUR'));
$countries->countries()->reduce(fn ($carry, $country) => $carry + 1, 0);
```

## 7.8 Export

```php
$countries->countries()->toJson();
$countries->countries()->toCsv();
$countries->countries()->exportJsonFile('/tmp/countries.json');
$countries->countries()->exportCsvFile('/tmp/countries.csv');
```

## 7.9 Exemples de chainages libres

### Exemple 1

```php
$countries->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->alpha2()
    ->sortByName()
    ->list();
```

### Exemple 2

```php
$countries->countries()
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
$countries->countries()
    ->withTld('.fr')
    ->withPhoneCode('+33')
    ->alpha3()
    ->codes();
```

### Exemple 4

```php
$countries->countries()
    ->matching('united')
    ->sortByNumeric()
    ->pluckNames();
```

### Exemple 5

```php
$countries->countries()
    ->filter(fn ($country) => $country->region()->name() === 'Europe')
    ->map(fn ($country) => [
        'code' => $country->alpha2(),
        'name' => $country->name(),
        'currency' => $country->currency()->code(),
    ]);
```

### Exemple 6

```php
$countries->countries()
    ->inRegion('Asia')
    ->sortByName()
    ->exportJsonFile('/tmp/asia.json');
```

### Exemple 7

```php
$countries->countries()
    ->inRegion('Americas')
    ->groupByCurrency();
```

---

## 8. Chainages possibles depuis `CurrenciesCollection`

Point d’entrée :

```php
$currencies = $countries->currencies();
```

### Méthodes publiques

```php
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

### Exemples

```php
$countries->currencies()->list();
$countries->currencies()->values();
$countries->currencies()->countries();
$countries->currencies()->toJson();
$countries->currencies()->exportCsvFile('/tmp/currencies.csv');
```

---

## 9. Chainages possibles depuis `RegionsCollection`

Point d’entrée :

```php
$regions = $countries->regions();
```

### Méthodes publiques

```php
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

### Exemples

```php
$countries->regions()->list();
$countries->regions()->values();
$countries->regions()->countries();
$countries->regions()->toJson();
$countries->regions()->exportCsvFile('/tmp/regions.csv');
```

---

## 10. Chainages possibles depuis `CountriesQuery`

Point d’entrée :

```php
$query = $countries->query();
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
$countries->query()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->limit(20)
    ->get();
```

```php
$countries->query()
    ->inRegion('Europe')
    ->inSubRegion('Western Europe')
    ->matching('fr')
    ->sortByCode()
    ->offset(0, 10)
    ->list();
```

```php
$countries->query()
    ->withTld('.fr')
    ->withPhoneCode('+33')
    ->get();
```

---

## 11. Chainages possibles depuis `MetaInfo`

Point d’entrée :

```php
$meta = $countries->meta();
```

### Méthodes publiques

```php
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

### Exemples

```php
$countries->meta()->source();
$countries->meta()->datasetVersion();
$countries->meta()->checksum();
$countries->meta()->builtAt();
$countries->meta()->toArray();
```

---

## 12. Chainages liés au factory et à la config runtime

### Factory directe

```php
Iriven\CountriesServiceFactory::make();
Iriven\CountriesServiceFactory::make(Iriven\CountriesServiceFactory::defaultSqlitePath());
Iriven\CountriesServiceFactory::make(Iriven\CountriesServiceFactory::defaultJsonPath());
Iriven\CountriesServiceFactory::make(Iriven\CountriesServiceFactory::defaultCsvPath());
```

### Factory avec config

```php
$config = new Iriven\CountriesRuntimeConfig(
    sourcePath: Iriven\CountriesServiceFactory::defaultSqlitePath(),
    verifyChecksum: true,
    strictValidation: true,
);

$countries = Iriven\CountriesServiceFactory::fromConfig($config);
```

### Validation stricte

```php
Iriven\CountriesServiceFactory::makeWithValidation();
```

### Checksum

```php
Iriven\CountriesServiceFactory::assertChecksum(
    Iriven\CountriesServiceFactory::defaultSqlitePath()
);
```

---

## 13. Résumé des familles de chainage

### 13.1 Service -> Country -> Value Object -> propriété

```php
$countries->country('FR')->region()->subRegion()->name();
$countries->country('FR')->currency()->code();
$countries->country('FR')->phone()->pattern();
```

### 13.2 Service -> CountriesCollection -> filtres -> tri -> sortie

```php
$countries->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->list();
```

### 13.3 Service -> CountriesCollection -> fonctionnel -> sortie

```php
$countries->countries()
    ->filter(fn ($country) => $country->hasCurrency('EUR'))
    ->map(fn ($country) => $country->name());
```

### 13.4 Service -> Query -> résultat

```php
$countries->query()
    ->matching('fr')
    ->limit(10)
    ->get();
```

### 13.5 Service -> collection spécialisée -> export

```php
$countries->currencies()->exportJsonFile('/tmp/currencies.json');
$countries->regions()->exportCsvFile('/tmp/regions.csv');
```

---

## 14. Important

Les chainages ci-dessus sont donnés **sans restriction artificielle** :  
tout ce qui est exposé publiquement par les objets peut être combiné dans l’ordre logique du type retourné.

En pratique :

- un `Country` peut chaîner vers ses value objects
- une `CountriesCollection` peut enchaîner filtres, tri, pagination, extraction, agrégation, export
- un `CountriesQuery` peut enchaîner filtres, tri et pagination avant `get()` ou `list()`
- `CurrenciesCollection` et `RegionsCollection` peuvent aller jusqu’à l’export final


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
- lecture unique des métadonnées et checksums dans `CountriesServiceFactory`
- optimisation des index SQLite
- support de requêtes partielles côté repository SQLite via :
  - `iterateAllLazy(int $limit = 500)`
  - `iterateByRegionLazy(string $region, int $limit = 500)`

## Notes sur le lazy loading SQLite

Ces méthodes sont internes au repository SQLite et permettent de parcourir les données par lots, sans charger l’intégralité du dataset d’un coup.

Exemple conceptuel :

```php
$repository = Iriven\CountriesServiceFactory::makeRepository(
    Iriven\CountriesServiceFactory::defaultSqlitePath()
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
