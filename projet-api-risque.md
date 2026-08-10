# Projet — API prédictive de risque climatique/sanitaire pour la conchyliculture

**Statut** : Phase 0 — validation terrain
**Localisation** : Guilers (29), zone CRC Bretagne Nord

---

## 1. Concept

API d'alerte précoce (J+3 à J+7) pour la filière conchylicole, basée sur des données environnementales publiques déjà existantes (température de l'eau, salinité, oxygène dissous, météo), avec des seuils de risque calibrés scientifiquement.

**Positionnement** : couche prédictive en amont, complémentaire au diagnostic a posteriori assuré par l'Ifremer (REPAMO). Ne remplace ni ne concurrence leur travail — valorise leurs données/recherches publiées sous forme d'outil actionnable.

**Trois types de risque ciblés** :
- **Thermique** — température > seuil (~28°C), durée d'exposition
- **Hypoxie** — température + absence de brassage + météo stable prolongée
- **Bactérien** — température + salinité + pluie récente (conditions favorables à Vibrio aestuarianus)

Chaque risque détecté → recommandation d'action associée (refroidissement des dégorgeoirs, sortie nocturne, restriction de transfert de naissain).

---

## 2. Pourquoi ce segment

- Aucun acteur commercial identifié combinant alerte précoce + risque climatique/bactérien pour la conchyliculture (contrairement à l'agriculture terrestre, couverte par Weenat/Sencrop)
- Filière fortement touchée par le réchauffement (mortalités OsHV-1 depuis 2008, Vibrio aestuarianus depuis 2012, épisodes d'hypoxie type étang de Thau 2018)
- Écosystème scientifique et institutionnel concentré à Brest (chance géographique) : Ifremer/LEMAR, CRC Bretagne Nord, Océanopolis

---

## 3. Sources de données à exploiter

| Source | Donnée | Accès |
|---|---|---|
| Copernicus Marine Service | Température, salinité, oxygène dissous | API gratuite — **à tester concrètement sur la rade de Brest, vérifier la résolution réelle en zone côtière/estran** |
| Météo-France | Prévisions vagues de chaleur, précipitations | API/open data |
| Ifremer (Archimer, publications) | Seuils de stress thermique, conditions Vibrio déjà identifiées | Extraction depuis publications existantes dans un premier temps |
| Historique CRC / Ifremer | Données de mortalité par bassin | À demander lors des échanges |

---

## 4. Contacts identifiés

### CRC Bretagne Nord — priorité immédiate
- Comité Régional de la Conchyliculture Bretagne Nord
- Basé à Morlaix (29600)
- Couvre la baie du Mont-Saint-Michel à la rade de Brest (600 km de côtes) — inclut ta zone
- Site / contact : **crcbn.com/contact/**
- Objectif du premier échange : valider le besoin réel, l'horizon utile (J+3/J+7/autre), comprendre les pratiques actuelles de veille

### Ifremer / LEMAR (Laboratoire des Sciences de l'Environnement Marin)
- Basé à l'IUEM, Brest
- Contact scientifique référent identifié : **Stéphane Pouvreau** (chercheur Ifremer, spécialiste huître en Bretagne)
- À solliciter **après** validation du besoin par le CRC, pas en premier contact
- Angle d'approche : valorisation applicative de leurs données/recherches publiées, pas de concurrence avec REPAMO

### Océanopolis (Brest)
- Rôle : médiation scientifique grand public, co-organisateur d'événements (ex. colloque Ostrea avec CRC + Ifremer)
- Pas un acteur concurrent ni un partenaire data direct — utile comme contexte de connaissance de l'écosystème local

### CNC — Comité National de la Conchyliculture
- Niveau national, à mobiliser si le projet dépasse l'échelle régionale

---

## 5. Plan d'action — étapes hiérarchisées

### Étape 1 — Contacter le CRC Bretagne Nord *(action immédiate)*
Envoyer le mail de demande d'échange (20-30 min), en personne ou téléphone vu la proximité géographique.

### Étape 2 — Préparer l'échange
- Vocabulaire métier (naissain, dégorgement, affinage, estran, exondation, concession)
- Connaître les crises de référence : virus OsHV-1 (naissain, depuis 2008), bactérie Vibrio aestuarianus (adultes, depuis 2012), épisode hypoxie étang de Thau 2018
- Savoir que REPAMO (Ifremer) est un réseau d'alerte a posteriori, pas prédictif
- Connaître l'existence du LEMAR et du contact Stéphane Pouvreau
- Préparer les questions : pratiques de veille existantes ? horizon utile pour agir ? qui décide des mesures ? sollicitations tech antérieures ?
- Ne pas arriver avec une solution figée — poser la réunion comme une écoute

### Étape 3 — Selon retour du CRC : sourcer les données
Tester l'accès réel à Copernicus Marine sur la rade de Brest, extraire les seuils publiés par Ifremer.

### Étape 4 — Construire un MVP minimal
Un seul bassin (rade de Brest), un seul type de risque pour commencer (thermique, le plus documenté).
Stack : API REST (FastAPI/Node) + PostGIS, cron d'ingestion quotidien.
Endpoint type : `GET /risque/{zone_bassin}` → score + type de risque + action recommandée + fenêtre temporelle.

### Étape 5 — Retour au CRC avec le MVP
Présenter quelque chose de concret, ouvrir la porte à un contact Ifremer/LEMAR pour validation scientifique des seuils.

### Étape 6 — Test pilote
Un bassin, une saison estivale, avec le CRC partenaire. Mesurer alertes émises vs mortalités observées, ajuster les seuils.

### Étape 7 — Financement *(seulement une fois un partenariat amorcé)*
Voir section 6.

### Étape 8 — Distribution
Modèle probable : accès mutualisé via CRC, ou abonnement producteur direct selon préférence du CRC.

---

## 6. Financements à explorer (post-partenariat uniquement)

| Dispositif | Type | Pertinence |
|---|---|---|
| **FEAMPA** (Fonds européen affaires maritimes, pêche, aquaculture) | Subvention, géré en partie par Région Bretagne | Priorité — cible explicitement l'adaptation climatique conchylicole/aquacole |
| **Bourse French Tech** (Bpifrance) | Jusqu'à 30k€ | Accessible en phase amorçage |
| **Technopole Brest-Iroise** | Incubateur local | Accompagnement + réseau, pertinent vu la localisation |
| **Région Bretagne** (pôle mer/économie maritime) | Aide à l'innovation | Complémentaire au FEAMPA |
| **i-Lab** (Bpifrada/Bpifrance) | Jusqu'à 600k€ | Plus tardif, structure formalisée requise |
| **France 2030** (volet économie de la mer) | Appels à projets | À surveiller, plus tardif |
| **Fondation de la Mer** | Appels à projets impact | Profil association, à vérifier |

**Point de vigilance** : la plupart de ces dispositifs demandent une lettre de soutien ou un partenariat déjà engagé (CRC, Ifremer). L'échange avec le CRC est donc aussi la première brique du futur dossier de financement.

---

## 7. Potentiel d'extension (à ne pas travailler maintenant)

Le modèle (données publiques + seuils calibrés par un partenaire scientifique + fenêtre d'alerte actionnable) est réplicable à d'autres filières, une fois validé sur l'ostréiculture :

- Mytiliculture (moules) — mêmes bassins, mêmes CRC
- Autres coquillages filtreurs (palourdes, coques)
- Algoculture
- Pêche côtière (migration d'espèces)
- Qualité des eaux de baignade / santé publique littorale
- Aquaculture continentale (pisciculture)

À ne considérer qu'après preuve de concept sur le premier cas d'usage.

---

## 8. Ce qu'il ne faut pas faire

- Ne pas arriver au CRC avec une solution déjà figée
- Ne pas prétendre remplacer ou concurrencer Ifremer/REPAMO
- Ne pas surestimer ses compétences en biologie marine — rester dans le rôle de développeur
- Ne pas viser un financement avant d'avoir un partenariat terrain amorcé

---

## Prochaine action unique et immédiate

**Envoyer le mail au CRC Bretagne Nord.** Tout le reste de ce document attend ce retour.
