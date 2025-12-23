#!/bin/bash
# Script om Personen direct te importeren van PostgreSQL naar OpenRegister

echo "📊 Personen importeren naar OpenRegister..."
echo ""

SCHEMA_ID=6
REGISTER_ID=1

# Haal maximaal 100 personen op en converteer naar JSON
echo "⏳ Personen ophalen uit database (maximaal 100)..."
PERSONEN_JSON=$(docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c "SET search_path = probev; SELECT json_agg(row_to_json(t)) FROM (SELECT * FROM \"Personen\" LIMIT 100) t;" 2>&1 | grep -v "^SET" | grep -v "^$" | head -1)

if [ -z "$PERSONEN_JSON" ] || [ "$PERSONEN_JSON" = "(0 rows)" ]; then
    echo "❌ Geen personen gevonden"
    exit 1
fi

echo "Gevonden personen JSON lengte: ${#PERSONEN_JSON} karakters"
echo ""

# Parse JSON en importeer elk persoon
SCHEMA_ID_VAR=$SCHEMA_ID
REGISTER_ID_VAR=$REGISTER_ID

echo "$PERSONEN_JSON" | python3 -c "
import sys
import json
import subprocess
import uuid
import os

SCHEMA_ID = int(os.environ.get('SCHEMA_ID_VAR', '6'))
REGISTER_ID = int(os.environ.get('REGISTER_ID_VAR', '1'))

try:
    data = json.load(sys.stdin)
    if not isinstance(data, list):
        print('❌ Geen array ontvangen')
        sys.exit(1)
    
    print(f'📋 {len(data)} personen gevonden')
    print('')
    
    imported = 0
    errors = 0
    
    for persoon in data:
        try:
            # Genereer UUID
            obj_uuid = str(uuid.uuid4())
            
            # Verwijder null waarden en lege strings uit object (OpenRegister heeft problemen hiermee)
            persoon_clean = {k: v for k, v in persoon.items() if v is not None and v != '' and v != ' '}
            
            # Maak object JSON
            object_json = json.dumps(persoon_clean, ensure_ascii=False)
            
            # Escapen voor MySQL
            object_json_escaped = object_json.replace(\"'\", \"''\")
            
            # Maak SQL
            sql = f\"INSERT INTO oc_openregister_objects (uuid, version, register, schema, object, created, updated) VALUES ('{obj_uuid}', '0.0.1', '{REGISTER_ID}', '{SCHEMA_ID}', '{object_json_escaped}', NOW(), NOW());\"
            
            # Voer SQL uit via temp file (betrouwbaarder voor grote data)
            import tempfile
            with tempfile.NamedTemporaryFile(mode='w', delete=False, suffix='.sql') as f:
                f.write(sql)
                temp_file = f.name
            
            try:
                result = subprocess.run(
                    ['docker', 'exec', '-i', 'nextcloud-db', 'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024', 'nextcloud'],
                    input=sql,
                    text=True,
                    capture_output=True,
                    timeout=30
                )
                
                if result.returncode == 0:
                    imported += 1
                    if imported % 500 == 0:
                        print(f'✅ {imported}/{len(data)} personen geïmporteerd...')
                else:
                    errors += 1
                    if errors <= 5:
                        print(f'❌ Fout bij persoon {imported + errors}: {result.stderr[:100]}')
            finally:
                import os
                if os.path.exists(temp_file):
                    os.unlink(temp_file)
        except Exception as e:
            errors += 1
            if errors <= 5:
                print(f'❌ Exception: {str(e)[:100]}')
    
    print('')
    print(f'✅ Import voltooid!')
    print(f'Geïmporteerd: {imported}')
    print(f'Fouten: {errors}')
    
except json.JSONDecodeError as e:
    print(f'❌ JSON decode error: {e}')
    print(f'Input: {sys.stdin.read()[:500]}')
    sys.exit(1)
except Exception as e:
    print(f'❌ Fout: {e}')
    import traceback
    traceback.print_exc()
    sys.exit(1)
" SCHEMA_ID_VAR=$SCHEMA_ID REGISTER_ID_VAR=$REGISTER_ID 2>&1

echo ""
echo "Controleer resultaat:"
docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -e "SELECT COUNT(*) FROM oc_openregister_objects WHERE schema = $SCHEMA_ID;" 2>&1

