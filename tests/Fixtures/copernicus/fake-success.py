#!/usr/bin/env python3
"""Double de test pour bin/ingest-copernicus.py — ignore ses arguments,
sort un JSON figé sur stdout, sans aucun appel réseau."""
import json

print(json.dumps([
    {"date": "2026-08-11", "water_temperature_celsius": 21.63},
    {"date": "2026-08-12", "water_temperature_celsius": 21.29},
]))
