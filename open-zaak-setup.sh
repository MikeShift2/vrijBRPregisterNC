#!/bin/bash

# Open Zaak Setup Script
# Dit script installeert en configureert Open Zaak in de Docker omgeving

set -e

echo "🚀 Open Zaak Setup Script"
echo "========================"
echo ""

# Check of docker-compose.yml bestaat
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ docker-compose.yml niet gevonden!"
    exit 1
fi

echo "✅ docker-compose.yml gevonden"
echo ""

# Check of Open Zaak al bestaat in docker-compose.yml
if grep -q "openzaak" docker-compose.yml; then
    echo "⚠️  Open Zaak lijkt al geconfigureerd te zijn in docker-compose.yml"
    echo "   Controleer de configuratie handmatig"
    exit 0
fi

echo "📋 Stappen voor Open Zaak installatie:"
echo ""
echo "1. Open Zaak service toevoegen aan docker-compose.yml"
echo "2. Open Zaak database aanmaken"
echo "3. Open Zaak configuratie bestanden aanmaken"
echo "4. Open Zaak starten"
echo ""
echo "⚠️  Let op: Dit script voegt alleen de configuratie toe."
echo "   Je moet docker-compose.yml handmatig aanpassen of dit script uitbreiden."
echo ""

# Maak openzaak directory aan voor configuratie
mkdir -p openzaak/config
mkdir -p openzaak/data

echo "✅ Directories aangemaakt:"
echo "   - openzaak/config/"
echo "   - openzaak/data/"
echo ""

echo "📝 Volgende stappen:"
echo ""
echo "1. Voeg Open Zaak service toe aan docker-compose.yml (zie OPEN-ZAAK-DOCKER-CONFIG.md)"
echo "2. Maak .env bestand aan voor Open Zaak configuratie"
echo "3. Start Open Zaak: docker-compose up -d openzaak"
echo "4. Voer migraties uit: docker-compose exec openzaak python manage.py migrate"
echo "5. Maak superuser aan: docker-compose exec openzaak python manage.py createsuperuser"
echo ""

echo "✅ Setup script voltooid!"
echo ""
echo "📚 Zie OPEN-ZAAK-DOCKER-CONFIG.md voor gedetailleerde instructies"







