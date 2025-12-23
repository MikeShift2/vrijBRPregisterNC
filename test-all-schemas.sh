#!/bin/bash
# Test script om alle schema's te testen

echo "=========================================="
echo "Test Alle Open Register Schema's"
echo "=========================================="
echo ""

# Database configuratie
DB_HOST="nextcloud-db"
DB_USER="nextcloud_user"
DB_PASS="nextcloud_secure_pass_2024"
DB_NAME="nextcloud"

POSTGRES_CONTAINER="mvpvrijbrp2025-db-1"
POSTGRES_USER="postgres"
POSTGRES_DB="bevax"

# Test BSN
TEST_BSN="168149291"

echo "📊 Overzicht van alle schema's:"
echo ""

# Haal alle schema's op
docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
  "SELECT id, title, 
   CASE 
     WHEN configuration IS NULL OR configuration = '' THEN 'Geen config'
     WHEN configuration LIKE '%v_personen_compleet_haal_centraal%' THEN '✅ Personen view'
     WHEN configuration LIKE '%v_vb_ax_haal_centraal%' THEN '✅ Adressen view'
     WHEN configuration LIKE '%v_%' THEN '✅ View geconfigureerd'
     ELSE '⚠️  Andere config'
   END as status
   FROM oc_openregister_schemas 
   ORDER BY id;" 2>/dev/null

echo ""
echo "=========================================="
echo "Test Kritieke Schema's"
echo "=========================================="
echo ""

# Test Schema ID 6: Personen
echo "1️⃣  Schema ID 6: Personen"
CONFIG_6=$(docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -sN -e \
  "SELECT configuration FROM oc_openregister_schemas WHERE id = 6;" 2>/dev/null)

if echo "$CONFIG_6" | grep -q "v_personen_compleet_haal_centraal"; then
    echo "   ✅ Configuratie correct"
    
    # Test of view data bevat
    COUNT=$(docker exec $POSTGRES_CONTAINER psql -U $POSTGRES_USER -d $POSTGRES_DB -t -A -c \
      "SELECT COUNT(*) FROM probev.v_personen_compleet_haal_centraal WHERE bsn = '$TEST_BSN';" 2>/dev/null)
    
    if [ "$COUNT" -gt 0 ]; then
        echo "   ✅ View bevat data voor BSN $TEST_BSN"
    else
        echo "   ⚠️  Geen data voor BSN $TEST_BSN"
    fi
else
    echo "   ❌ Configuratie incorrect of niet ingesteld"
fi

echo ""

# Test Schema ID 7: Adressen
echo "2️⃣  Schema ID 7: Adressen"
CONFIG_7=$(docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -sN -e \
  "SELECT configuration FROM oc_openregister_schemas WHERE id = 7;" 2>/dev/null)

if echo "$CONFIG_7" | grep -q "v_vb_ax_haal_centraal"; then
    echo "   ✅ Configuratie correct"
    
    # Test of view data bevat
    COUNT=$(docker exec $POSTGRES_CONTAINER psql -U $POSTGRES_USER -d $POSTGRES_DB -t -A -c \
      "SELECT COUNT(*) FROM probev.v_vb_ax_haal_centraal WHERE bsn = '$TEST_BSN';" 2>/dev/null)
    
    if [ "$COUNT" -gt 0 ]; then
        echo "   ✅ View bevat data voor BSN $TEST_BSN"
    else
        echo "   ⚠️  Geen data voor BSN $TEST_BSN"
    fi
else
    echo "   ❌ Configuratie incorrect of niet ingesteld"
    echo "   Configuratie: $CONFIG_7"
fi

echo ""

# Test Schema ID 21: GGM
echo "3️⃣  Schema ID 21: GGM IngeschrevenPersoon"
TITLE_21=$(docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -sN -e \
  "SELECT title FROM oc_openregister_schemas WHERE id = 21;" 2>/dev/null)

if [ -n "$TITLE_21" ]; then
    echo "   ✅ Schema bestaat: $TITLE_21"
else
    echo "   ⚠️  Schema bestaat niet"
fi

echo ""
echo "=========================================="
echo "Test Haal Centraal API Endpoints"
echo "=========================================="
echo ""

echo "4️⃣  Test: GET /ingeschrevenpersonen/{bsn}"
echo "   curl -u admin:password \\"
echo "     'http://localhost:8080/apps/openregister/ingeschrevenpersonen/$TEST_BSN'"
echo ""

echo "5️⃣  Test: GET /ingeschrevenpersonen/{bsn}/verblijfplaats"
echo "   curl -u admin:password \\"
echo "     'http://localhost:8080/apps/openregister/ingeschrevenpersonen/$TEST_BSN/verblijfplaats'"
echo ""

echo "6️⃣  Test: GET /ingeschrevenpersonen/{bsn}/partners"
echo "   curl -u admin:password \\"
echo "     'http://localhost:8080/apps/openregister/ingeschrevenpersonen/$TEST_BSN/partners'"
echo ""

echo "=========================================="
echo "Test Objecten in Open Register"
echo "=========================================="
echo ""

# Tel objecten per schema
echo "📊 Aantal objecten per schema:"
docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -e \
  "SELECT s.id, s.title, COUNT(o.id) as object_count
   FROM oc_openregister_schemas s
   LEFT JOIN oc_openregister_objects o ON o.schema = s.id
   GROUP BY s.id, s.title
   ORDER BY s.id;" 2>/dev/null

echo ""
echo "=========================================="
echo "✅ Tests voltooid"
echo "=========================================="
echo ""
echo "📝 Aanbevelingen:"
echo "   - Test de Haal Centraal API endpoints handmatig"
echo "   - Controleer of data correct wordt getransformeerd"
echo "   - Verifieer dat alle vereiste velden aanwezig zijn"
echo ""







