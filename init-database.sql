-- Nextcloud PostgreSQL Database Setup
-- Voer dit script uit met: psql -U postgres -f init-database.sql
-- Of: psql -U [jouw_gebruiker] -f init-database.sql

-- Database aanmaken
CREATE DATABASE nextcloud;

-- Gebruiker aanmaken (of bijwerken als deze al bestaat)
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'nextcloud_user') THEN
        CREATE USER nextcloud_user WITH PASSWORD 'nextcloud_secure_pass_2024';
    ELSE
        ALTER USER nextcloud_user WITH PASSWORD 'nextcloud_secure_pass_2024';
    END IF;
END
$$;

-- Rechten toekennen
GRANT ALL PRIVILEGES ON DATABASE nextcloud TO nextcloud_user;
ALTER DATABASE nextcloud OWNER TO nextcloud_user;

-- Bevestiging
\echo '✅ Database en gebruiker aangemaakt!'
\echo 'Database: nextcloud'
\echo 'Gebruiker: nextcloud_user'

