# Sources de données — inventaire (libre d'accès)

Recensement des sources de données **ouvertes** et **gratuites** exploitables par
Coastal Sentinel, pour l'ingestion et le calcul des trois risques (thermique,
hypoxie, bactérien).

**Critères de sélection** : accès libre (API, WMS/WFS, téléchargement), licence
permissive, couverture de la façade Bretagne / rade de Brest, horizon de
prévision compatible J+3 → J+7.

> ⚠️ Point clé métier : _Vibrio aestuarianus_ et _OsHV-1_ ne sont **pas** diffusés
> en temps réel dans un jeu open data. Le risque **bactérien** se calcule par
> **proxys environnementaux** (température + salinité + pluie récente) croisés
> avec les **seuils publiés par l'Ifremer**, pas par une mesure directe du
> pathogène. Le réseau REMI surveille _E. coli_ (indicateur fécal), variable
> distincte à ne pas confondre.

---

## 1. Tableau de synthèse

| Variable nécessaire    | Prévision (J+3→J+7)         | Observation in-situ                   |
| ---------------------- | --------------------------- | ------------------------------------- |
| Température de l'eau   | Copernicus Marine, Shom     | Copernicus in-situ, ECOSCOPA, EMODnet |
| Salinité               | Copernicus Marine, Shom     | Copernicus in-situ, ECOSCOPA, EMODnet |
| Oxygène dissous        | Copernicus Marine (BGC)     | EMODnet Chemistry                     |
| Courants / brassage    | Copernicus Marine, Shom     | Shom (courants de marée)              |
| Météo (chaleur, pluie) | Météo-France (AROME/ARPEGE) | Météo-France (observations)           |
| Géométrie des bassins  | —                           | data.gouv.fr (zones conchylicoles)    |
| Contexte sanitaire     | —                           | Ifremer Surval / REMI / REPHY         |

### Couverture par type de risque

| Risque    | Sources principales                                                          |
| --------- | ---------------------------------------------------------------------------- |
| Thermique | Copernicus Marine (temp.), Shom, Météo-France (vagues de chaleur)            |
| Hypoxie   | Copernicus Marine (O₂ + temp. + courants), Shom (brassage), Météo-France     |
| Bactérien | Copernicus Marine (temp. + salinité) + Météo-France (pluie) + seuils Ifremer |

---

## 2. Océanographie prévisionnelle

### Copernicus Marine Service — **source pivot**

Système Mercator, prévisions océaniques 3D à ~10 jours, mise à jour quotidienne.
Zone régionale **IBI (Iberia-Biscay-Irish)** qui couvre la Bretagne.

| Produit                                       | Variables                                         |
| --------------------------------------------- | ------------------------------------------------- |
| `IBI_ANALYSISFORECAST_PHY_005_001`            | Température, salinité, courants, niveau de la mer |
| `IBI_ANALYSISFORECAST_BGC_005_004`            | Oxygène dissous, chlorophylle, nutriments, pH     |
| `INSITU_IBI_PHYBGCWAV_DISCRETE_MYNRT_013_033` | Observations in-situ temps quasi réel (24-48 h)   |

- **Accès** : compte gratuit + [Copernicus Marine Toolbox](https://toolbox-docs.marine.copernicus.eu/) (CLI/Python), ou API/subsetter. Format NetCDF.
- **Licence** : Copernicus Marine Service License (réutilisation libre, y compris commerciale, avec attribution).
- **Vigilance** : résolution IBI ~1/36° (≈ 2-3 km) — **à tester concrètement sur la rade de Brest** (zone côtière/estran, résolution réelle à valider).

### Shom — raffinement côtier français

Prévisions océanographiques côtières (basées Copernicus, raffinées localement) +
marée, courants de marée, niveaux d'eau, bathymétrie, trait de côte.

- **Accès** : portail [data.shom.fr](https://data.shom.fr/) — services **WMS / WMTS / WFS** et **ncWMS** (prévisions océano en NetCDF). Conforme INSPIRE.
- **Pertinence** : meilleure précision côtière que Copernicus sur la rade de Brest ; **courants de marée** = indicateur direct du brassage (risque hypoxie).
- **Licence** : Licence Ouverte pour la majorité des jeux (vérifier au cas par cas).

---

## 3. Observations in-situ (mesures réelles)

### Ifremer ECOSCOPA (ex-VELYGER / RESCO)

Observatoire national du cycle de vie de l'huître creuse. **Température et
salinité mesurées au pas de 15 min depuis 2010** sur 8 sites conchylicoles.

- **Accès** : open data via **SEANOE** — base haute fréquence [DOI 10.17882/86131](https://doi.org/10.17882/86131) ; base larves VELYGER [DOI 10.17882/41888](https://doi.org/10.17882/41888).
- **Licence** : Creative Commons (CC-BY).
- **Vigilance** : **vérifier la présence d'un site en rade de Brest / Bretagne** parmi les 8 ateliers — donnée de calibration idéale si oui.

### EMODnet (Physics & Chemistry)

Portail européen agrégeant les observations marines. >52 M mesures temp/salinité,

> 40 M oxygène dissous. Utile pour **historique / climatologie / calibration des seuils**.

- **Accès** : [emodnet.ec.europa.eu](https://emodnet.ec.europa.eu/) — **WMS / WFS / CSW**, **ERDDAP** (Chemistry), formats NetCDF / SeaDataNet ODV.
- **Licence** : accès libre et gratuit (attribution).

---

## 4. Météo

### Météo-France — Open Data

| Ressource         | Contenu                                            |
| ----------------- | -------------------------------------------------- |
| Modèle **AROME**  | Prévision haute résolution 1,5 km (France), ~48 h  |
| Modèle **ARPEGE** | Prévision globale, échéance plus longue (~4 j)     |
| **Vigilance**     | Alertes (dont vagues de chaleur, pluie-inondation) |
| Observations      | Données SYNOP, précipitations, températures        |

- **Accès** : [portail API Météo-France](https://portail-api.meteofrance.fr/) — inscription gratuite, jeton **OAuth2 / clé API** par API souscrite. Jeux en masse aussi sur [meteo.data.gouv.fr](https://meteo.data.gouv.fr/).
- **Licence** : Licence Ouverte Etalab 2.0.
- **Pertinence** : **pluie récente** (proxy risque bactérien), **vagues de chaleur** (risque thermique), **météo stable prolongée** (risque hypoxie).

---

## 5. Surveillance sanitaire & qualité des eaux littorales (Ifremer)

Contexte et calibration — pas des variables de prévision, mais indispensables
pour relier les alertes aux observations sanitaires réelles.

### Surval / Quadrige

Interface nationale de valorisation des données environnementales littorales
(gérée par l'Ifremer depuis 1974). Température, salinité, oxygène, chlorophylle,
phytoplancton, microbiologie, contaminants.

- **Accès** : [surval.ifremer.fr](https://surval.ifremer.fr/) — consultation + **téléchargement** (données validées sans embargo diffusées à **D+1**). Services cartographiques via [Sextant](https://sextant.ifremer.fr/).
- **Licence** : Licence Ouverte.

### Réseaux de surveillance sanitaire

| Réseau       | Objet                                                                 |
| ------------ | --------------------------------------------------------------------- |
| **REMI**     | Microbiologie — _E. coli_ dans les coquillages → classement des zones |
| **REPHY**    | Phytoplancton & hydrologie littorale (efflorescences)                 |
| **REPHYTOX** | Phycotoxines réglementées (DSP, PSP, ASP) dans les mollusques         |

- **Accès** : plateforme **REPHY-TOX** (hébergée par l'OiEau), format SANDRE ; données également dans **Surval** et **Quadrige / Eaufrance**.
- **Licence** : Licence Ouverte.

---

## 6. Référentiels géographiques & classement des zones

### data.gouv.fr — zones conchylicoles

- **[Zones de production ou de parcage conchylicole — France entière](https://www.data.gouv.fr/en/datasets/zones-de-production-ou-de-parcage-conchylicole-france-entiere/)** — polygones géospatiaux → **alimente directement la géométrie PostGIS de l'entité `Zone`**.
- **Classement sanitaire de salubrité** (par département, ex. Finistère groupe 3) — arrêtés préfectoraux, 3 groupes (G1 gastéropodes/échinodermes, G2 fouisseurs, G3 non-fouisseurs = huîtres/moules).
- **Licence** : Licence Ouverte Etalab.

### Shom — fonds de carte

Bathymétrie, trait de côte, zones de marée (via `data.shom.fr`, cf. §2) — utile
pour le contexte géographique et le calcul de brassage.

---

## 7. Licences — récapitulatif

| Source              | Licence                           | Réutilisation commerciale |
| ------------------- | --------------------------------- | ------------------------- |
| Copernicus Marine   | Copernicus Marine Service License | Oui (attribution)         |
| Shom (data.shom.fr) | Licence Ouverte (majorité)        | Oui (vérifier par jeu)    |
| Météo-France        | Licence Ouverte Etalab 2.0        | Oui                       |
| Ifremer Surval/REMI | Licence Ouverte                   | Oui                       |
| ECOSCOPA / VELYGER  | Creative Commons CC-BY (SEANOE)   | Oui (attribution)         |
| EMODnet             | Libre (attribution)               | Oui                       |
| data.gouv.fr        | Licence Ouverte Etalab            | Oui                       |

---

## 8. Actions de vérification technique (avant ingestion)

- [ ] Créer un compte **Copernicus Marine** et tester une requête sur la bbox rade de Brest → valider la **résolution réelle** côtière.
- [ ] **(E2)** Vérifier si les produits **IBI forecast** de Copernicus sont exposés en **ERDDAP** (griddap → CSV/JSON) sur le nouveau Data Store. Sinon → repli **batch Python** (Toolbox). Conditionne toute la stratégie « ERDDAP-first ».
- [ ] Vérifier si un site **ECOSCOPA** existe en rade de Brest (donnée de calibration 15 min).
- [ ] Souscrire aux API **Météo-France** et récupérer un jeton. **(E3)** Distinguer les formats : _pluie observée / Vigilance_ = **JSON** (facile) vs prévision **AROME/ARPEGE** = **GRIB** (binaire, comme NetCDF) → choisir la source selon le besoin réel.
- [ ] Récupérer le polygone de la **zone rade de Brest** depuis data.gouv.fr pour l'entité `Zone`.
- [ ] Extraire les **seuils Ifremer** (stress thermique, conditions _Vibrio_) depuis Archimer pour le moteur de risque.

---

## Sources

- [Copernicus Marine — produits IBI](https://data.marine.copernicus.eu/products) · [Toolbox](https://toolbox-docs.marine.copernicus.eu/)
- [data.shom.fr](https://data.shom.fr/) · [Shom — prévisions Copernicus](https://www.shom.fr/fr/copernicus)
- [Portail API Météo-France](https://portail-api.meteofrance.fr/) · [meteo.data.gouv.fr](https://meteo.data.gouv.fr/)
- [Ifremer Surval](https://surval.ifremer.fr/) · [Sextant](https://sextant.ifremer.fr/)
- [Ifremer ECOSCOPA](https://ecoscopa.ifremer.fr/) · [SEANOE — base haute fréquence](https://doi.org/10.17882/86131)
- [EMODnet](https://emodnet.ec.europa.eu/)
- [REMI (Ifremer/COAST)](https://coast.ifremer.fr/Reseaux-de-surveillance/Environnement/REMI-REseau-de-controle-MIcrobiologique) · [REPHY/REPHYTOX](https://coast.ifremer.fr/Reseaux-de-surveillance/Environnement/REPHY-la-surveillance-du-phytoplancton-et-des-phycotoxines)
- [data.gouv.fr — zones conchylicoles France entière](https://www.data.gouv.fr/en/datasets/zones-de-production-ou-de-parcage-conchylicole-france-entiere/)
