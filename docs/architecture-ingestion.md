# Architecture d'ingestion — le principe « hors-ligne »

Ce document explique **comment** la donnée entre dans Coastal Sentinel, et
pourquoi le traitement lourd (Python, NetCDF) n'impacte jamais l'API.

---

## 1. Le problème à résoudre

- Certaines données précieuses (Copernicus : température, salinité, O₂
  prévisionnels) n'existent qu'en **NetCDF**, un format binaire scientifique
  illisible nativement en PHP (voir [couts-acquisition-donnees.md](couts-acquisition-donnees.md)).
- On veut malgré tout une **API 100 % PHP**, rapide et fiable.

La solution : **séparer dans le temps** la préparation de la donnée (lente,
tolérante aux pannes) de sa consultation (rapide, fiable).

---

## 2. Deux temps distincts : « en ligne » vs « hors-ligne »

> **« Hors-ligne » ne signifie pas « sans internet ».**
> Ça signifie **« hors du cycle requête ↔ réponse »** — du travail de fond
> déclenché par une horloge, pas par un utilisateur.

| Critère              | ① En ligne (requête)        | ② Hors-ligne (batch)           |
| -------------------- | --------------------------- | ------------------------------ |
| Déclencheur          | Un client appelle l'API     | Une **horloge (cron)**         |
| Qui attend ?         | Le client, **en direct**    | Personne                       |
| Contrainte           | **Rapide** (ms), **fiable** | Peut être lent, peut réessayer |
| Techno               | **PHP uniquement**          | PHP **ou Python**              |
| Exemple              | `GET /risque/rade-de-brest` | Ingestion quotidienne à 04:00  |
| Touche NetCDF/Python | **Jamais**                  | Oui, si nécessaire             |

---

## 3. L'analogie de la boulangerie

- **Python = le boulanger** : il pétrit et enfourne **à 04:00**, avant
  l'ouverture. Lourd, lent, en coulisses.
- **L'API PHP = le comptoir** : il vend le pain **déjà cuit** pendant la journée.
- Le client au comptoir **n'attend jamais** que le pain cuise — il est prêt.
- Four en panne un matin ? Le comptoir **vend le stock de la veille** : pas
  d'erreur, juste une donnée un peu moins fraîche.

---

## 4. Chronologie concrète

```text
04:00   ⏰ cron → script d'ingestion : télécharge + parse + écrit en base
        (peut durer 2 min, personne ne regarde)

08:37   👤 requête client → API PHP lit la base → répond en ~20 ms
12:14   👤 requête client → API PHP lit la base → répond en ~20 ms
        (le script d'ingestion n'a pas tourné pendant ces requêtes)

J+1, source externe en panne : l'API répond toujours, avec la donnée de la veille.
```

---

## 5. Schéma

```text
        ┌─────────────────────── HORS-LIGNE (cron, 1×/jour) ───────────────────────┐
        │                                                                          │
        │   Sources        Adaptateurs              Normalisation                  │
        │   externes       (1 par source)                                          │
        │                                                                          │
        │   Copernicus ─┐   ┌──────────────┐        ┌────────────────────┐         │
        │   Shom       ─┼─▶ │ ERDDAP-first │  ───▶  │ EnvironmentReading │  ──┐     │
        │   Météo-Fr   ─┘   │ Python-repli │        │ (modèle canonique) │    │     │
        │                   └──────────────┘        └────────────────────┘    │     │
        └──────────────────────────────────────────────────────────────────── │ ────┘
                                                                               ▼
                                                                        ┌────────────┐
                                                                        │ PostgreSQL │
                                                                        │  + PostGIS │
                                                                        └────────────┘
                                                                               ▲
        ┌──────────────────────── EN LIGNE (requête client) ─────────────────── │ ────┐
        │                                                                        │     │
        │   Client ──▶  API PHP / API Platform  ── lit UNIQUEMENT le local ──────┘     │
        │               (RiskEngine)                                                   │
        │   GET /risque/{zone} → score + type + action + fenêtre J+3→J+7                │
        └──────────────────────────────────────────────────────────────────────────────┘
```

Le trait vertical = la base. Le haut (hors-ligne) **écrit**, le bas (en ligne)
**lit**. Les deux mondes ne se croisent **que** via PostgreSQL.

---

## 6. Ce que ce découpage garantit

1. **L'API n'est jamais bloquée** par Python, le NetCDF, ou une source externe
   lente/en panne.
2. **Python est isolé et remplaçable** : si un accès ERDDAP suffit, on supprime
   Python sans toucher à l'API. C'est un **détail d'implémentation d'un
   adaptateur**, pas une dépendance de l'application.
3. **Les alertes sont reproductibles** : la donnée ingérée est persistée, donc
   on peut rejouer/auditer une alerte passée (indispensable au test pilote —
   comparer alertes émises vs mortalités observées).
4. **Portable** : « cron → script → PostgreSQL » tourne à l'identique sur
   o2switch (Python 2.7→3.13 + cron disponibles), un VPS, un conteneur ou une CI
   planifiée. Aucun verrou d'hébergement.

---

## 7. Stratégie d'accès par adaptateur (rappel)

Ordre de préférence, décidé source par source (détail dans
[couts-acquisition-donnees.md](couts-acquisition-donnees.md) §4) :

1. **ERDDAP (griddap) / WMS `GetFeatureInfo`** → CSV/JSON, **100 % PHP**, zéro
   dépendance. Premier choix.
2. **Batch Python cron** (`copernicusmarine`) → **uniquement** si la donnée
   nécessaire n'est disponible qu'en NetCDF. Écrit en base, jamais dans le
   chemin requête.
3. **Librairie PHP NetCDF** → écartée (coût de maintenance > bénéfice).

> Le contrat d'interface des adaptateurs et le schéma détaillé de
> `EnvironmentReading` seront définis dans le document d'architecture applicative.
