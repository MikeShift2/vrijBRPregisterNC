#!/bin/bash

# Script om de Nextcloud database en gebruiker aan te maken of te repareren
# Dit script maakt de database aan als deze nog niet bestaat

echo "🔧 Nextcloud Database Setup/Reparatie"
echo "======================================"
echo ""

# Laad .env variabelen
if [ -f .env ]; then
    source .env
    DB_NAME="${POSTGRES_DB:-nextcloud}"
    DB_USER="${POSTGRES_USER:-nextcloud_user}"
    DB_PASSWORD="${POSTGRES_PASSWORD:-nextcloud_secure_pass_2024}"
else
    echo "❌ .env bestand niet gevonden!"
    exit 1
fi

echo "📋 Configuratie uit .env:"
echo "   Database: $DB_NAME"
echo "   Gebruiker: $DB_USER"
echo "   Wachtwoord: [verborgen]"
echo ""

# Gebruik environment variable als beschikbaar, anders vraag interactief
if [ -n "$PGPASSWORD" ]; then
    PG_ADMIN_PASSWORD="$PGPASSWORD"
    echo "🔐 PostgreSQL wachtwoord uit environment variable gebruikt"
else
    echo "🔐 Voer je PostgreSQL admin wachtwoord in (voor gebruiker 'postgres'):"
    read -s PG_ADMIN_PASSWORD
fi
echo ""

export PGPASSWORD="$PG_ADMIN_PASSWORD"

echo "🔄 Verbinden met PostgreSQL..."

# Test verbinding met postgres gebruiker
if psql -h localhost -U postgres -d postgres -c "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Verbonden met PostgreSQL"
else
    echo "❌ Kan niet verbinden met PostgreSQL. Controleer je wachtwoord."
    unset PGPASSWORD
    exit 1
fi

echo ""
echo "🗄️  Database controleren/aanmaken..."

# Controleer of database bestaat
DB_EXISTS=$(psql -h localhost -U postgres -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'")

if [ "$DB_EXISTS" = "1" ]; then
    echo "⚠️  Database '$DB_NAME' bestaat al."
else
    echo "📦 Database '$DB_NAME' aanmaken..."
    psql -h localhost -U postgres -d postgres <<EOF
CREATE DATABASE $DB_NAME;
EOF
    echo "✅ Database aangemaakt"
fi

echo ""
echo "👤 Gebruiker controleren/aanmaken..."

# Controleer of gebruiker bestaat
USER_EXISTS=$(psql -h localhost -U postgres -d postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'")

if [ "$USER_EXISTS" = "1" ]; then
    echo "⚠️  Gebruiker '$DB_USER' bestaat al. Wachtwoord bijwerken..."
    psql -h localhost -U postgres -d postgres <<EOF
ALTER USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
EOF
    echo "✅ Wachtwoord bijgewerkt"
else
    echo "📦 Gebruiker '$DB_USER' aanmaken..."
    psql -h localhost -U postgres -d postgres <<EOF
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
EOF
    echo "✅ Gebruiker aangemaakt"
fi

echo ""
echo "🔑 Rechten toekennen..."

psql -h localhost -U postgres -d postgres <<EOF
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
ALTER DATABASE $DB_NAME OWNER TO $DB_USER;
EOF

echo "✅ Rechten toegekend"
echo ""

# Test de verbinding met de nieuwe gebruiker
echo "🧪 Verbinding testen met nieuwe gebruiker..."
export PGPASSWORD="$DB_PASSWORD"
if psql -h localhost -U "$DB_USER" -d "$DB_NAME" -c "SELECT version();" > /dev/null 2>&1; then
    echo "✅ Verbinding succesvol!"
else
    echo "⚠️  Verbinding test gefaald, maar database is aangemaakt."
fi

unset PGPASSWORD

echo ""
echo "✨ Database setup voltooid!"
echo ""
echo "Herstart Nextcloud container:"
echo "   docker-compose restart nextcloud"
echo ""
echo "Of bekijk de logs:"
echo "   docker-compose logs -f nextcloud"
echo ""

