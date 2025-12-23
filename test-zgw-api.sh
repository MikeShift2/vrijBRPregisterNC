#!/bin/bash

# Test script voor ZGW API endpoints

BASE_URL="http://localhost:8080/apps/openregister"
USERNAME="admin"
PASSWORD="admin_secure_pass_2024"

echo "🧪 ZGW API Test Script"
echo "======================"
echo ""

# Test 1: Lijst zaken (moet leeg zijn)
echo "1️⃣  Test: GET /zgw/zaken (lijst zaken)"
curl -s -u "$USERNAME:$PASSWORD" \
  "$BASE_URL/zgw/zaken" | python3 -m json.tool 2>/dev/null | head -20
echo ""
echo ""

# Test 2: Maak test zaak aan
echo "2️⃣  Test: POST /zgw/zaken (nieuwe zaak aanmaken)"
ZAAK_RESPONSE=$(curl -s -X POST -u "$USERNAME:$PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{
    "identificatie": "ZAAK-TEST-001",
    "bronorganisatie": "123456789",
    "zaaktype": "https://catalogi.nl/api/v1/zaaktypen/1",
    "registratiedatum": "2025-01-27T10:00:00Z",
    "startdatum": "2025-01-27",
    "status": "https://catalogi.nl/api/v1/statussen/1",
    "omschrijving": "Test zaak voor ZGW API",
    "verantwoordelijkeOrganisatie": "123456789",
    "betrokkeneIdentificaties": "[{\"identificatie\": \"168149291\", \"type\": \"natuurlijk_persoon\"}]"
  }' \
  "$BASE_URL/zgw/zaken")

echo "$ZAAK_RESPONSE" | python3 -m json.tool 2>/dev/null | head -30
echo ""

# Extract zaak UUID
ZAAK_UUID=$(echo "$ZAAK_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('url', '').split('/')[-1])" 2>/dev/null)

if [ -z "$ZAAK_UUID" ] || [ "$ZAAK_UUID" == "None" ]; then
    echo "⚠️  Kon zaak UUID niet extraheren, probeer handmatig"
    ZAAK_UUID="test-uuid"
else
    echo "✅ Zaak aangemaakt met UUID: $ZAAK_UUID"
fi

echo ""
echo ""

# Test 3: Haal specifieke zaak op
echo "3️⃣  Test: GET /zgw/zaken/{zaakId}"
curl -s -u "$USERNAME:$PASSWORD" \
  "$BASE_URL/zgw/zaken/$ZAAK_UUID" | python3 -m json.tool 2>/dev/null | head -30
echo ""
echo ""

# Test 4: Lijst tasks (moet leeg zijn)
echo "4️⃣  Test: GET /zgw/tasks (lijst tasks)"
curl -s -u "$USERNAME:$PASSWORD" \
  "$BASE_URL/zgw/tasks" | python3 -m json.tool 2>/dev/null | head -20
echo ""
echo ""

# Test 5: Maak test task aan
echo "5️⃣  Test: POST /zgw/tasks (nieuwe task aanmaken)"
TASK_RESPONSE=$(curl -s -X POST -u "$USERNAME:$PASSWORD" \
  -H "Content-Type: application/json" \
  -d "{
    \"task_type\": \"relocation_consent\",
    \"status\": \"planned\",
    \"bsn\": \"168149291\",
    \"description\": \"Toestemming hoofdhuurder vereist\",
    \"zaak_id\": \"$ZAAK_UUID\"
  }" \
  "$BASE_URL/zgw/tasks")

echo "$TASK_RESPONSE" | python3 -m json.tool 2>/dev/null | head -30
echo ""

# Extract task ID
TASK_ID=$(echo "$TASK_RESPONSE" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('taskId', ''))" 2>/dev/null)

if [ -z "$TASK_ID" ] || [ "$TASK_ID" == "None" ]; then
    echo "⚠️  Kon task ID niet extraheren"
    TASK_ID="test-task-id"
else
    echo "✅ Task aangemaakt met ID: $TASK_ID"
fi

echo ""
echo ""

# Test 6: Haal specifieke task op
echo "6️⃣  Test: GET /zgw/tasks/{taskId}"
curl -s -u "$USERNAME:$PASSWORD" \
  "$BASE_URL/zgw/tasks/$TASK_ID" | python3 -m json.tool 2>/dev/null | head -30
echo ""
echo ""

# Test 7: Update task status
echo "7️⃣  Test: PUT /zgw/tasks/{taskId} (update status naar in_progress)"
curl -s -X PUT -u "$USERNAME:$PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress"
  }' \
  "$BASE_URL/zgw/tasks/$TASK_ID" | python3 -m json.tool 2>/dev/null | head -20
echo ""
echo ""

echo "✅ Tests voltooid!"
echo ""
echo "📝 Test resultaten:"
echo "   - Zaak UUID: $ZAAK_UUID"
echo "   - Task ID: $TASK_ID"







