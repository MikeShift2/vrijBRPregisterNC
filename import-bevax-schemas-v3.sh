#!/bin/bash
# Script om bevax tabellen te importeren als OpenRegister schemas

echo "📊 Bevax tabellen importeren als OpenRegister schemas..."
echo ""

# Haal tabellen op
TABLES=$(docker exec mvpvrijbrp2025-db-1 psql -U postgres -d vrijBRPauth -t -c "SELECT table_name FROM information_schema.tables WHERE table_schema = 'bevax' ORDER BY table_name;" 2>&1 | grep -v "^$" | tr -d ' ')

echo "Gevonden tabellen:"
echo "$TABLES"
echo ""

# Voor elke tabel een schema aanmaken
for TABLE in $TABLES; do
    echo "📋 Schema aanmaken voor tabel: $TABLE"
    
    # Genereer UUID
    UUID=$(uuidgen 2>/dev/null || cat /proc/sys/kernel/random/uuid 2>/dev/null || python3 -c "import uuid; print(uuid.uuid4())" 2>/dev/null || echo $(date +%s | sha256sum | head -c 32))
    
    # Haal kolommen op en maak JSON
    COLUMNS=$(docker exec mvpvrijbrp2025-db-1 psql -U postgres -d vrijBRPauth -t -c "SELECT column_name FROM information_schema.columns WHERE table_schema = 'bevax' AND table_name = '$TABLE' ORDER BY ordinal_position;" 2>&1 | grep -v "^$" | tr -d ' ')
    
    # Maak properties JSON met Python
    PROPERTIES=$(echo "$COLUMNS" | python3 -c "
import sys
import json
cols = [line.strip() for line in sys.stdin if line.strip()]
props = {}
for col in cols:
    props[col] = {'type': 'string'}
print(json.dumps(props))
" 2>/dev/null)
    
    if [ -z "$PROPERTIES" ]; then
        echo "⚠️  Kon geen properties genereren voor $TABLE"
        continue
    fi
    
    # Escapen van single quotes voor MySQL
    PROPERTIES_ESCAPED=$(echo "$PROPERTIES" | sed "s/'/''/g")
    
    # Maak SQL bestand
    SQL_FILE=$(mktemp)
    cat > "$SQL_FILE" << SQL
INSERT INTO oc_openregister_schemas 
(uuid, version, title, description, properties, created, updated)
VALUES 
(
    '$UUID',
    '0.0.1',
    '$TABLE',
    'Schema voor bevax tabel: $TABLE',
    '$PROPERTIES_ESCAPED',
    NOW(),
    NOW()
);
SQL
    
    # Voeg schema toe
    docker exec -i nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud < "$SQL_FILE" 2>&1
    
    EXIT_CODE=$?
    rm -f "$SQL_FILE"
    
    if [ $EXIT_CODE -eq 0 ]; then
        echo "✅ Schema '$TABLE' toegevoegd"
    else
        echo "❌ Fout bij toevoegen van schema '$TABLE' (exit code: $EXIT_CODE)"
    fi
done

echo ""
echo "✅ Import voltooid!"
echo ""
echo "Controleer de schemas:"
docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -e "SELECT id, title FROM oc_openregister_schemas ORDER BY title;" 2>&1








