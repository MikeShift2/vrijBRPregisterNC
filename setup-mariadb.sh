#!/bin/bash
# Script om MariaDB database aan te maken voor Nextcloud

echo "🔧 MariaDB database setup voor Nextcloud..."

echo ""
echo "Je wordt gevraagd om je macOS wachtwoord (voor sudo) en daarna om je MariaDB root wachtwoord."
echo "Als je MariaDB net hebt geïnstalleerd, kan het zijn dat er nog geen root wachtwoord is ingesteld."
echo ""

# Database aanmaken
sudo /opt/homebrew/opt/mariadb/bin/mariadb -u root << 'EOF'
CREATE DATABASE IF NOT EXISTS nextcloud CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'nextcloud_user'@'localhost' IDENTIFIED BY 'nextcloud_secure_pass_2024';
CREATE USER IF NOT EXISTS 'nextcloud_user'@'%' IDENTIFIED BY 'nextcloud_secure_pass_2024';
GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'localhost';
GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'%';
FLUSH PRIVILEGES;
SELECT '✅ Database en gebruiker aangemaakt!' AS status;
SELECT 'Database: nextcloud' AS database_name;
SELECT 'Gebruiker: nextcloud_user' AS user_name;
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ MariaDB database setup voltooid!"
    echo ""
    echo "Test de verbinding:"
    echo "  mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' -e 'USE nextcloud;'"
else
    echo ""
    echo "❌ Fout bij database setup"
    echo ""
    echo "Probeer handmatig:"
    echo "  sudo mariadb -u root"
    echo "  CREATE DATABASE nextcloud CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    echo "  CREATE USER 'nextcloud_user'@'localhost' IDENTIFIED BY 'nextcloud_secure_pass_2024';"
    echo "  GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'localhost';"
    echo "  FLUSH PRIVILEGES;"
fi








