# Bonnes pratiques de code — principes appliqués

Documente les principes de conception **réellement appliqués** dans le
code, avec des exemples concrets du projet — pas un catalogue théorique de
design patterns. Un pattern ne se justifie que s'il résout un problème
**présent**, jamais par anticipation.

---

## Garde-fou n°1 : pas d'usine à gaz

Un principe ne s'applique que s'il simplifie _maintenant_. Symptômes à
éviter :

- une interface avec une seule implémentation et aucun besoin d'en changer
- une factory qui ne fait que `return new X(...)` sans logique de
  construction propre
- découper une classe en 4 juste pour "faire propre" alors que 15 lignes
  suffisaient

Dans le doute : **trois lignes similaires valent mieux qu'une abstraction
prématurée.**

## SRP (Single Responsibility)

Une classe = une raison de changer. Exemple réel (ticket #15, ingestion
Copernicus) : `App\Service\Adapter\Copernicus\`

| Classe                            | Seule responsabilité                                     |
| --------------------------------- | -------------------------------------------------------- |
| `CopernicusProcessFactory`        | Construire la commande/le `Process` (bbox + date → CLI)  |
| `CopernicusReadingMapper`         | Transformer un JSON décodé en `EnvironmentReadingData[]` |
| `CopernicusEnvironmentDataSource` | Orchestrer les deux ci-dessus + gérer l'échec            |

Chacune est testable **sans** exécuter de sous-processus, sauf la dernière
(dont le seul rôle est justement d'orchestrer l'exécution).

## Factory

Utilisée **quand la construction a une vraie logique** à isoler (ex.
`CopernicusProcessFactory` : transformer une `BoundingBox` + une date en
ligne de commande). Bénéfice concret : testable sans lancer de process, et
substituable en test (script factice) sans toucher à l'adaptateur.

Ne pas créer de factory pour un `new` trivial sans paramètre à assembler.

## Adapter

`EnvironmentDataSourceInterface` (cf. `.claude/architecture.md`) — chaque
fournisseur de données externe (Copernicus, plus tard Météo-France…)
implémente la même interface (`fetch()`, `getSourceName()`). Le reste de
l'application ne connaît jamais Python, NetCDF, ou la forme réelle de
l'API du fournisseur.

## Strategy (sélection d'adaptateur)

`IngestionOrchestrator` reçoit tous les adaptateurs enregistrés
(collection Symfony taguée sur `EnvironmentDataSourceInterface`) et choisit
celui qui correspond à `--source` au runtime — sans `switch`/`if` sur des
noms de classe. Ajouter une source = ajouter une classe, zéro modification
de l'orchestrateur.

## Command (Symfony Console)

`IngestCommand` ne contient **aucune logique métier** — il parse les
options et délègue à `IngestionOrchestrator`. Cf. règle déjà posée dans
`.claude/architecture.md` : `Command/` est un point d'entrée, jamais un
endroit où raisonner.

## DRY — dans la limite du raisonnable

`BoundingBox::fromWkt()` est réutilisable par n'importe quel futur
adaptateur ayant besoin d'un bbox à partir de la géométrie d'une `Zone` —
extrait une seule fois car un deuxième besoin réel (au-delà de Copernicus)
est déjà prévisible (Météo-France, même logique de zone). Ce n'est **pas**
extrait "au cas où" : c'est extrait parce que la même donnée
(géométrie → bbox) sert déjà à un besoin identifié.

---

## Même exigence côté Python

Les principes ci-dessus ne s'arrêtent pas à la frontière PHP/Python — le
script `bin/ingest-copernicus.py` suit la même discipline SRP, avec les
outils idiomatiques Python (`dataclass`, pas de hiérarchie de classes
inutile) :

| Composant                           | Seule responsabilité                                            |
| ----------------------------------- | --------------------------------------------------------------- |
| `IngestionRequest` (dataclass)      | Porter la zone + la fenêtre temporelle demandées                |
| `CopernicusDailyTemperatureFetcher` | Interroger Copernicus Marine et produire la moyenne journalière |
| `main()`                            | Parser les arguments, orchestrer, gérer l'échec, écrire le JSON |

Même garde-fou qu'en PHP : pas de classe pour parser les arguments (une
fonction `parse_args()` suffit — `argparse` fait déjà le travail), pas de
hiérarchie d'exceptions custom pour un script qui a un seul point d'échec
possible (le `try/except` générique dans `main()` suffit).
