#!/bin/bash
# Test script voor Haal Centraal BRP Bevragen API

# Haal Nextcloud admin wachtwoord op
PASSWORD=$(docker exec nextcloud env | grep NEXTCLOUD_ADMIN_PASSWORD | cut -d= -f2)

if [ -z "$PASSWORD" ]; then
    echo "❌ Kon Nextcloud wachtwoord niet ophalen"
    echo "   Voer handmatig je wachtwoord in:"
    read -s PASSWORD
fi

BASE_URL="http://localhost:8080/apps/openregister"

echo "🧪 Haal Centraal BRP Bevragen API Test"
echo "======================================"
echo ""

# Test 1: Lijst ingeschreven personen
echo "1️⃣  Lijst ingeschreven personen (max 3)..."
curl -s -u admin:"$PASSWORD" \
  "$BASE_URL/ingeschrevenpersonen?_limit=3" | python3 -m json.tool 2>/dev/null | head -40
echo ""
echo ""

# Test 2: Haal een BSN op uit database
BSN=$(docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -sN -e "SELECT JSON_EXTRACT(object, '$.bsn') FROM oc_openregister_objects WHERE schema = 6 LIMIT 1;" 2>/dev/null | tr -d '"')

if [ -n "$BSN" ] && [ "$BSN" != "null" ]; then
    echo "2️⃣  Specifieke persoon op BSN: $BSN..."
    curl -s -u admin:"$PASSWORD" \
      "$BASE_URL/ingeschrevenpersonen/$BSN" | python3 -m json.tool 2>/dev/null | head -50
    echo ""
    echo ""
else
    echo "⚠️  Kon geen BSN vinden in database"
fi

# Test 3: Test met ongeldig BSN
echo "3️⃣  Test met ongeldig BSN formaat..."
curl -s -u admin:"$PASSWORD" \
  "$BASE_URL/ingeschrevenpersonen/123" | python3 -m json.tool 2>/dev/null
echo ""
echo ""

echo "✅ API test voltooid!"
echo ""
echo "💡 Tip: Als je '404' ziet, controleer of Nextcloud is herstart"
echo "💡 Tip: Bekijk de Haal Centraal specificatie voor meer endpoints"








