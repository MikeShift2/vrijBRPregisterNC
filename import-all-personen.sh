#!/bin/bash
# Script om alle personen uit probev database te importeren naar OpenRegister

echo "📊 Personen importeren van probev database naar OpenRegister..."
echo ""

SCHEMA_ID=6
REGISTER_ID=2
BATCH_SIZE=1000

# Haal totaal aantal op
echo "⏳ Totaal aantal personen ophalen..."
TOTAL_COUNT=$(docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c "SELECT COUNT(*) FROM probev.\"Personen\";" 2>&1 | grep -E "^[0-9]+$" | head -1)

if [ -z "$TOTAL_COUNT" ] || [ "$TOTAL_COUNT" = "0" ]; then
    echo "❌ Geen personen gevonden"
    exit 1
fi

echo "✅ Totaal aantal personen: $TOTAL_COUNT"
echo ""

# Check hoeveel er al zijn
EXISTING=$(docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -sN -e "SELECT COUNT(*) FROM oc_openregister_objects WHERE register = $REGISTER_ID AND schema = $SCHEMA_ID;" 2>/dev/null)
echo "Aantal personen al in OpenRegister: $EXISTING"
echo ""

IMPORTED=0
ERRORS=0
OFFSET=0

while [ $OFFSET -lt $TOTAL_COUNT ]; do
    echo "📥 Batch ophalen: offset $OFFSET, limit $BATCH_SIZE..."
    
    # Haal batch op als JSON - gebruik temp file voor grote data
    TEMP_FILE=$(mktemp)
    docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c "SELECT json_agg(row_to_json(t)) FROM (SELECT * FROM probev.\"Personen\" ORDER BY id LIMIT $BATCH_SIZE OFFSET $OFFSET) t;" > "$TEMP_FILE" 2>&1
    
    # Verwijder "SET" output en andere noise
    JSON_DATA=$(grep -E "^\[.*\]$" "$TEMP_FILE" | head -1 || cat "$TEMP_FILE" | tail -1)
    rm -f "$TEMP_FILE"
    
    if [ -z "$JSON_DATA" ] || [ "$JSON_DATA" = "null" ] || [ "$JSON_DATA" = "[null]" ] || [ "${JSON_DATA:0:1}" != "[" ]; then
        echo "Geen geldige JSON data gevonden, stoppen."
        echo "Debug output: ${JSON_DATA:0:200}"
        break
    fi
    
    # Importeer batch via Python script
    echo "$JSON_DATA" | python3 << 'PYTHON_SCRIPT'
import sys
import json
import subprocess
import uuid

try:
    data = json.load(sys.stdin)
    if not isinstance(data, list):
        print(f"❌ Geen array ontvangen: {type(data)}")
        sys.exit(1)
    
    print(f"📋 Batch grootte: {len(data)} personen")
    
    SCHEMA_ID = 6
    REGISTER_ID = 2
    imported = 0
    errors = 0
    
    for persoon in data:
        try:
            # Genereer UUID
            obj_uuid = str(uuid.uuid4())
            
            # Maak object JSON
            object_json = json.dumps(persoon, ensure_ascii=False)
            
            # Escapen voor MySQL
            object_json_escaped = object_json.replace("'", "''")
            
            # Check of BSN al bestaat
            bsn = persoon.get('bsn')
            if bsn:
                check_cmd = [
                    'docker', 'exec', 'nextcloud-db',
                    'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
                    'nextcloud', '-sN', '-e',
                    f"SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID} AND JSON_EXTRACT(object, '$.bsn') = '{bsn}';"
                ]
                result = subprocess.run(check_cmd, capture_output=True, text=True)
                if result.stdout.strip() and int(result.stdout.strip()) > 0:
                    continue  # Skip als al bestaat
            
            # Maak SQL
            sql = f"INSERT INTO oc_openregister_objects (uuid, version, register, schema, object, created, updated) VALUES ('{obj_uuid}', '0.0.1', '{REGISTER_ID}', '{SCHEMA_ID}', '{object_json_escaped}', NOW(), NOW());"
            
            # Voer SQL uit
            cmd = [
                'docker', 'exec', 'nextcloud-db',
                'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
                'nextcloud', '-e', sql
            ]
            
            result = subprocess.run(cmd, capture_output=True, text=True)
            
            if result.returncode == 0:
                imported += 1
                if imported % 100 == 0:
                    print(f"✅ {imported} personen geïmporteerd in deze batch...")
            else:
                errors += 1
                if errors <= 5:
                    print(f"❌ Fout: {result.stderr[:200]}")
                    
        except Exception as e:
            errors += 1
            if errors <= 5:
                print(f"❌ Exception: {str(e)[:200]}")
    
    print(f"✅ Batch voltooid: {imported} geïmporteerd, {errors} fouten")
    sys.exit(0)
    
except json.JSONDecodeError as e:
    print(f"❌ JSON decode error: {e}")
    sys.exit(1)
except Exception as e:
    print(f"❌ Error: {e}")
    sys.exit(1)
PYTHON_SCRIPT
    
    BATCH_RESULT=$?
    if [ $BATCH_RESULT -eq 0 ]; then
        IMPORTED_BATCH=$(echo "$JSON_DATA" | python3 -c "import sys, json; print(len(json.load(sys.stdin)))" 2>/dev/null || echo "0")
        IMPORTED=$((IMPORTED + IMPORTED_BATCH))
    else
        ERRORS=$((ERRORS + 1))
    fi
    
    OFFSET=$((OFFSET + BATCH_SIZE))
    echo ""
done

echo "✅ Import voltooid!"
echo "Totaal geïmporteerd: $IMPORTED"
echo "Fouten: $ERRORS"
echo ""

# Controleer eindresultaat
FINAL_COUNT=$(docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -sN -e "SELECT COUNT(*) FROM oc_openregister_objects WHERE register = $REGISTER_ID AND schema = $SCHEMA_ID;" 2>/dev/null)
echo "Totaal personen in OpenRegister: $FINAL_COUNT"

