#!/bin/bash
# Script om PostgreSQL extensie te installeren voor OpenRegister

echo "🔧 PostgreSQL extensie installeren voor OpenRegister..."

# Vraag om PostgreSQL admin gebruiker
read -p "PostgreSQL admin gebruiker (standaard: postgres): " PG_USER
PG_USER=${PG_USER:-postgres}

# Vraag om database naam
read -p "Database naam (standaard: nextcloud): " DB_NAME
DB_NAME=${DB_NAME:-nextcloud}

echo ""
echo "Je wordt nu gevraagd om je PostgreSQL wachtwoord..."

# Installeer de extensie
psql -U "$PG_USER" -d "$DB_NAME" -c "CREATE EXTENSION IF NOT EXISTS btree_gin;" 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Extensie succesvol geïnstalleerd!"
    echo ""
    echo "Probeer nu OpenRegister opnieuw te installeren:"
    echo "  docker exec -u 33 nextcloud php /var/www/html/occ app:install openregister"
else
    echo "❌ Fout bij installeren van extensie"
    echo ""
    echo "Probeer handmatig:"
    echo "  psql -U $PG_USER -d $DB_NAME"
    echo "  CREATE EXTENSION IF NOT EXISTS btree_gin;"
fi








