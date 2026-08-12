#!/usr/bin/env python3
"""Récupère un instantané de la grille de température de l'eau (Copernicus
Marine, produit IBI) pour une bbox donnée, et l'écrit en JSON sur stdout.

Contrairement à ingest-copernicus.py (moyenne journalière sur toute la
zone), ce script renvoie la valeur brute de chaque maille du modèle avec
sa position (ou null si la maille n'est pas résolue) — pour visualiser la
couverture réelle du modèle sur une zone (cf. ticket #33).

Ne touche jamais la base de données — seul l'adaptateur PHP
(CopernicusGridDataSource) interprète cette sortie.

Identifiants attendus dans l'environnement :
COPERNICUSMARINE_SERVICE_USERNAME, COPERNICUSMARINE_SERVICE_PASSWORD.
"""
import argparse
import json
import sys
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone

DATASET_ID = "cmems_mod_ibi_phy_anfc_0.027deg-3D_PT1H-m"
VARIABLE = "thetao"


@dataclass(frozen=True)
class GridSnapshotRequest:
    """Bbox demandée — seule responsabilité : la porter."""

    lon_min: float
    lon_max: float
    lat_min: float
    lat_max: float

    @staticmethod
    def from_args(args: argparse.Namespace) -> "GridSnapshotRequest":
        return GridSnapshotRequest(args.lon_min, args.lon_max, args.lat_min, args.lat_max)


class CopernicusGridSnapshotFetcher:
    """Seule responsabilité : interroger Copernicus Marine et produire la
    valeur brute de chaque maille du modèle (ou None si non résolue) à
    l'instant le plus proche de maintenant, sur la zone demandée."""

    def fetch(self, request: GridSnapshotRequest) -> list[dict]:
        import copernicusmarine

        now = datetime.now(timezone.utc)

        dataset = copernicusmarine.open_dataset(
            dataset_id=DATASET_ID,
            variables=[VARIABLE],
            minimum_longitude=request.lon_min,
            maximum_longitude=request.lon_max,
            minimum_latitude=request.lat_min,
            maximum_latitude=request.lat_max,
            minimum_depth=0,
            maximum_depth=1,
            start_datetime=(now - timedelta(hours=1)).strftime("%Y-%m-%dT%H:%M:%S"),
            end_datetime=(now + timedelta(hours=2)).strftime("%Y-%m-%dT%H:%M:%S"),
        )

        snapshot = dataset[VARIABLE].isel(time=0).sel(depth=dataset.depth.values[0])
        lats = snapshot.latitude.values
        lons = snapshot.longitude.values
        values = snapshot.values

        cells = []
        for i, lat in enumerate(lats):
            for j, lon in enumerate(lons):
                value = values[i, j]
                cells.append({
                    "lat": round(float(lat), 5),
                    "lon": round(float(lon), 5),
                    "value": round(float(value), 2) if value == value else None,
                })
        return cells


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--lon-min", type=float, required=True)
    parser.add_argument("--lon-max", type=float, required=True)
    parser.add_argument("--lat-min", type=float, required=True)
    parser.add_argument("--lat-max", type=float, required=True)
    return parser.parse_args()


def main() -> int:
    request = GridSnapshotRequest.from_args(parse_args())

    try:
        payload = CopernicusGridSnapshotFetcher().fetch(request)
    except ImportError:
        print(
            "Le paquet 'copernicusmarine' n'est pas installé "
            "(voir bin/requirements-copernicus.txt).",
            file=sys.stderr,
        )
        return 1
    except Exception as error:  # connexion, identifiants, dataset indisponible…
        print(f"Échec de l'accès à Copernicus Marine : {error}", file=sys.stderr)
        return 1

    print(json.dumps(payload))
    return 0


if __name__ == "__main__":
    sys.exit(main())
