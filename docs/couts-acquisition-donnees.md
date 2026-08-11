# Coûts d'acquisition des données — recensement

Inventaire de **tous les coûts** que représente déjà la récupération des données,
au-delà du seul prix. Objectif : savoir ce que « aller chercher la donnée » coûte
réellement avant de décider de l'architecture d'ingestion.

**Conclusion en une phrase** : le coût **financier est ≈ 0 €** (tout est ouvert et
gratuit). Le vrai coût est **technique** — dominé par (1) le **format NetCDF**
mal adapté à un stack PHP et (2) la **maintenance des connecteurs** face aux
changements d'API.

---

## 1. Coût financier direct — quasi nul

| Source              | Prix    | Condition                                          |
| ------------------- | ------- | -------------------------------------------------- |
| Copernicus Marine   | Gratuit | Compte requis, **sans quota documenté** (fair-use) |
| Shom (data.shom.fr) | Gratuit | Licence Ouverte, services OGC libres               |
| Météo-France        | Gratuit | Compte + souscription par API                      |
| Ifremer Surval      | Gratuit | Téléchargement libre                               |
| ECOSCOPA / SEANOE   | Gratuit | CC-BY                                              |
| EMODnet             | Gratuit | Attribution                                        |
| data.gouv.fr        | Gratuit | Licence Ouverte                                    |

→ **Aucun abonnement, aucune facture d'API.** Le budget « données » du POC est nul.

---

## 2. Coût d'accès technique — par source

| Source          | Auth                   | Format livré                                    | Protocole                   | Friction           |
| --------------- | ---------------------- | ----------------------------------------------- | --------------------------- | ------------------ |
| Copernicus      | Compte (login/mdp)     | **NetCDF** / Zarr ARCO                          | Toolbox **Python** / ERDDAP | 🔴 élevée          |
| Shom            | Aucune                 | Images / **GetFeatureInfo (XML/JSON)**, vecteur | WMS / WFS / WMTS            | 🟠 moyenne         |
| Météo-France    | **Token OAuth2 / clé** | JSON (obs/Vigilance) / **GRIB** (modèles)       | REST                        | 🟠→🔴 selon besoin |
| Ifremer Surval  | Aucune                 | CSV                                             | Téléchargement HTTP         | 🟢 faible          |
| ECOSCOPA/SEANOE | Aucune                 | CSV / NetCDF                                    | Téléchargement HTTP (DOI)   | 🟢 faible          |
| EMODnet         | Aucune                 | NetCDF / ODV / **CSV via ERDDAP**               | WMS / WFS / ERDDAP          | 🟠 moyenne         |
| data.gouv.fr    | Aucune                 | GeoJSON / Shapefile                             | Téléchargement HTTP         | 🟢 faible          |

> **Shom** : via WMS/ncWMS on récupère des **images** ou une **valeur ponctuelle**
> (`GetFeatureInfo`, XML/JSON), pas un fichier NetCDF — celui-ci se récupère par
> d'autres canaux. **Météo-France** : les observations et la Vigilance sortent en
> JSON (facile), mais la **prévision quantitative** (AROME/ARPEGE : pluie, temp.)
> n'est diffusée qu'en **GRIB** — format binaire à la friction identique au
> NetCDF (voir §3 et §4). D'où la friction variable 🟠→🔴 selon la donnée visée.

---

## 3. Coûts par dimension

### Traitement (parsing / transformation) — **le coût dominant**

- **NetCDF** (Copernicus ; EMODnet en download ; **pas** Shom, traité en
  `GetFeatureInfo`) : format scientifique binaire multidimensionnel. **Aucune lib
  PHP native mature** pour le lire. C'est le point de friction majeur (voir §4).
- **GRIB** (modèles Météo-France bruts) : même problème, format binaire météo.
- CSV / JSON / GeoJSON : triviaux à parser en PHP.

### Réseau / bande passante

- Faible **si** on sous-échantillonne à l'ingestion (uniquement les points de
  grille intersectant les zones).
- Élevé **si** on télécharge des grilles régionales complètes → à éviter.

### Stockage

- Négligeable avec sous-échantillonnage : quelques lignes/jour/zone.
- Croît avec l'**historique** — mais cet historique est une **exigence produit**
  (test pilote : comparer alertes émises vs mortalités), pas un gaspillage.

### Quotas / rate limits

- Copernicus : **sans quota documenté** sur le download/subset (fair-use).
- Météo-France : **100 req/min** (depuis 2026, doublé) — très large pour 1 cron/jour.
- Services OGC (Shom, EMODnet) : best-effort, pas de quota strict documenté.

### Maintenance / fragilité — **coût récurrent sous-estimé**

Les API évoluent et cassent :

- Copernicus a **supprimé ses _anciens_ services (dont OPeNDAP) en avril 2024**,
  remplacés par le nouveau Data Store (nouveaux OPeNDAP/ERDDAP, **disponibilité à
  vérifier par produit**, cf. §4) — un connecteur codé avant la bascule aurait cassé.
- Météo-France a **migré Vigilance V5 → V6 en 2026**.

→ Chaque source = un connecteur à **surveiller et maintenir dans le temps**.
C'est le coût qui dure le plus longtemps.

### Humain / juridique

- Création et gestion de comptes (Copernicus, Météo-France) + rotation des tokens.
- **Attribution obligatoire** (toutes les licences) — à afficher dans le produit.

---

## 4. Le coût dominant : NetCDF ⟷ PHP

La donnée la plus précieuse (température/salinité/O₂ prévisionnels de Copernicus)
arrive en **NetCDF**, illisible nativement en PHP. Trois voies pour contourner,
par ordre de préférence :

| Option                          | Principe                                                                                                          | Coût                                    | Verdict                                                      |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------- | --------------------------------------- | ------------------------------------------------------------ |
| **A. ERDDAP (griddap)**         | Requête HTTP renvoyant **CSV/JSON** pour un point/bbox — pas de NetCDF, pas de Python                             | Faible, 100 % PHP                       | ✅ à privilégier **si** exposé en ERDDAP (cf. ⚠️ ci-dessous) |
| **B. OGC WMS `GetFeatureInfo`** | Interroger une valeur ponctuelle via Shom/EMODnet, réponse XML/JSON                                               | Faible, 100 % PHP                       | ✅ complément côtier (Shom)                                  |
| **C. Batch Python (cron)**      | Script Python (Copernicus Toolbox) lancé par cron : parse le NetCDF et **écrit en base**, jamais appelé par l'app | Moyen (2ᵉ runtime à déployer/maintenir) | 🟠 repli si A/B insuffisants                                 |

→ **Enjeu d'architecture** : privilégier les accès qui rendent du CSV/JSON
(ERDDAP, GetFeatureInfo) quand ils existent — ils gardent l'ingestion en **un
seul runtime**, plus simple à maintenir. Le **batch Python** (option C) reste un
**repli légitime**, faisable et portable (Python dispo sur o2switch, cf.
[architecture-ingestion.md](architecture-ingestion.md)) — à mobiliser dès qu'une
donnée n'existe qu'en NetCDF, sans en faire une techno à fuir.

> ⚠️ **Risque ouvert, à lever en priorité** : la disponibilité d'un **ERDDAP sur
> les produits Copernicus IBI de prévision** (extraction point → CSV/JSON) n'est
> **pas confirmée** — Copernicus a migré son Data Store en avril 2024. Si seuls
> le Toolbox/Zarr sont proposés pour ces produits, l'option A tombe et Copernicus
> bascule sur l'option C (batch Python). **À tester avant tout choix définitif.**

---

## 5. Estimation pour le POC (rade de Brest, risque thermique)

| Poste                   | Ordre de grandeur                                        |
| ----------------------- | -------------------------------------------------------- |
| € / mois                | **0 €**                                                  |
| Appels / jour           | ~quelques (1 cron : Copernicus + Météo-France)           |
| Volume ingéré/jour      | ~ko (quelques points × quelques variables × 7 échéances) |
| Stockage / an           | ~Mo (négligeable)                                        |
| Comptes à gérer         | 2 (Copernicus, Météo-France)                             |
| Connecteurs à maintenir | 2-3 (Copernicus, Météo-France, éventuellement Shom)      |

Le coût réel du POC n'est donc **pas** en euros ni en infra, mais en **temps de
développement et de maintenance des connecteurs**.

---

## 6. Implications pour l'architecture (transition)

1. **Isoler chaque source derrière un adaptateur** — la fragilité des API est
   le coût récurrent n°1 ; un changement d'API ne doit toucher qu'un connecteur.
2. **Préférer les accès CSV/JSON (ERDDAP, GetFeatureInfo)** quand ils existent —
   non parce que Python serait exclu (il est faisable et portable, cf. §4), mais
   parce qu'ils gardent l'ingestion **plus simple** : un seul runtime, moins de
   dépendances à installer et maintenir, empreinte mémoire réduite.
3. **Sous-échantillonner à l'ingestion** — coûts réseau/stockage maîtrisés.
4. **Persister l'historique** — nécessaire au produit, coût de stockage négligeable.

---

## Sources

- [Copernicus Marine Toolbox](https://toolbox-docs.marine.copernicus.eu/) · [Accès OPeNDAP/ERDDAP](https://marine.copernicus.eu/news/access-data-opendap-erddap-api)
- [Météo-France — doublement des quotas (100 req/min)](https://confluence-meteofrance.atlassian.net/wiki/spaces/OpenDataMeteoFrance/pages/1426423810)
- [data.shom.fr — services WMS/WFS](https://data.shom.fr/)
- [EMODnet](https://emodnet.ec.europa.eu/)
