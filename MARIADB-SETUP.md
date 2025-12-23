# MariaDB Setup voor Nextcloud

## Database Aanmaken

Voer deze commando's uit in je terminal:

```bash
sudo mariadb -u root
```

Voer dan deze SQL commando's uit:

```sql
CREATE DATABASE IF NOT EXISTS nextcloud CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'nextcloud_user'@'localhost' IDENTIFIED BY 'nextcloud_secure_pass_2024';
CREATE USER IF NOT EXISTS 'nextcloud_user'@'%' IDENTIFIED BY 'nextcloud_secure_pass_2024';
GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'localhost';
GRANT ALL PRIVILEGES ON nextcloud.* TO 'nextcloud_user'@'%';
FLUSH PRIVILEGES;
EXIT;
```

## MariaDB Configureren voor Docker Toegang

Om Docker containers toegang te geven tot MariaDB, moet je MariaDB configureren om te luisteren op alle interfaces:

1. Bewerk het configuratiebestand:
   ```bash
   sudo nano /opt/homebrew/etc/my.cnf
   ```

2. Voeg toe onder `[mysqld]`:
   ```
   bind-address = 0.0.0.0
   ```

3. Herstart MariaDB:
   ```bash
   brew services restart mariadb
   ```

## Test de Verbinding

```bash
mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' -e "USE nextcloud; SELECT 'Connection successful!' AS status;"
```

## Volgende Stappen

Na het aanmaken van de database:
1. Start Nextcloud: `docker-compose up -d`
2. Nextcloud zal automatisch de database detecteren en configureren
3. OpenRegister installeren: `docker exec -u 33 nextcloud php /var/www/html/occ app:install openregister`








