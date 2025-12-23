#!/bin/bash

# Eenvoudig script om Nextcloud database aan te maken
# Voert het SQL script uit met het juiste wachtwoord

echo "🔧 Nextcloud Database Aanmaken"
echo "=============================="
echo ""
echo "Voer je PostgreSQL wachtwoord in voor gebruiker 'postgres':"
echo ""

cd "$(dirname "$0")"

# Probeer het SQL script uit te voeren
psql -h localhost -U postgres -f init-database.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Database succesvol aangemaakt!"
    echo ""
    echo "Nextcloud zou nu moeten kunnen verbinden."
    echo "Controleer met: docker-compose logs -f nextcloud"
else
    echo ""
    echo "❌ Fout bij aanmaken database. Controleer je wachtwoord."
fi

