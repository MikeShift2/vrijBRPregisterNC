#!/bin/bash
# Test script voor Schema ID 7 (Adressen) na update

echo "=========================================="
echo "Test Schema ID 7 (Adressen)"
echo "=========================================="
echo ""

# Test BSN voor testen
TEST_BSN="168149291"

# Database configuratie
DB_HOST="nextcloud-db"
DB_USER="nextcloud_user"
DB_PASS="nextcloud_secure_pass_2024"
DB_NAME="nextcloud"

POSTGRES_CONTAINER="mvpvrijbrp2025-db-1"
POSTGRES_USER="postgres"
POSTGRES_DB="bevax"

echo "1️⃣  Test: Check of view v_vb_ax_haal_centraal bestaat..."
docker exec $POSTGRES_CONTAINER psql -U $POSTGRES_USER -d $POSTGRES_DB -t -c \
  "SELECT COUNT(*) FROM information_schema.views WHERE table_schema = 'probev' AND table_name = 'v_vb_ax_haal_centraal';" \
  | grep -q "1"

if [ $? -eq 0 ]; then
    echo "   ✅ View v_vb_ax_haal_centraal bestaat"
else
    echo "   ❌ View v_vb_ax_haal_centraal bestaat niet!"
    exit 1
fi

echo ""
echo "2️⃣  Test: Check of view data bevat..."
COUNT=$(docker exec $POSTGRES_CONTAINER psql -U $POSTGRES_USER -d $POSTGRES_DB -t -A -c \
  "SELECT COUNT(*) FROM probev.v_vb_ax_haal_centraal WHERE bsn = '$TEST_BSN';")

if [ "$COUNT" -gt 0 ]; then
    echo "   ✅ View bevat data voor BSN $TEST_BSN ($COUNT records)"
else
    echo "   ⚠️  Geen data gevonden voor BSN $TEST_BSN"
fi

echo ""
echo "3️⃣  Test: Check schema configuratie in Open Register..."
CONFIG=$(docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -sN -e \
  "SELECT configuration FROM oc_openregister_schemas WHERE id = 7;")

if echo "$CONFIG" | grep -q "v_vb_ax_haal_centraal"; then
    echo "   ✅ Schema configuratie correct"
    echo "   Configuratie: $CONFIG"
else
    echo "   ❌ Schema configuratie incorrect!"
    echo "   Configuratie: $CONFIG"
    exit 1
fi

echo ""
echo "4️⃣  Test: Check schema properties..."
PROPERTIES=$(docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -sN -e \
  "SELECT properties FROM oc_openregister_schemas WHERE id = 7;")

if echo "$PROPERTIES" | grep -q "verblijfplaats_straatnaam"; then
    echo "   ✅ Schema properties bevatten Haal Centraal velden"
else
    echo "   ⚠️  Schema properties lijken niet compleet"
fi

echo ""
echo "5️⃣  Test: Haal adres op uit view voor test BSN..."
docker exec $POSTGRES_CONTAINER psql -U $POSTGRES_USER -d $POSTGRES_DB -c \
  "SELECT bsn, verblijfplaats_straatnaam, verblijfplaats_huisnummer, verblijfplaats_postcode, verblijfplaats_woonplaats FROM probev.v_vb_ax_haal_centraal WHERE bsn = '$TEST_BSN' LIMIT 1;"

echo ""
echo "6️⃣  Test: Check of Open Register objecten bestaan voor schema 7..."
OBJECT_COUNT=$(docker exec $DB_HOST mariadb -u $DB_USER -p"$DB_PASS" $DB_NAME -sN -e \
  "SELECT COUNT(*) FROM oc_openregister_objects WHERE schema = 7;")

echo "   Aantal objecten in Open Register voor schema 7: $OBJECT_COUNT"

if [ "$OBJECT_COUNT" -eq 0 ]; then
    echo "   ⚠️  Geen objecten gevonden - mogelijk moet data worden geïmporteerd"
fi

echo ""
echo "=========================================="
echo "✅ Basis tests voltooid"
echo "=========================================="
echo ""
echo "📝 Volgende stappen:"
echo "   1. Test Haal Centraal API endpoint:"
echo "      curl -u admin:password \\"
echo "        'http://localhost:8080/apps/openregister/ingeschrevenpersonen/$TEST_BSN/verblijfplaats'"
echo ""
echo "   2. Test via Open Register API:"
echo "      curl -u admin:password \\"
echo "        'http://localhost:8080/apps/openregister/api/objects/2/7?bsn=$TEST_BSN'"
echo ""







