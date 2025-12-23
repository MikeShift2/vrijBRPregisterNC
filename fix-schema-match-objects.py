#!/usr/bin/env python3
"""
Script om het Personen schema aan te passen zodat het alleen de velden bevat die in de objecten zitten
"""

import json
import subprocess
import sys

# Haal een voorbeeld object op
result = subprocess.run(
    ['docker', 'exec', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud', '-e', 'SELECT object FROM oc_openregister_objects WHERE schema = 6 LIMIT 1;'],
    capture_output=True,
    text=True
)

# Parse output
lines = result.stdout.strip().split('\n')
if len(lines) < 2:
    print("❌ Kon object niet ophalen")
    sys.exit(1)

obj_json = lines[1].strip()
if not obj_json:
    print("❌ Leeg object")
    sys.exit(1)

try:
    obj = json.loads(obj_json)
    object_keys = set(obj.keys())
    print(f"✅ Object keys gevonden: {sorted(object_keys)}")
except json.JSONDecodeError as e:
    print(f"❌ JSON parse error: {e}")
    sys.exit(1)

# Haal huidige properties op
result = subprocess.run(
    ['docker', 'exec', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud', '-e', 'SELECT properties FROM oc_openregister_schemas WHERE id = 6;'],
    capture_output=True,
    text=True
)

# Parse output
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
    sys.exit(1)

# Maak nieuwe properties met alleen de velden die in de objecten zitten
new_props = {}
for key in sorted(object_keys):
    if key in props:
        # Behoud het type van het schema
        new_props[key] = {'type': 'string'}
    else:
        # Nieuw veld, voeg toe als string
        new_props[key] = {'type': 'string'}

print(f"✅ Nieuwe schema properties: {sorted(new_props.keys())}")

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
    print("✅ Schema bijgewerkt: alleen velden die in objecten zitten")
    print(f"   Aantal velden: {len(new_props)}")
else:
    print(f"❌ Fout bij updaten: {result.stderr}")
    sys.exit(1)








