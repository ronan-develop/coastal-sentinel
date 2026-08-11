# coastal-sentinel

Repo : [github.com/ronan-develop/coastal-sentinel](https://github.com/ronan-develop/coastal-sentinel)

API prédictive de risque climatique et sanitaire pour la conchyliculture — alerte précoce (J+3 à J+7) basée sur des données environnementales publiques, en complément du diagnostic scientifique assuré par l'Ifremer.

**Statut** : Phase 0 — validation terrain (contact CRC Bretagne Nord en cours)

## Concept

Trois types de risque détectés à partir de données publiques (température de l'eau, salinité, oxygène dissous, météo) :

- **Thermique** — dépassement de seuil, durée d'exposition
- **Hypoxie** — température + absence de brassage + météo stable prolongée
- **Bactérien** — température + salinité + pluie récente (conditions favorables à Vibrio aestuarianus)

Chaque alerte est associée à une action recommandée (refroidissement, sortie nocturne, restriction de transfert).

## Documentation complète du projet

Plan détaillé, sources de données, contacts (CRC Bretagne Nord, Ifremer/LEMAR), étapes de développement et pistes de financement :

📄 [docs/projet-api-risque.md](./docs/projet-api-risque.md)

## Documentation technique

- [docs/sources-donnees.md](./docs/sources-donnees.md) — inventaire des sources de données ouvertes
- [docs/couts-acquisition-donnees.md](./docs/couts-acquisition-donnees.md) — coûts d'acquisition des données
- [docs/architecture-ingestion.md](./docs/architecture-ingestion.md) — principe d'ingestion « hors-ligne »

## Prochaine étape

Validation du besoin terrain avec le CRC Bretagne Nord avant tout développement.
