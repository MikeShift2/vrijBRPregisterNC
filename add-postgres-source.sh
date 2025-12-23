#!/bin/bash
# Script om een PostgreSQL database als source toe te voegen aan OpenRegister

echo "🔗 PostgreSQL database koppelen als OpenRegister source..."
echo ""

# Vraag om database gegevens
read -p "Database naam (bijv. mijnregister): " DB_NAME
read -p "Database host (bijv. host.docker.internal of IP): " DB_HOST
read -p "Database poort (standaard: 5432): " DB_PORT
DB_PORT=${DB_PORT:-5432}
read -p "Database gebruiker: " DB_USER
read -s -p "Database wachtwoord: " DB_PASSWORD
echo ""
read -p "Titel voor deze source: " SOURCE_TITLE
read -p "Beschrijving (optioneel): " SOURCE_DESC

# Genereer UUID
UUID=$(cat /proc/sys/kernel/random/uuid 2>/dev/null || uuidgen 2>/dev/null || echo $(date +%s | sha256sum | head -c 32))

# Maak database URL
# Format: pgsql://user:password@host:port/database
# URL encoding voor speciale karakters in wachtwoord
DB_PASSWORD_ENCODED=$(echo -n "$DB_PASSWORD" | sed 's/@/%40/g; s/:/%3A/g; s/#/%23/g; s/\?/%3F/g; s/&/%26/g')
DB_URL="pgsql://${DB_USER}:${DB_PASSWORD_ENCODED}@${DB_HOST}:${DB_PORT}/${DB_NAME}"

echo ""
echo "📝 Source gegevens:"
echo "  Titel: $SOURCE_TITLE"
echo "  Database: $DB_NAME"
echo "  Host: $DB_HOST:$DB_PORT"
echo "  URL: pgsql://${DB_USER}:***@${DB_HOST}:${DB_PORT}/${DB_NAME}"
echo ""

# Voeg source toe via database
docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud << EOF
INSERT INTO oc_openregister_sources 
(uuid, title, description, version, database_url, type, created, updated)
VALUES 
('$UUID', '$SOURCE_TITLE', '$SOURCE_DESC', '0.0.1', '$DB_URL', 'postgresql', NOW(), NOW());
EOF

if [ $? -eq 0 ]; then
    echo "✅ Source succesvol toegevoegd!"
    echo ""
    echo "Je kunt deze source nu gebruiken in OpenRegister via:"
    echo "  http://localhost:8080/apps/openregister"
else
    echo "❌ Fout bij toevoegen van source"
    exit 1
fi

