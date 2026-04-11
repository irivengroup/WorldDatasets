# Changelog

## 1.0.0
- Rationalisation de l’architecture
- Source de vérité déplacée dans `src/data`
- Source SQLite par défaut
- Renommage `WorldDatasets` -> `WorldDatasets`
- Query builder, exports, validation, doctor, pipeline data
- Métadonnées de dataset, checksums, CI, documentation étendue

# Convention retenue

Cette version conserve volontairement :

- `WorldDatasetsService` comme service principal
- `WorldDatasetsFactory` comme factory principale
- `countries()` comme point d’entrée de collection
- `CountriesCollection` comme nom officiel de la collection de pays
- la variable d’exemple `$worldDatasets` dans toute la documentation

Autrement dit :
- on **ne migre pas** `CountriesCollection` vers `DatasetsCollection`
- on garde `countries()` → `CountriesCollection`

---

# Version publique stabilisée v1

Cette archive ne contient plus d’alias de transition.  
L’API publique officielle repose uniquement sur :

- `Iriven\WorldDatasets\WorldDatasetsService`
- `Iriven\WorldDatasets\WorldDatasetsFactory`
- `Iriven\WorldDatasets\WorldDatasetsRuntimeConfig`
- `Iriven\WorldDatasets\CountriesCollection`
- `Iriven\WorldDatasets\CurrencyCollection`
- `Iriven\WorldDatasets\RegionCollection`
- `Iriven\WorldDatasets\WorldDatasetsQuery`
- `Iriven\WorldDatasets\WorldDatasetsStats`

---

# Public API harmonisée

Le package est maintenant aligné de bout en bout :

- package Composer : `iriven/php-world-datasets`
- namespace principal : `Iriven\WorldDatasets\`
- service principal : `WorldDatasetsService`
- factory principale : `WorldDatasetsFactory`
- collection principale : `CountriesCollection`
- query builder : `WorldDatasetsQuery`

---

# Installation

```bash
composer require iriven/php-world-datasets
```

Namespace principal :

```php
use Iriven\WorldDatasets\WorldDatasetsService;
use Iriven\WorldDatasets\WorldDatasetsFactory;
```

---