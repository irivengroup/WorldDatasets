# PHP Countries Data

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XDCFPNTKUC4TU)

All useful information about every country packaged as convenient little country objects. It includes data from ISO 3166 (countries and states/subdivisions ), ISO 4217 (currency), and E.164 (phone numbers). 

## Pré-requis

- PHP 8.2+
- extension PDO
- extension SQLite3

## Installation

```bash
composer install
php bin/import_countries.php
php bin/validate_countries.php
composer analyse
composer test
```

## Démarrage rapide

```php
use Iriven\CountriesServiceFactory;

require_once __DIR__ . '/vendor/autoload.php';

$countries = CountriesServiceFactory::make(__DIR__ . '/data/countries.sqlite');

echo $countries->getCountry('FR')->name();          // France
echo $countries->getCountry('250')->tld();          // .fr
print_r($countries->getCountry('FRA')->all());      // tableau associatif
```

---

## Philosophie

Cette version ne conserve que l’approche moderne :

- accès principal via `getCountry()`
- chaînage fluide sur l’objet `Country`
- recherches métier dédiées
- value objects pour devise, région et téléphone

L’ancienne API legacy a été retirée.

---

## Accès principal

```php
$countries->getCountry('FR');
$countries->country('FR');        // alias
$countries->findCountry('FR');    // nullable
```

Formats acceptés :

- `FR` : alpha2
- `FRA` : alpha3
- `250` : code numérique
- casse et espaces tolérés : `fr`, ` Fr `, etc.

---

## Inventaire des méthodes disponibles

### 1) Méthodes du service principal `WorldCountriesDatas`

| Méthode | Retour | Description |
|---|---:|---|
| `all()` | `array` | Retourne tous les pays au format associatif |
| `iterator($format = self::ALPHA2)` | `Generator` | Itérateur indexé par alpha2, alpha3 ou numeric |
| `count()` | `int` | Nombre total de pays |
| `getIterator()` | `Traversable` | Support `foreach` |
| `getCountry(string $code)` | `Country` | Retourne un pays ou lève une exception |
| `country(string $code)` | `Country` | Alias de `getCountry()` |
| `findCountry(string $code)` | `?Country` | Retourne `null` si introuvable |
| `getCountryData(string $code)` | `array` | Données du pays au format associatif |
| `getAllCurrenciesCodeAndName()` | `array<string,string>` | Devise => nom |
| `getAllCountriesCodeAndName($format = self::ALPHA2)` | `array<string,string>` | Code => nom pays |
| `getAllRegionsCodeAndName()` | `array<string,string>` | Région => nom |
| `getAllCountriesGroupedByRegions()` | `array<string,array<string,string>>` | Pays groupés par région |
| `getAllCountriesGroupedByCurrencies()` | `array<string,array<string,string>>` | Pays groupés par devise |
| `findByName(string $name)` | `array<Country>` | Recherche exacte par nom |
| `searchCountries(string $term)` | `array<Country>` | Recherche partielle |
| `findByCurrencyCode(string $code)` | `array<Country>` | Recherche par devise |
| `findByRegion(string $region)` | `array<Country>` | Recherche par région |
| `findByPhoneCode(string $code)` | `array<Country>` | Recherche par indicatif |
| `findByTld(string $tld)` | `array<Country>` | Recherche par TLD |

### 2) Méthodes de l’objet fluide `Country`

| Méthode | Retour | Description |
|---|---:|---|
| `alpha2()` | `string` | Code alpha2 |
| `alpha3()` | `string` | Code alpha3 |
| `numeric()` | `string` | Code numérique |
| `name()` | `string` | Nom du pays |
| `capital()` | `string` | Capitale |
| `tld()` | `string` | Domaine de premier niveau |
| `regionName()` | `string` | Nom de région |
| `regionAlphaCode()` | `string` | Code alpha de région |
| `regionNumCode()` | `string` | Code numérique de région |
| `subRegionName()` | `string` | Nom de sous-région |
| `subRegionCode()` | `string` | Code de sous-région |
| `language()` | `string` | Langues |
| `languages()` | `string` | Alias |
| `currencyCode()` | `string` | Code devise |
| `currencyName()` | `string` | Nom devise |
| `postalCodePattern()` | `string` | Regex code postal |
| `phoneCode()` | `string` | Indicatif téléphonique |
| `internationalDialingPrefix()` | `string` | Préfixe international |
| `nationalDialingPrefix()` | `string` | Préfixe national |
| `subscriberPhonePattern()` | `string` | Pattern local |
| `phoneNumberPattern()` | `string` | Pattern complet |
| `currency()` | `CurrencyInfo` | Objet devise |
| `regionInfo()` | `RegionInfo` | Objet région |
| `phone()` | `PhoneInfo` | Objet téléphone |
| `isInRegion(string $region)` | `bool` | Vérifie l’appartenance régionale |
| `hasCurrency(string $code)` | `bool` | Vérifie la devise |
| `exists()` | `bool` | Toujours `true` si obtenu via `getCountry()` |
| `toArray()` | `array` | Tableau associatif |
| `toIndexedArray()` | `array` | Tableau technique |
| `all()` | `array` | Alias de `toArray()` |
| `jsonSerialize()` | `array` | Compatible JSON |

### 3) Méthodes des value objects

#### `CurrencyInfo`
- `code(): string`
- `name(): string`
- `toArray(): array`

#### `RegionInfo`
- `alphaCode(): string`
- `numericCode(): string`
- `name(): string`
- `subRegionCode(): string`
- `subRegionName(): string`
- `toArray(): array`

#### `PhoneInfo`
- `code(): string`
- `internationalPrefix(): string`
- `nationalPrefix(): string`
- `subscriberPattern(): string`
- `pattern(): string`
- `toArray(): array`

---

## Totalité des chaînages possibles

### Chaînages directs sur `Country`

```php
$countries->getCountry('FR')->alpha2();
$countries->getCountry('FR')->alpha3();
$countries->getCountry('FR')->numeric();
$countries->getCountry('FR')->name();
$countries->getCountry('FR')->capital();
$countries->getCountry('FR')->tld();
$countries->getCountry('FR')->regionName();
$countries->getCountry('FR')->regionAlphaCode();
$countries->getCountry('FR')->regionNumCode();
$countries->getCountry('FR')->subRegionName();
$countries->getCountry('FR')->subRegionCode();
$countries->getCountry('FR')->language();
$countries->getCountry('FR')->languages();
$countries->getCountry('FR')->currencyCode();
$countries->getCountry('FR')->currencyName();
$countries->getCountry('FR')->postalCodePattern();
$countries->getCountry('FR')->phoneCode();
$countries->getCountry('FR')->internationalDialingPrefix();
$countries->getCountry('FR')->nationalDialingPrefix();
$countries->getCountry('FR')->subscriberPhonePattern();
$countries->getCountry('FR')->phoneNumberPattern();
$countries->getCountry('FR')->exists();
$countries->getCountry('FR')->isInRegion('Europe');
$countries->getCountry('FR')->hasCurrency('EUR');
$countries->getCountry('FR')->toArray();
$countries->getCountry('FR')->toIndexedArray();
$countries->getCountry('FR')->all();
$countries->getCountry('FR')->jsonSerialize();
```

### Chaînages via `currency()`

```php
$countries->getCountry('FR')->currency()->code();
$countries->getCountry('FR')->currency()->name();
$countries->getCountry('FR')->currency()->toArray();
```

### Chaînages via `regionInfo()`

```php
$countries->getCountry('FR')->regionInfo()->alphaCode();
$countries->getCountry('FR')->regionInfo()->numericCode();
$countries->getCountry('FR')->regionInfo()->name();
$countries->getCountry('FR')->regionInfo()->subRegionCode();
$countries->getCountry('FR')->regionInfo()->subRegionName();
$countries->getCountry('FR')->regionInfo()->toArray();
```

### Chaînages via `phone()`

```php
$countries->getCountry('FR')->phone()->code();
$countries->getCountry('FR')->phone()->internationalPrefix();
$countries->getCountry('FR')->phone()->nationalPrefix();
$countries->getCountry('FR')->phone()->subscriberPattern();
$countries->getCountry('FR')->phone()->pattern();
$countries->getCountry('FR')->phone()->toArray();
```

---

## Exemples rapides

```php
echo $countries->getCountry('FR')->name();
echo $countries->getCountry('250')->tld();
echo $countries->getCountry('fra')->currency()->code();

print_r($countries->getCountry('FR')->all());
print_r($countries->getCountryData('FR'));
```

### Recherche et chaînage

```php
foreach ($countries->findByRegion('Europe') as $country) {
    echo $country->name() . PHP_EOL;
}

foreach ($countries->findByCurrencyCode('EUR') as $country) {
    echo $country->alpha2() . ' - ' . $country->name() . PHP_EOL;
}
```

### Mode tolérant

```php
$country = $countries->findCountry('XX');

if ($country !== null) {
    echo $country->name();
}
```

---

## Exemple Symfony

### Enregistrement du service

`config/services.yaml`

```yaml
services:
  Iriven\Infrastructure\Cache\ArrayCache: ~

  Iriven\Support\NullLogger: ~

  Iriven\Infrastructure\Persistence\SqliteCountryRepository:
    factory: ['Iriven\Infrastructure\Persistence\SqliteCountryRepository', 'fromSqliteFile']
    arguments:
      $sqliteFilePath: '%kernel.project_dir%/data/countries.sqlite'
      $cache: '@Iriven\Infrastructure\Cache\ArrayCache'
      $logger: '@Iriven\Support\NullLogger'

  Iriven\WorldCountriesDatas:
    arguments:
      $repository: '@Iriven\Infrastructure\Persistence\SqliteCountryRepository'
```

### Utilisation dans un contrôleur

```php
<?php

namespace App\Controller;

use Iriven\WorldCountriesDatas;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CountryController extends AbstractController
{
    #[Route('/country/{code}', name: 'app_country_show')]
    public function show(string $code, WorldCountriesDatas $countries): Response
    {
        $country = $countries->getCountry($code);

        return $this->json([
            'name' => $country->name(),
            'alpha2' => $country->alpha2(),
            'currency' => $country->currency()->code(),
            'phone' => $country->phone()->toArray(),
        ]);
    }
}
```

---

## Exemple Laravel

### Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Iriven\Infrastructure\Cache\ArrayCache;
use Iriven\Infrastructure\Persistence\SqliteCountryRepository;
use Iriven\Support\NullLogger;
use Iriven\WorldCountriesDatas;

final class CountriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorldCountriesDatas::class, function () {
            $repository = SqliteCountryRepository::fromSqliteFile(
                database_path('countries.sqlite'),
                new ArrayCache(),
                new NullLogger()
            );

            return new WorldCountriesDatas($repository);
        });
    }
}
```

### Utilisation dans un contrôleur Laravel

```php
<?php

namespace App\Http\Controllers;

use Iriven\WorldCountriesDatas;
use Illuminate\Http\JsonResponse;

final class CountryController extends Controller
{
    public function show(string $code, WorldCountriesDatas $countries): JsonResponse
    {
        $country = $countries->getCountry($code);

        return response()->json([
            'name' => $country->name(),
            'alpha3' => $country->alpha3(),
            'region' => $country->regionInfo()->name(),
            'currency' => $country->currency()->toArray(),
        ]);
    }
}
```

### Utilisation directe

```php
$countryName = app(\Iriven\WorldCountriesDatas::class)
    ->getCountry('FR')
    ->name();
```

---

## Commandes utiles

```bash
composer install
php bin/import_countries.php
php bin/validate_countries.php
composer analyse
composer test
```
