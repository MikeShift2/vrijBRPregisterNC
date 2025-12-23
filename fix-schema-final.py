#!/usr/bin/env python3
"""
Script om het Personen schema definitief te repareren:
1. Verwijder 'voorvoegsel' uit properties (niet in objecten)
2. Zet required op NULL (niet lege array)
"""

import json
import subprocess
import sys

# Haal huidige properties op
result = subprocess.run(
    ['docker', 'exec', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud', '-e', 'SELECT properties, required FROM oc_openregister_schemas WHERE id = 6;'],
    capture_output=True,
    text=True
)

# Parse output
lines = result.stdout.strip().split('\n')
if len(lines) < 2:
    print("❌ Kon properties niet ophalen")
    sys.exit(1)

props_json = lines[1].split('\t')[0].strip()
required_val = lines[1].split('\t')[1].strip() if len(lines[1].split('\t')) > 1 else 'NULL'

if not props_json:
    print("❌ Lege properties")
    sys.exit(1)

try:
    props = json.loads(props_json)
except json.JSONDecodeError as e:
    print(f"❌ JSON parse error: {e}")
    print(f"Raw output: {props_json[:200]}")
    sys.exit(1)

# Verwijder voorvoegsel omdat het niet in de objecten zit
if 'voorvoegsel' in props:
    del props['voorvoegsel']
    print("✅ 'voorvoegsel' verwijderd uit schema")

# Converteer naar JSON string en escape voor MySQL
new_props_json = json.dumps(props, ensure_ascii=False)
new_props_escaped = new_props_json.replace("'", "''")

# Update schema in database - zet required op een lege JSON array in plaats van NULL
# Dit voorkomt dat getSchemaObject() een lege PHP array maakt
update_sql = f"UPDATE oc_openregister_schemas SET properties = '{new_props_escaped}', required = JSON_ARRAY() WHERE id = 6;"

result = subprocess.run(
    ['docker', 'exec', '-i', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud'],
    input=update_sql,
    text=True,
    capture_output=True
)

if result.returncode == 0:
    print("✅ Schema bijgewerkt:")
    print(f"   Aantal velden: {len(props)}")
    print(f"   Velden: {', '.join(sorted(props.keys()))}")
    print(f"   Required: NULL")
else:
    print(f"❌ Fout bij updaten: {result.stderr}")
    sys.exit(1)

