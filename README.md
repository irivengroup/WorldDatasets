# PHP Countries Data

Service autonome de consultation des pays avec source de données **SQLite par défaut**.

Les fichiers de référence générés depuis `legacy/cdata.php` sont désormais :

- `src/data/.countriesRepository.sqlite`
- `src/data/.countriesRepository.json`
- `src/data/countriesRepository.csv`

La solution utilise maintenant **le fichier SQLite comme source de données par défaut**.  
Le JSON caché et le CSV sont conservés comme sources alternatives et pour l’interopérabilité.

## Source par défaut

```php
use Iriven\CountriesServiceFactory;

require_once __DIR__ . '/vendor/autoload.php';

$countries = CountriesServiceFactory::make();
```

Par défaut, cela charge :

```text
src/data/.countriesRepository.sqlite
```

## Fichiers de données générés

### SQLite
```text
src/data/.countriesRepository.sqlite
```

### JSON
```text
src/data/.countriesRepository.json
```

### CSV
```text
src/data/countriesRepository.csv
```

## Régénérer les fichiers depuis `legacy/cdata.php`

```bash
php bin/import_countries.php
```

Cette commande lit `legacy/cdata.php` et reconstruit :

- `src/data/.countriesRepository.sqlite`
- `src/data/.countriesRepository.json`
- `src/data/countriesRepository.csv`

## Utiliser une autre source

### SQLite explicite

```php
$countries = CountriesServiceFactory::make(__DIR__ . '/src/data/.countriesRepository.sqlite');
```

### JSON

```php
$countries = CountriesServiceFactory::make(__DIR__ . '/src/data/.countriesRepository.json');
```

### CSV

```php
$countries = CountriesServiceFactory::make(__DIR__ . '/src/data/countriesRepository.csv');
```

## Détection automatique de la source

Le factory détecte la source par extension :

- `.sqlite`
- `.db`
- `.json`
- `.jsonn`
- `.csv`

## Utilisation détaillée

### Pays

```php
$country = $countries->country('FR');

$country->name();
$country->capital();
$country->tld();
$country->currency()->code();
$country->region()->name();
$country->phone()->pattern();
$country->data();
```

### Collections

```php
$countries->countries()->list();
$countries->countries()->alpha2()->list();
$countries->countries()->alpha3()->list();
$countries->countries()->numeric()->list();

$countries->currencies()->list();
$countries->regions()->list();
```

### Filtres chaînables

```php
$countries->countries()
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->alpha2()
    ->list();

$countries->countries()
    ->inSubRegion('Western Europe')
    ->sortByName()
    ->values();

$countries->countries()
    ->withTld('.fr')
    ->matching('fr')
    ->values();
```

### Agrégations

```php
$countries->countries()->stats();
$countries->countries()->groupByRegion();
$countries->countries()->groupByCurrency();
$countries->countries()->pluckNames();
$countries->countries()->pluckCodes();
```

### API fonctionnelle

```php
$countries->countries()->map(fn ($country) => $country->name());
$countries->countries()->filter(fn ($country) => $country->hasCurrency('EUR'));
$countries->countries()->reduce(fn ($carry, $country) => $carry + 1, 0);
```

### Exports

```php
$countries->countries()->toJson();
$countries->countries()->toCsv();

$countries->countries()->exportJsonFile('/tmp/countries.json');
$countries->countries()->exportCsvFile('/tmp/countries.csv');
```

### Query builder

```php
$query = $countries->query();

$result = $query
    ->inRegion('Europe')
    ->withCurrency('EUR')
    ->sortByName()
    ->limit(20)
    ->get();
```

## Validation de dataset

### Mode strict

```bash
php bin/validate_countries.php --strict
```

### Mode tolérant

```bash
php bin/validate_countries.php --lenient
```

### En PHP

```php
$validator = new Iriven\DatasetValidator();
$report = $validator->validate($countries->countries()->values(), false);

$report->duplicates();
$report->invalidCodes();
$report->warnings();
$report->isValid();
```

## CLI officielle

### Lister les pays

```bash
php bin/countries list alpha2
php bin/countries list alpha3
php bin/countries list numeric
```

### Afficher un pays

```bash
php bin/countries show FR
```

### Rechercher

```bash
php bin/countries search france
```

### Exporter

```bash
php bin/countries export countries json
php bin/countries export countries csv /tmp/countries.csv
php bin/countries export currencies json
php bin/countries export regions csv
```

### Valider

```bash
php bin/countries validate --strict
php bin/countries validate --lenient
```

## Repositories disponibles

- `Iriven\Infrastructure\Persistence\SqliteCountryRepository`
- `Iriven\JsonCountryRepository`
- `Iriven\CsvCountryRepository`
- `Iriven\ArrayCountryRepository`

## Intégration Symfony

```yaml
services:
  Iriven\Countries:
    factory: ['Iriven\CountriesServiceFactory', 'make']
```

## Intégration Laravel

Le provider pointe désormais vers :

```text
src/data/.countriesRepository.sqlite
```

## Tests

Les tests utilisent désormais la source SQLite par défaut, avec un test dédié pour la source JSON.

```bash
composer test
```

## Analyse statique

```bash
composer analyse
```
