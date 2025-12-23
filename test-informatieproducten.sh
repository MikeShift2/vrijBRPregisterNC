#!/bin/bash

echo "════════════════════════════════════════════════════════"
echo "  TEST: Informatieproducten in Haal Centraal BRP API"
echo "════════════════════════════════════════════════════════"
echo ""

# Test BSN's
BSNS=("168149291" "216007574" "999999011")

for BSN in "${BSNS[@]}"; do
    echo "─────────────────────────────────────────────────────"
    echo "Testing BSN: $BSN"
    echo "─────────────────────────────────────────────────────"
    
    RESPONSE=$(curl -s -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=$BSN&_limit=1")
    
    # Check of er resultaten zijn
    COUNT=$(echo "$RESPONSE" | jq -r '._embedded.ingeschrevenpersonen | length')
    
    if [ "$COUNT" = "0" ] || [ "$COUNT" = "null" ]; then
        echo "❌ Geen resultaten voor BSN $BSN"
        echo ""
        continue
    fi
    
    echo "✅ Persoon gevonden"
    echo ""
    
    # Extract data
    PERSOON=$(echo "$RESPONSE" | jq '._embedded.ingeschrevenpersonen[0]')
    
    # Basis velden
    VOORNAMEN=$(echo "$PERSOON" | jq -r '.naam.voornamen // empty')
    GESLACHTSNAAM=$(echo "$PERSOON" | jq -r '.naam.geslachtsnaam // empty')
    
    echo "👤 Naam: $VOORNAMEN $GESLACHTSNAAM"
    echo ""
    
    # Test 1: Voorletters
    VOORLETTERS=$(echo "$PERSOON" | jq -r '.naam.voorletters // empty')
    if [ -n "$VOORLETTERS" ] && [ "$VOORLETTERS" != "null" ]; then
        echo "✅ Voorletters: $VOORLETTERS"
    else
        echo "❌ Voorletters ontbreekt!"
    fi
    
    # Test 2: Volledige naam
    VOLLEDIGEAAM=$(echo "$PERSOON" | jq -r '.naam.volledigeNaam // empty')
    if [ -n "$VOLLEDIGEAAM" ] && [ "$VOLLEDIGEAAM" != "null" ]; then
        echo "✅ Volledige naam: $VOLLEDIGEAAM"
    else
        echo "❌ Volledige naam ontbreekt!"
    fi
    
    # Test 3: Leeftijd
    LEEFTIJD=$(echo "$PERSOON" | jq -r '.leeftijd // empty')
    if [ -n "$LEEFTIJD" ] && [ "$LEEFTIJD" != "null" ]; then
        echo "✅ Leeftijd: $LEEFTIJD jaar"
    else
        echo "❌ Leeftijd ontbreekt!"
    fi
    
    # Test 4: Adressering object
    AANSCHRIJFWIJZE=$(echo "$PERSOON" | jq -r '.adressering.aanschrijfwijze // empty')
    AANHEF=$(echo "$PERSOON" | jq -r '.adressering.aanhef // empty')
    LOPENDE_TEKST=$(echo "$PERSOON" | jq -r '.adressering.gebruikInLopendeTekst // empty')
    
    if [ -n "$AANSCHRIJFWIJZE" ] && [ "$AANSCHRIJFWIJZE" != "null" ]; then
        echo "✅ Aanschrijfwijze: $AANSCHRIJFWIJZE"
    else
        echo "❌ Aanschrijfwijze ontbreekt!"
    fi
    
    if [ -n "$AANHEF" ] && [ "$AANHEF" != "null" ]; then
        echo "✅ Aanhef: $AANHEF"
    else
        echo "❌ Aanhef ontbreekt!"
    fi
    
    if [ -n "$LOPENDE_TEKST" ] && [ "$LOPENDE_TEKST" != "null" ]; then
        echo "✅ Gebruik in lopende tekst: $LOPENDE_TEKST"
    else
        echo "❌ Gebruik in lopende tekst ontbreekt!"
    fi
    
    # Test 5: Adresregels (alleen als adres aanwezig)
    ADRESREGEL1=$(echo "$PERSOON" | jq -r '.adressering.adresregel1 // empty')
    ADRESREGEL2=$(echo "$PERSOON" | jq -r '.adressering.adresregel2 // empty')
    ADRESREGEL3=$(echo "$PERSOON" | jq -r '.adressering.adresregel3 // empty')
    
    if [ -n "$ADRESREGEL1" ] && [ "$ADRESREGEL1" != "null" ]; then
        echo "✅ Adresregel 1: $ADRESREGEL1"
        echo "✅ Adresregel 2: $ADRESREGEL2"
        echo "✅ Adresregel 3: $ADRESREGEL3"
    else
        echo "⚠️  Adresregels: Geen adres beschikbaar (verwacht voor sommige personen)"
    fi
    
    echo ""
done

echo "════════════════════════════════════════════════════════"
echo "  VOLLEDIGE RESPONSE VOORBEELD (Eerste persoon)"
echo "════════════════════════════════════════════════════════"
echo ""

curl -s -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=168149291&_limit=1" | jq '._embedded.ingeschrevenpersonen[0]' | head -80

echo ""
echo "════════════════════════════════════════════════════════"
echo "  TEST VOLTOOID"
echo "════════════════════════════════════════════════════════"
