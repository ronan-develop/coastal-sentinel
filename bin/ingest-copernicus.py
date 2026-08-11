#!/usr/bin/env python3
"""Récupère la température de l'eau (Copernicus Marine, produit IBI) pour une
bbox et un horizon donnés, et l'écrit en JSON sur stdout.

Ne touche jamais la base de données — seul l'adaptateur PHP
(CopernicusEnvironmentDataSource) interprète cette sortie. Aucun fichier
NetCDF n'est écrit sur disque : l'accès se fait en mémoire via
copernicusmarine.open_dataset().

Identifiants attendus dans l'environnement :
COPERNICUSMARINE_SERVICE_USERNAME, COPERNICUSMARINE_SERVICE_PASSWORD.
"""
import argparse
import json
import sys
from datetime import datetime, timedelta, timezone

DATASET_ID = "cmems_mod_ibi_phy_anfc_0.027deg-3D_PT1H-m"
VARIABLE = "thetao"
HORIZON_DAYS = 7


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--lon-min", type=float, required=True)
    parser.add_argument("--lon-max", type=float, required=True)
    parser.add_argument("--lat-min", type=float, required=True)
    parser.add_argument("--lat-max", type=float, required=True)
    parser.add_argument("--since", type=str, required=True, help="Date de départ (YYYY-MM-DD)")
    return parser.parse_args()


def main() -> int:
    args = parse_args()

    try:
        import copernicusmarine
    except ImportError:
        print(
            "Le paquet 'copernicusmarine' n'est pas installé "
            "(voir bin/requirements-copernicus.txt).",
            file=sys.stderr,
        )
        return 1

    since = datetime.strptime(args.since, "%Y-%m-%d").replace(tzinfo=timezone.utc)
    end = since + timedelta(days=HORIZON_DAYS)

    try:
        dataset = copernicusmarine.open_dataset(
            dataset_id=DATASET_ID,
            variables=[VARIABLE],
            minimum_longitude=args.lon_min,
            maximum_longitude=args.lon_max,
            minimum_latitude=args.lat_min,
            maximum_latitude=args.lat_max,
            minimum_depth=0,
            maximum_depth=1,
            start_datetime=since.strftime("%Y-%m-%dT%H:%M:%S"),
            end_datetime=end.strftime("%Y-%m-%dT%H:%M:%S"),
        )
    except Exception as error:  # connexion, identifiants, dataset indisponible…
        print(f"Échec de l'accès à Copernicus Marine : {error}", file=sys.stderr)
        return 1

    mean = dataset[VARIABLE].mean(dim=["latitude", "longitude"]).sel(depth=dataset.depth.values[0])
    daily = mean.resample(time="1D").mean()

    payload = [
        {"date": str(date)[:10], "water_temperature_celsius": round(float(value), 2)}
        for date, value in zip(daily.time.values, daily.values)
        if value == value  # écarte les NaN (jours hors couverture du modèle)
    ]

    print(json.dumps(payload))
    return 0


if __name__ == "__main__":
    sys.exit(main())
