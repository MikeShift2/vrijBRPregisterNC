#!/usr/bin/env python3
import subprocess
import json
import sys

# Haal kolommen op
result = subprocess.run(
    ['docker', 'exec', 'mvpvrijbrp2025-db-1', 'psql', '-U', 'postgres', '-d', 'vrijBRPauth', '-t', '-c',
     "SELECT column_name FROM information_schema.columns WHERE table_schema = 'bevax' AND table_name = 'Personen' ORDER BY ordinal_position;"],
    capture_output=True,
    text=True
)

columns = [line.strip() for line in result.stdout.split('\n') if line.strip()]

# Maak properties JSON
properties = {col: {'type': 'string'} for col in columns}
properties_json = json.dumps(properties)

# Escapen voor MySQL
properties_escaped = properties_json.replace("'", "''")

# Update schema
sql = f"UPDATE oc_openregister_schemas SET properties = '{properties_escaped}', updated = NOW() WHERE title = 'Personen';"

result = subprocess.run(
    ['docker', 'exec', '-i', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud'],
    input=sql,
    text=True,
    capture_output=True
)

if result.returncode == 0:
    print(f"✅ Personen schema gerepareerd ({len(columns)} kolommen)")
    print(f"Properties: {properties_json[:100]}...")
else:
    print(f"❌ Fout: {result.stderr}")
    sys.exit(1)








