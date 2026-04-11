# Migration to v1

La v1 publique stabilise les noms suivants :

| Ancien nom | Nouveau nom |
|---|---|
| `Countries` | `WorldDatasetsService` |
| `CountriesServiceFactory` | `WorldDatasetsFactory` |
| `CountriesRuntimeConfig` | `WorldDatasetsRuntimeConfig` |
| `CountriesQuery` | `WorldDatasetsQuery` |
| `CountriesStats` | `WorldDatasetsStats` |
| `CountriesServiceProvider` | `WorldDatasetsServiceProvider` |

## Important

`CountriesCollection` est conservé tel quel.  
L’appel :

```php
$worldDatasets->countries()
```

retourne toujours une instance de `CountriesCollection`.

## Exemple

```php
use Iriven\WorldDatasets\WorldDatasetsFactory;

$worldDatasets = WorldDatasetsFactory::make();
$list = $worldDatasets->countries()->alpha2()->list();
```
