#!/bin/bash
# Script om schema properties te repareren met alle kolommen

echo "🔧 Schema properties repareren..."
echo ""

# Controleer of PostgreSQL container draait
if ! docker ps | grep -q "mvpvrijbrp2025-db-1"; then
    echo "⚠️  PostgreSQL container draait niet. Start deze eerst:"
    echo "   docker start mvpvrijbrp2025-db-1"
    exit 1
fi

# Voor elk schema de properties repareren
SCHEMAS=$(docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -t -c "SELECT id, title FROM oc_openregister_schemas ORDER BY title;" 2>&1 | grep -v "^$" | grep -v "id.*title")

for SCHEMA_LINE in $SCHEMAS; do
    SCHEMA_ID=$(echo "$SCHEMA_LINE" | awk '{print $1}')
    SCHEMA_TITLE=$(echo "$SCHEMA_LINE" | awk '{for(i=2;i<=NF;i++) printf "%s ", $i; print ""}' | sed 's/ $//')
    
    if [ -z "$SCHEMA_ID" ] || [ -z "$SCHEMA_TITLE" ]; then
        continue
    fi
    
    echo "📋 Repareren schema: $SCHEMA_TITLE (ID: $SCHEMA_ID)"
    
    # Haal alle kolommen op
    COLUMNS=$(docker exec mvpvrijbrp2025-db-1 psql -U postgres -d vrijBRPauth -t -c "SELECT column_name FROM information_schema.columns WHERE table_schema = 'bevax' AND table_name = '$SCHEMA_TITLE' ORDER BY ordinal_position;" 2>&1 | grep -v "^$" | tr -d ' ')
    
    if [ -z "$COLUMNS" ]; then
        echo "⚠️  Geen kolommen gevonden voor $SCHEMA_TITLE"
        continue
    fi
    
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
        echo "⚠️  Kon geen properties genereren voor $SCHEMA_TITLE"
        continue
    fi
    
    # Escapen van single quotes voor MySQL
    PROPERTIES_ESCAPED=$(echo "$PROPERTIES" | sed "s/'/''/g")
    
    # Update schema
    SQL_FILE=$(mktemp)
    cat > "$SQL_FILE" << SQL
UPDATE oc_openregister_schemas 
SET properties = '$PROPERTIES_ESCAPED',
    updated = NOW()
WHERE id = $SCHEMA_ID;
SQL
    
    docker exec -i nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud < "$SQL_FILE" 2>&1
    
    EXIT_CODE=$?
    rm -f "$SQL_FILE"
    
    if [ $EXIT_CODE -eq 0 ]; then
        PROP_COUNT=$(echo "$COLUMNS" | wc -l | tr -d ' ')
        echo "✅ Schema '$SCHEMA_TITLE' gerepareerd ($PROP_COUNT kolommen)"
    else
        echo "❌ Fout bij repareren van schema '$SCHEMA_TITLE'"
    fi
done

echo ""
echo "✅ Reparatie voltooid!"
echo ""
echo "Controleer de properties:"
docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -e "SELECT id, title, JSON_LENGTH(properties) as property_count FROM oc_openregister_schemas ORDER BY title;" 2>&1








