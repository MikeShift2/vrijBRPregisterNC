#!/usr/bin/env python3
"""
Script om het Personen schema aan te passen zodat null waarden zijn toegestaan
"""

import json
import subprocess
import sys

# Haal huidige properties op
result = subprocess.run(
    ['docker', 'exec', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud', '-e', 'SELECT properties FROM oc_openregister_schemas WHERE id = 6;'],
    capture_output=True,
    text=True
)

# Parse output (skip header)
lines = result.stdout.strip().split('\n')
if len(lines) < 2:
    print("❌ Kon properties niet ophalen")
    sys.exit(1)

props_json = lines[1].strip()
if not props_json:
    print("❌ Lege properties")
    sys.exit(1)

try:
    props = json.loads(props_json)
except json.JSONDecodeError as e:
    print(f"❌ JSON parse error: {e}")
    print(f"Raw output: {props_json[:200]}")
    sys.exit(1)

# Update alle properties om null toe te staan
new_props = {}
for key, value in props.items():
    if isinstance(value, dict) and value.get('type') == 'string':
        # Maak type nullable: ["string", "null"]
        new_props[key] = {'type': ['string', 'null']}
    else:
        new_props[key] = value

# Converteer naar JSON string en escape voor MySQL
new_props_json = json.dumps(new_props, ensure_ascii=False)
new_props_escaped = new_props_json.replace("'", "''")

# Update schema in database
update_sql = f"UPDATE oc_openregister_schemas SET properties = '{new_props_escaped}' WHERE id = 6;"

result = subprocess.run(
    ['docker', 'exec', '-i', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud'],
    input=update_sql,
    text=True,
    capture_output=True
)

if result.returncode == 0:
    print("✅ Schema bijgewerkt: alle string velden zijn nu nullable")
    print(f"   Aantal velden: {len(new_props)}")
else:
    print(f"❌ Fout bij updaten: {result.stderr}")
    sys.exit(1)








