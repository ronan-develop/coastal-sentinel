# Méthodologie TDD

Documente la méthodologie **réellement suivie**, telle qu'appliquée pour la
première fois en pratique au ticket #14 (modèle de données) — pas une
description théorique déconnectée du projet.

---

## Cycle

1. **RED** — écrire le test, le voir échouer pour la bonne raison
2. **GREEN** — implémenter le minimum pour le faire passer
3. **REFACTOR** — nettoyer sans casser les tests

Ne jamais passer à GREEN sans avoir vu le test échouer en RED.

## Stack de test

| Outil                                                    | Rôle                                                                                                        |
| -------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| **PHPUnit 13**                                           | Runner                                                                                                      |
| **`Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`** | Tests nécessitant le container (accès Doctrine, services)                                                   |
| **`dama/doctrine-test-bundle`**                          | Rollback transactionnel automatique après chaque test — aucune purge manuelle, aucune pollution entre tests |
| **`doctrine/doctrine-fixtures-bundle`**                  | Seed de données (`src/DataFixtures/AppFixtures.php`)                                                        |

## Ce qui a été RED pour de vrai (ticket #14)

- **Tests d'entités** (`tests/Entity/*Test.php`) : écrits **avant** que les
  classes `Zone`, `DataSource`, etc. n'existent — RED confirmé par `Error:
Class "App\Entity\Zone" not found`, puis GREEN après création des 5
  entités + 4 enums PHP natifs.
- **Tests de fixtures** (`tests/DataFixtures/AppFixturesTest.php`) : écrits
  contre le stub vide `AppFixtures::load()` généré par le bundle — RED
  confirmé (`assertNotNull` échoue), puis GREEN après implémentation réelle
  du seed.

## Nuance honnête — ce qui n'a pas été RED-first à 100 %

Le test de contrainte unique (`EnvironmentReadingUniqueConstraintTest`) a
été écrit **après** que la contrainte `#[ORM\UniqueConstraint(...)]` a été
posée dans l'entité (elle faisait partie du schéma dès la conception,
cf. `.claude/architecture.md`). Ce test **vérifie** un comportement déjà
implémenté plutôt que de le **driver** — une distinction à assumer plutôt
qu'à maquiller. Le TDD reste appliqué à l'esprit (aucun code non testé),
mais l'ordre strict RED→GREEN n'a pas été respecté sur ce point précis.

## Couverture attendue par feature

- Accès non autorisé (ownership check) — pertinent dès qu'un endpoint existe
- Cas nominal (happy path)
- Cas limites (validation, conflit, contrainte unique)
- Rollback / erreur (ex. échec d'ingestion externe)
