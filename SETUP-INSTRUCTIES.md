# Nextcloud Database Setup Instructies

## Probleem
Nextcloud kan niet verbinden met PostgreSQL omdat de gebruiker `nextcloud_user` nog niet bestaat.

## Oplossing

Voer **één** van de volgende opties uit:

### Optie 1: Via het reparatie script (aanbevolen)

```bash
cd /Users/mikederuiter/Nextcloud
./fix-database.sh
```

Dit script vraagt om je PostgreSQL admin wachtwoord en maakt dan automatisch de database en gebruiker aan met het juiste wachtwoord uit `.env`.

### Optie 2: Direct via psql

```bash
cd /Users/mikederuiter/Nextcloud
psql -h localhost -U postgres -f init-database.sql
```

Je wordt gevraagd om je PostgreSQL wachtwoord.

### Optie 3: Handmatig via psql

```bash
psql -h localhost -U postgres
```

Voer dan deze commando's uit:

```sql
CREATE DATABASE nextcloud;
CREATE USER nextcloud_user WITH PASSWORD 'nextcloud_secure_pass_2024';
GRANT ALL PRIVILEGES ON DATABASE nextcloud TO nextcloud_user;
ALTER DATABASE nextcloud OWNER TO nextcloud_user;
\q
```

## Na het aanmaken van de database

1. Herstart de Nextcloud container:
   ```bash
   docker-compose restart nextcloud
   ```

2. Controleer de logs:
   ```bash
   docker-compose logs -f nextcloud
   ```

3. Open Nextcloud in je browser:
   ```
   http://localhost:8080
   ```

4. Log in met:
   - Gebruikersnaam: `admin`
   - Wachtwoord: `admin_secure_pass_2024`

**⚠️ Belangrijk:** Wijzig deze wachtwoorden direct na de eerste login!

## Troubleshooting

Als je het PostgreSQL wachtwoord niet weet:

1. Probeer eerst zonder wachtwoord (als trust authentication is ingesteld)
2. Of reset het wachtwoord:
   ```bash
   psql -h localhost -U postgres -c "ALTER USER postgres WITH PASSWORD 'nieuw_wachtwoord';"
   ```

