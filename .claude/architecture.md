# Architecture applicative

Document de conception — issu du ticket [#4](https://github.com/ronan-develop/coastal-sentinel/issues/4).
Complète [architecture-ingestion.md](../docs/architecture-ingestion.md) (le
_pourquoi_ du découpage hors-ligne/en-ligne) avec le _comment_ : structure du
code, schéma des entités, contrat des adaptateurs, orchestration PHP↔Python.

---

## 1. Structure `src/`

Inspirée du découpage SRP/DIP de home-cloud, adaptée à une API sans frontend :

```text
src/
├── Command/        ← app:ingest, app:assess-risk, app:purge-readings
├── Controller/      ← si besoin hors API Platform (health-check...)
├── Entity/          ← entités Doctrine (UUID v7 dans le constructeur)
├── Interface/        ← contrats DIP (adaptateurs d'ingestion, RiskEngine)
├── Repository/       ← accès données
├── Service/          ← logique métier (RiskEngine, IngestionOrchestrator)
├── Service/Adapter/  ← un adaptateur par source (Copernicus, Météo-France...)
└── State/            ← providers/processors API Platform
```

- **`Controller/`** : quasiment vide au MVP, API Platform gère le routing via
  `State/`.
- **`Service/Adapter/`** : chaque source de données = une classe qui implémente
  `EnvironmentDataSourceInterface` (§3).
- **`Command/`** : point d'entrée unique des tâches cron — aucune logique
  métier, délègue aux `Service/`.

---

## 2. Schéma des entités

Moteur : **MariaDB** (support spatial natif — `GEOMETRY`, `ST_*`, index
spatiaux — pas besoin de PostGIS). UUID v7 partout, jamais `GeneratedValue`
(cf. règle CLAUDE.md).

### `Zone`

| Champ     | Type                       | Note                                    |
| --------- | -------------------------- | --------------------------------------- |
| id        | uuid (v7)                  |                                         |
| code      | string, unique             | ex. `rade-de-brest`                     |
| name      | string                     |                                         |
| geometry  | geometry (MariaDB spatial) | polygone du bassin, depuis data.gouv.fr |
| createdAt | datetime_immutable         |                                         |

### `DataSource`

| Champ                     | Type                             | Note                                                                            |
| ------------------------- | -------------------------------- | ------------------------------------------------------------------------------- |
| id                        | uuid (v7)                        |                                                                                 |
| name                      | string, unique                   | `copernicus`, `meteo-france`                                                    |
| type                      | enum (`forecast`, `observation`) |                                                                                 |
| lastSuccessfulIngestionAt | datetime_immutable, nullable     | fraîcheur de la donnée (cf. architecture-ingestion.md §3, analogie boulangerie) |

### `EnvironmentReading` — stockage hybride

Colonnes structurées (requêtes de seuil du `RiskEngine`) **+** JSON brut
(traçabilité). Voir §4 pour la justification du choix hybride.

| Champ      | Type                     | Note                                                                 |
| ---------- | ------------------------ | -------------------------------------------------------------------- |
| id         | uuid (v7)                |                                                                      |
| zone       | ManyToOne → `Zone`       |                                                                      |
| dataSource | ManyToOne → `DataSource` |                                                                      |
| variable   | enum                     | `water_temperature`, `salinity`, `dissolved_oxygen`, `precipitation` |
| value      | float                    | unité SI                                                             |
| unit       | string                   | ex. `celsius`, `psu`, `mg/l`, `mm`                                   |
| measuredAt | datetime_immutable       | horodatage de la mesure/prévision                                    |
| horizon    | int, nullable            | jours d'échéance (0/null = observation)                              |
| rawPayload | json, nullable           | réponse brute de la source, pour audit/debug                         |
| ingestedAt | datetime_immutable       |                                                                      |

**Contrainte unique** : `(zone, dataSource, variable, measuredAt, horizon)` —
upsert à l'ingestion, idempotent (rejouer un cron ne duplique rien).

### `RiskThreshold`

Table dédiée (pas de config YAML) — modifiable sans déploiement si besoin de
recalibrer après le test pilote (cf. §6 projet-api-risque.md).

| Champ           | Type          | Note                               |
| --------------- | ------------- | ---------------------------------- |
| id              | uuid (v7)     |                                    |
| riskType        | enum          | `thermal`, `hypoxia`, `bacterial`  |
| variable        | enum          | même enum que `EnvironmentReading` |
| operator        | enum          | `gt`, `gte`, `lt`, `lte`           |
| value           | float         | ex. `28` (°C)                      |
| minExposureDays | int, nullable | durée d'exposition requise         |
| source          | string        | référence à la publication Ifremer |

### `RiskAssessment`

| Champ                 | Type               | Note                              |
| --------------------- | ------------------ | --------------------------------- |
| id                    | uuid (v7)          |                                   |
| zone                  | ManyToOne → `Zone` |                                   |
| riskType              | enum               | `thermal`, `hypoxia`, `bacterial` |
| score                 | float              |                                   |
| windowStart/windowEnd | date               | fenêtre J+3→J+7                   |
| recommendedAction     | string             |                                   |
| computedAt            | datetime_immutable |                                   |

---

## 3. Contrat des adaptateurs d'ingestion

```php
interface EnvironmentDataSourceInterface
{
    /** @return iterable<EnvironmentReadingData> */
    public function fetch(Zone $zone, \DateTimeImmutable $since): iterable;

    public function getSourceName(): string;
}
```

- `fetch()` retourne des **DTO** (`EnvironmentReadingData`), jamais
  directement des entités Doctrine — découple la source externe de la
  persistance.
- Chaque implémentation choisit sa propre stratégie interne (ERDDAP/HTTP ou
  sous-processus Python, cf. §5), invisible du reste de l'application.
- `IngestionOrchestrator` (Service) boucle sur les adaptateurs enregistrés,
  valide chaque DTO, upsert via `EnvironmentReading`.

---

## 4. Pourquoi le stockage hybride (et pas 100 % JSON)

Le `RiskEngine` doit exécuter des requêtes comme _« température > seuil
pendant N jours consécutifs »_. Sur des colonnes structurées indexées
(`value`, `measuredAt`), c'est une requête SQL simple et rapide. Sur du JSON
pur, il faudrait du `JSON_TABLE`/`JSON_EXTRACT` à chaque calcul — plus lent,
plus complexe, et ça duplique la définition du schéma en dehors de Doctrine.

→ **Colonnes structurées obligatoires** pour tout ce que le moteur de risque
interroge. **`rawPayload` JSON en complément**, pour la traçabilité brute
(debug, audit, rejouabilité) sans coût de conception supplémentaire.

### Purge / rétention

```bash
php bin/console app:purge-readings --keep-days=N
```

- Le `rawPayload` JSON (lourd, surtout utile en debug court terme) se purge
  rapidement — ex. 90 jours par défaut.
- Les colonnes structurées (légères) se gardent plus longtemps, a minima le
  temps du test pilote (une saison estivale complète) pour permettre la
  calibration des seuils.
- Durée exacte à trancher : dépend de ce que le test pilote (Étape 6) exige
  réellement — question ouverte, à valider notamment avec le retour CRC/Ifremer.

---

## 5. Orchestration PHP ↔ Python

Rappel du principe déjà acté ([architecture-ingestion.md](../docs/architecture-ingestion.md)) :
Python n'intervient que hors-ligne, jamais dans le chemin d'une requête API.
Ce paragraphe précise **comment** les deux communiquent de façon fiable.

### Principes

1. **PHP est le seul orchestrateur.** Un unique cron (`app:ingest`) ; s'il a
   besoin de Python (repli NetCDF, cf. couts-acquisition-donnees.md §4), il le
   lance en sous-processus via `Symfony\Component\Process\Process`.
2. **Python ne touche jamais la base.** Il ne fait que _fetch + parser +
   normaliser_ → JSON sur stdout. Une seule définition du schéma (Doctrine),
   zéro risque de dérive entre deux langages.
3. **PHP valide avant de persister.** Le JSON reçu de Python est une frontière
   système — validé (forme, types) avant toute écriture, jamais de confiance
   aveugle en un processus externe.
4. **Échec propre.** Code de sortie ≠ 0, timeout, ou JSON invalide → on garde
   les données de la veille, on logge, rien ne casse (analogie boulangerie :
   pas de pain frais aujourd'hui, mais le stock d'hier reste vendable).
5. **Isolation des versions.** `python/` à la racine (hors `src/`), venv
   dédié, `requirements.txt` pinné — jamais de dépendance système ambiguë.
6. **Testabilité.** L'adaptateur PHP qui invoque Python est testé en mockant
   `Process` (TDD/PHPUnit intégral, sans exécuter Python dans la suite
   rapide). Un test d'intégration séparé exerce le vrai sous-processus.

### Séquence

```text
app:ingest (PHP, cron)
  → pour chaque adaptateur nécessitant Python :
      Process::run(['python/venv/bin/python', 'python/copernicus_fetch.py', '--zone=...'])
      → stdout : JSON (liste de lectures normalisées)
      → exit 0 attendu, sinon : log + skip, données précédentes conservées
  → validation du JSON (forme attendue)
  → mapping vers EnvironmentReadingData (DTO)
  → upsert EnvironmentReading (idempotent)
```

---

## 6. Design API Platform

```text
GET /api/risque/{zone} → score + type + action + fenêtre J+3→J+7
```

- **Protégé par JWT (lexik)** dès le MVP.
- Implémenté via un `State\Provider` custom (`RiskAssessmentProvider`) qui
  **lit uniquement** le dernier `RiskAssessment` en base pour la zone — jamais
  de calcul dans le chemin de la requête (cohérent avec le principe
  hors-ligne : le calcul tourne via `app:assess-risk`, en cron).

---

## 7. Génération de fichiers

| Fichier                        | Méthode                                                         |
| ------------------------------ | --------------------------------------------------------------- |
| Migration                      | `make:migration` (diff schéma/entités auto)                     |
| Entité/Repository/Service/Test | écrits directement (maker interactif incompatible avec l'agent) |

## 8. Points laissés ouverts

- Durée exacte de rétention des colonnes structurées (§4) — dépend du retour
  terrain CRC/Ifremer.
- Détail du DTO `EnvironmentReadingData` (mapping exact JSON → champs) — à
  préciser au premier ticket d'implémentation d'un adaptateur.
