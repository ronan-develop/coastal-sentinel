#!/usr/bin/env python3
"""Double de test simulant un échec d'ingestion (ex. Copernicus indisponible)."""
import sys

print("erreur simulée : service indisponible", file=sys.stderr)
sys.exit(1)
