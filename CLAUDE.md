# CLAUDE.md — Coastal Sentinel

Sommaire de référence. Lire avant toute intervention — suivre les liens pour le détail.

API prédictive de risque climatique/sanitaire pour la conchyliculture — alerte
précoce (J+3 à J+7) à partir de données environnementales publiques.

---

## Index

| Sujet                        | Fichier                                                    |
| ---------------------------- | ---------------------------------------------------------- |
| Tickets, planification, `gh` | [.claude/workflow-tickets.md](.claude/workflow-tickets.md) |
| Commits, branches, `/git`    | [.claude/git-conventions.md](.claude/git-conventions.md)   |
| Architecture `src/`, UUID    | [.claude/architecture.md](.claude/architecture.md)         |
| Commandes bin/console, tests | [.claude/commands.md](.claude/commands.md)                 |
| Méthodologie TDD             | [.claude/tdd.md](.claude/tdd.md)                           |
| Modèle de risque, ingestion  | `.claude/domaine.md` _(à venir)_                           |

---

## Règles critiques — toujours actives

### Tickets & planification — NON NÉGOCIABLE, préalable à tout code

- **Aucune ligne de code sans ticket GitHub associé** — l'issue se crée d'abord, via `gh`
- **Aucun code sans plan de tâche validé** — présenter le plan, attendre l'accord explicite, puis coder
- Chaque branche est liée à un ticket : `<type>/#<numéro>-<slug>`
- Flux imposé, dans l'ordre : **ticket → plan validé → branche → TDD → commits → PR (`Closes #<n>`)**
- Détail complet : [.claude/workflow-tickets.md](.claude/workflow-tickets.md)

### Secrets

- Ne jamais commiter de secrets — relire le diff stagé avant chaque commit
- `.env` : valeurs génériques uniquement ; `.env.local` / `.env.test.local` : valeurs sensibles
- Clés d'API des fournisseurs de données (Copernicus, Météo-France) : jamais dans git

### Git

- Jamais de commit direct sur `main`
- Commits atomiques — jamais `git add .` en un bloc
- Confirmation obligatoire avant : `git push`, merge, ouvrir/fermer une PR, supprimer une branche

### TDD

- Toujours RED → GREEN → REFACTOR — ne jamais écrire le code avant le test
- Couverture attendue : accès non authentifié, happy path, cas limites, rollback ingestion

### UUID Doctrine

Chaque entité **doit** initialiser l'ID dans son constructeur :

```php
#[ORM\Id]
#[ORM\Column(type: 'uuid', unique: true)]
private Uuid $id;

public function __construct()
{
    $this->id = Uuid::v7();
}
```

Ne jamais utiliser `#[ORM\GeneratedValue]` ni `private ?Uuid $id = null;`.

---

## Entités Doctrine

| Entité               | Rôle                                                                    |
| -------------------- | ----------------------------------------------------------------------- |
| `Zone`               | Bassin conchylicole — géométrie (support spatial MariaDB), code, nom    |
| `EnvironmentReading` | Mesure environnementale ingérée (temp. eau, salinité, O₂, source, date) |
| `RiskAssessment`     | Évaluation de risque calculée (type, score, action, fenêtre J+3→J+7)    |
| `RiskThreshold`      | Seuil de déclenchement d'un risque (ex. 28 °C thermique)                |
| `DataSource`         | Fournisseur de données (Copernicus, Shom, Météo-France)                 |

Stack : Symfony 7 / API Platform 4, PHP 8.2+, **MariaDB** (support spatial natif), JWT (lexik).

### Trois types de risque

| Type        | Déclencheur                                                    |
| ----------- | -------------------------------------------------------------- |
| `thermal`   | Température > seuil (~28 °C) + durée d'exposition              |
| `hypoxia`   | Température + absence de brassage + météo stable prolongée     |
| `bacterial` | Température + salinité + pluie récente (_Vibrio aestuarianus_) |

Chaque risque détecté → recommandation d'action associée.

---

## Commandes essentielles

```bash
./vendor/bin/phpunit --colors=always              # tous les tests
./vendor/bin/phpunit --filter NomDuTest           # test ciblé
php bin/console doctrine:migrations:migrate        # appliquer les migrations
php bin/console make:migration                     # générer une migration
php bin/console app:ingest --source=copernicus     # ingestion manuelle (cron en prod)
php bin/console app:assess-risk --zone=<code>      # recalcul des scores de risque
```

---

## Statut projet

Phase 0 — validation terrain (CRC Bretagne Nord). Le POC vise **un seul bassin
(rade de Brest)** et **un seul type de risque (thermique)** avant extension.
