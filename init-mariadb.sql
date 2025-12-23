-- MariaDB Database Setup voor Nextcloud met OpenRegister
-- Voer dit script uit met: mysql -u root < init-mariadb.sql

-- Database aanmaken
CREATE DATABASE IF NOT EXISTS nextcloud CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Gebruiker aanmaken (of bijwerken als deze al bestaat)
CREATE USER IF NOT EXISTS 'nextcloud_user'@'localhost' IDENTIFIED BY 'nextcloud_secure_pass_2024';
CREATE USER IF NOT EXISTS 'nextcloud_user'@'%' IDENTIFIED BY 'nextcloud_secure_pass_2024';

-- Rechten toekennen
GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'localhost';
GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'%';

-- Rechten verversen
FLUSH PRIVILEGES;

-- Bevestiging
SELECT '✅ Database en gebruiker aangemaakt!' AS status;
SELECT 'Database: nextcloud' AS database_name;
SELECT 'Gebruiker: nextcloud_user' AS user_name;








