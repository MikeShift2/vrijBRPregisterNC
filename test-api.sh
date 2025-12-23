#!/bin/bash
# Test script voor OpenRegister API

# Haal Nextcloud admin wachtwoord op
PASSWORD=$(docker exec nextcloud cat /var/www/html/config/config.php 2>/dev/null | grep password | head -1 | cut -d"'" -f4)

if [ -z "$PASSWORD" ]; then
    echo "❌ Kon Nextcloud wachtwoord niet ophalen"
    echo "   Voer handmatig je wachtwoord in:"
    read -s PASSWORD
fi

BASE_URL="http://localhost:8080/apps/openregister/api"
REGISTER_ID=2
SCHEMA_ID=6

echo "🧪 OpenRegister API Test"
echo "========================"
echo ""

# Test 1: Schema informatie ophalen
echo "1️⃣  Schema informatie ophalen..."
curl -s -u admin:"$PASSWORD" \
  "$BASE_URL/schemas/$SCHEMA_ID" | python3 -m json.tool 2>/dev/null | head -20
echo ""
echo ""

# Test 2: Eerste persoon UUID ophalen uit database
UUID=$(docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -sN -e "SELECT uuid FROM oc_openregister_objects WHERE schema = $SCHEMA_ID LIMIT 1;" 2>/dev/null)

if [ -n "$UUID" ]; then
    echo "2️⃣  Specifieke persoon ophalen (UUID: $UUID)..."
    curl -s -u admin:"$PASSWORD" \
      "$BASE_URL/objects/$REGISTER_ID/$SCHEMA_ID/$UUID?source=database" | python3 -m json.tool 2>/dev/null | head -30
    echo ""
    echo ""
fi

# Test 3: Lijst personen (met database source)
echo "3️⃣  Lijst personen ophalen (max 3)..."
curl -s -u admin:"$PASSWORD" \
  "$BASE_URL/objects/$REGISTER_ID/$SCHEMA_ID?source=database&_limit=3" | python3 -m json.tool 2>/dev/null | head -40
echo ""
echo ""

echo "✅ API test voltooid!"
echo ""
echo "💡 Tip: Als je 'Login failed' ziet, controleer je Nextcloud wachtwoord"
echo "💡 Tip: Gebruik altijd 'source=database' parameter tot SOLR is geconfigureerd"








