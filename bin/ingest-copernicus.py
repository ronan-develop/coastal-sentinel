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
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone

DATASET_ID = "cmems_mod_ibi_phy_anfc_0.027deg-3D_PT1H-m"
VARIABLE = "thetao"
HORIZON_DAYS = 7


@dataclass(frozen=True)
class IngestionRequest:
    """Zone et fenêtre temporelle demandées — seule responsabilité : les porter."""

    lon_min: float
    lon_max: float
    lat_min: float
    lat_max: float
    since: datetime

    @property
    def until(self) -> datetime:
        return self.since + timedelta(days=HORIZON_DAYS)

    @staticmethod
    def from_args(args: argparse.Namespace) -> "IngestionRequest":
        since = datetime.strptime(args.since, "%Y-%m-%d").replace(tzinfo=timezone.utc)
        return IngestionRequest(args.lon_min, args.lon_max, args.lat_min, args.lat_max, since)


class CopernicusDailyTemperatureFetcher:
    """Seule responsabilité : interroger Copernicus Marine et produire une
    moyenne journalière de température de l'eau sur la zone demandée."""

    def fetch(self, request: IngestionRequest) -> list[dict]:
        import copernicusmarine

        dataset = copernicusmarine.open_dataset(
            dataset_id=DATASET_ID,
            variables=[VARIABLE],
            minimum_longitude=request.lon_min,
            maximum_longitude=request.lon_max,
            minimum_latitude=request.lat_min,
            maximum_latitude=request.lat_max,
            minimum_depth=0,
            maximum_depth=1,
            start_datetime=request.since.strftime("%Y-%m-%dT%H:%M:%S"),
            end_datetime=request.until.strftime("%Y-%m-%dT%H:%M:%S"),
        )

        mean = dataset[VARIABLE].mean(dim=["latitude", "longitude"]).sel(depth=dataset.depth.values[0])
        daily = mean.resample(time="1D").mean()

        return [
            {"date": str(date)[:10], "water_temperature_celsius": round(float(value), 2)}
            for date, value in zip(daily.time.values, daily.values)
            if value == value  # écarte les NaN (jours hors couverture du modèle)
        ]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--lon-min", type=float, required=True)
    parser.add_argument("--lon-max", type=float, required=True)
    parser.add_argument("--lat-min", type=float, required=True)
    parser.add_argument("--lat-max", type=float, required=True)
    parser.add_argument("--since", type=str, required=True, help="Date de départ (YYYY-MM-DD)")
    return parser.parse_args()


def main() -> int:
    request = IngestionRequest.from_args(parse_args())

    try:
        payload = CopernicusDailyTemperatureFetcher().fetch(request)
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
