#!/bin/bash
# Script om PostgreSQL extensie te installeren voor OpenRegister

echo "🔧 PostgreSQL extensie installeren voor OpenRegister..."

# Probeer via Docker te verbinden met lokale PostgreSQL
docker run --rm --network nextcloud_nextcloud_network \
  -e PGPASSWORD="${POSTGRES_PASSWORD:-nextcloud_secure_pass_2024}" \
  postgres:latest \
  psql -h host.docker.internal \
       -U "${POSTGRES_USER:-nextcloud_user}" \
       -d "${POSTGRES_DB:-nextcloud}" \
       -c "CREATE EXTENSION IF NOT EXISTS btree_gin;" 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Extensie succesvol geïnstalleerd!"
    echo ""
    echo "Installeer nu OpenRegister:"
    docker exec -u 33 nextcloud php /var/www/html/occ app:install openregister
    docker exec -u 33 nextcloud php /var/www/html/occ app:enable openregister
else
    echo "❌ Fout bij installeren via Docker"
    echo ""
    echo "Probeer handmatig via psql:"
    echo "  psql -U postgres -d nextcloud"
    echo "  CREATE EXTENSION IF NOT EXISTS btree_gin;"
fi








