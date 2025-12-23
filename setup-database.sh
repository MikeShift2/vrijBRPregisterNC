#!/bin/bash

# Nextcloud PostgreSQL Database Setup Script
# Dit script maakt de database en gebruiker aan voor Nextcloud

set -e

echo "🔧 Nextcloud PostgreSQL Database Setup"
echo "======================================"
echo ""

# Lees database naam uit .env
if [ -f .env ]; then
    source .env
    DB_NAME="${POSTGRES_DB:-nextcloud}"
    DB_USER="${POSTGRES_USER:-nextcloud_user}"
    DB_PASSWORD="${POSTGRES_PASSWORD:-nextcloud_secure_pass_2024}"
else
    echo "❌ .env bestand niet gevonden!"
    exit 1
fi

echo "📋 Configuratie:"
echo "   Database: $DB_NAME"
echo "   Gebruiker: $DB_USER"
echo ""

# Vraag om PostgreSQL admin wachtwoord
echo "🔐 Voer je PostgreSQL admin wachtwoord in (voor gebruiker 'postgres' of je huidige gebruiker):"
read -s PG_ADMIN_PASSWORD

# Probeer eerst met postgres gebruiker
PG_USER="postgres"
export PGPASSWORD="$PG_ADMIN_PASSWORD"

echo ""
echo "🔄 Verbinden met PostgreSQL..."

# Test verbinding
if psql -h localhost -U "$PG_USER" -d postgres -c "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Verbonden met PostgreSQL als gebruiker '$PG_USER'"
else
    # Probeer met huidige gebruiker
    PG_USER=$(whoami)
    export PGPASSWORD="$PG_ADMIN_PASSWORD"
    if psql -h localhost -U "$PG_USER" -d postgres -c "SELECT 1;" > /dev/null 2>&1; then
        echo "✅ Verbonden met PostgreSQL als gebruiker '$PG_USER'"
    else
        echo "❌ Kan niet verbinden met PostgreSQL. Controleer je wachtwoord en of PostgreSQL draait."
        exit 1
    fi
fi

echo ""
echo "🗄️  Database aanmaken..."

# Controleer of database al bestaat
if psql -h localhost -U "$PG_USER" -d postgres -lqt | cut -d \| -f 1 | grep -qw "$DB_NAME"; then
    echo "⚠️  Database '$DB_NAME' bestaat al. Overslaan..."
else
    psql -h localhost -U "$PG_USER" -d postgres <<EOF
CREATE DATABASE $DB_NAME;
EOF
    echo "✅ Database '$DB_NAME' aangemaakt"
fi

echo ""
echo "👤 Gebruiker aanmaken..."

# Controleer of gebruiker al bestaat
if psql -h localhost -U "$PG_USER" -d postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1; then
    echo "⚠️  Gebruiker '$DB_USER' bestaat al. Wachtwoord bijwerken..."
    psql -h localhost -U "$PG_USER" -d postgres <<EOF
ALTER USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
EOF
else
    psql -h localhost -U "$PG_USER" -d postgres <<EOF
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
EOF
    echo "✅ Gebruiker '$DB_USER' aangemaakt"
fi

echo ""
echo "🔑 Rechten toekennen..."

psql -h localhost -U "$PG_USER" -d postgres <<EOF
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
ALTER DATABASE $DB_NAME OWNER TO $DB_USER;
EOF

echo "✅ Rechten toegekend"
echo ""
echo "✨ Database setup voltooid!"
echo ""
echo "Je kunt nu Nextcloud starten met:"
echo "   docker-compose up -d"
echo ""

# Reset PGPASSWORD
unset PGPASSWORD

