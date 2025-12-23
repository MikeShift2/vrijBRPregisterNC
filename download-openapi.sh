#!/bin/bash
# Script om OpenAPI specificaties te downloaden van OpenRegister

# Haal Nextcloud admin wachtwoord op
PASSWORD=$(docker exec nextcloud env | grep NEXTCLOUD_ADMIN_PASSWORD | cut -d= -f2)

if [ -z "$PASSWORD" ]; then
    echo "❌ Kon Nextcloud wachtwoord niet ophalen"
    echo "   Voer handmatig je wachtwoord in:"
    read -s PASSWORD
fi

BASE_URL="http://localhost:8080/apps/openregister/api"

echo "📥 Downloaden OpenAPI specificaties..."
echo ""

# Download alle registers
echo "1️⃣  Downloaden OpenAPI voor alle registers..."
curl -s -u admin:"$PASSWORD" \
  "$BASE_URL/registers/oas" > openregister-api.json

if [ $? -eq 0 ]; then
    SIZE=$(ls -lh openregister-api.json | awk '{print $5}')
    echo "   ✅ Gedownload: openregister-api.json ($SIZE)"
else
    echo "   ❌ Fout bij downloaden"
fi

# Download register 2 specifiek
echo ""
echo "2️⃣  Downloaden OpenAPI voor register 2 (vrijBRPpersonen)..."
curl -s -u admin:"$PASSWORD" \
  "$BASE_URL/registers/2/oas" > openregister-register2-api.json

if [ $? -eq 0 ]; then
    SIZE=$(ls -lh openregister-register2-api.json | awk '{print $5}')
    echo "   ✅ Gedownload: openregister-register2-api.json ($SIZE)"
else
    echo "   ❌ Fout bij downloaden"
fi

echo ""
echo "✅ Klaar!"
echo ""
echo "📋 Volgende stappen:"
echo "   1. Ga naar: https://redocly.github.io/redoc/"
echo "   2. Klik op 'Upload a file'"
echo "   3. Selecteer een van de gedownloade bestanden:"
echo "      - $(pwd)/openregister-api.json (alle registers)"
echo "      - $(pwd)/openregister-register2-api.json (register 2)"
echo ""







