# PostgreSQL Database Koppelen als OpenRegister Source

## Methode 1: Via Script (Aanbevolen)

Voer het script uit:

```bash
cd /Users/mikederuiter/Nextcloud
./add-postgres-source.sh
```

Het script vraagt om:
- Database naam
- Database host (bijv. `host.docker.internal` voor lokale PostgreSQL)
- Database poort (standaard: 5432)
- Database gebruiker
- Database wachtwoord
- Titel voor de source
- Beschrijving (optioneel)

## Methode 2: Handmatig via Database

Je kunt ook direct via de database een source toevoegen:

```bash
docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud
```

Voer dan deze SQL uit:

```sql
INSERT INTO oc_openregister_sources 
(uuid, title, description, version, database_url, type, created, updated)
VALUES 
(
    UUID(),  -- Genereert automatisch een UUID
    'Mijn PostgreSQL Register',  -- Titel
    'Beschrijving van het register',  -- Beschrijving
    '0.0.1',  -- Versie
    'pgsql://gebruiker:wachtwoord@host.docker.internal:5432/databasenaam',  -- Database URL
    'postgresql',  -- Type
    NOW(),
    NOW()
);
```

## Database URL Format

De `database_url` moet in dit format zijn:

```
pgsql://[gebruiker]:[wachtwoord]@[host]:[poort]/[databasenaam]
```

**Voorbeelden:**

- Lokale PostgreSQL (vanuit Docker):
  ```
  pgsql://postgres:mijnwachtwoord@host.docker.internal:5432/mijnregister
  ```

- PostgreSQL op specifiek IP:
  ```
  pgsql://gebruiker:wachtwoord@192.168.1.100:5432/databasenaam
  ```

- PostgreSQL met speciale karakters in wachtwoord:
  ```
  pgsql://user:p%40ssw%3Aord@host:5432/db
  ```
  (Gebruik URL encoding: `@` = `%40`, `:` = `%3A`)

## Methode 3: Via Nextcloud Web Interface

1. Log in op Nextcloud: http://localhost:8080
2. Ga naar **OpenRegister** app
3. Navigeer naar **Sources** of **Registers**
4. Klik op **Nieuw** of **Add Source**
5. Vul de database gegevens in:
   - **Type**: PostgreSQL
   - **Database URL**: `pgsql://user:password@host:port/database`
   - **Titel**: Naam van je register
   - **Beschrijving**: Optionele beschrijving

## Na het Toevoegen

Na het toevoegen van een source:

1. **Maak een Register aan** dat deze source gebruikt:
   - Ga naar OpenRegister → Registers
   - Klik op "Nieuw Register"
   - Selecteer de source die je zojuist hebt toegevoegd
   - Configureer het schema

2. **Test de Verbinding**:
   ```bash
   # Test vanuit Nextcloud container
   docker exec nextcloud curl -s http://localhost:8080/apps/openregister/api/sources
   ```

## Belangrijke Opmerkingen

- **Netwerk toegang**: Zorg dat de PostgreSQL database bereikbaar is vanaf de Nextcloud container
  - Voor lokale PostgreSQL: gebruik `host.docker.internal`
  - Voor Docker PostgreSQL: gebruik de container naam of IP
  - Voor externe servers: gebruik het IP adres of hostname

- **Firewall**: Controleer of poort 5432 open is voor de Nextcloud container

- **Database rechten**: De database gebruiker moet lees- en schrijfrechten hebben op de database

- **Wachtwoord encoding**: Speciale karakters in wachtwoorden moeten URL-gecodeerd worden

## Troubleshooting

### Verbindingsproblemen

Test de verbinding vanuit de Nextcloud container:

```bash
# Test PostgreSQL verbinding
docker exec nextcloud bash -c "PGPASSWORD='wachtwoord' psql -h host.docker.internal -U gebruiker -d databasenaam -c 'SELECT version();'"
```

### Source niet zichtbaar

Controleer of de source is toegevoegd:

```bash
docker exec nextcloud-db mariadb -u nextcloud_user -p'nextcloud_secure_pass_2024' nextcloud -e "SELECT id, title, type, database_url FROM oc_openregister_sources;"
```

### Database URL encoding

Als je problemen hebt met speciale karakters, gebruik URL encoding:
- `@` → `%40`
- `:` → `%3A`
- `#` → `%23`
- `?` → `%3F`
- `&` → `%26`








