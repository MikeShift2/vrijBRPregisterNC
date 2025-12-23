# pgAdmin - Probev Database Verbinding Instructies

## Database Configuratie

De **probev** database is een **schema** binnen de **bevax** database. Dit is belangrijk om te begrijpen!

### Verbindingsgegevens

- **Host:** `localhost` (voor pgAdmin) of `host.docker.internal` (voor Docker containers)
- **Port:** `5432`
- **Database:** `bevax` (niet "probev"!)
- **Schema:** `probev` (dit is het schema binnen de bevax database)
- **Username:** `postgres`
- **Password:** `postgres`

## Waarom zie je de probev database niet in pgAdmin?

**Probev is geen database, het is een schema!** Je moet verbinden met de **bevax** database en dan het **probev** schema selecteren.

## Stappen om verbinding te maken in pgAdmin

### 1. Start PostgreSQL (als het niet draait)

```bash
# Start PostgreSQL via Homebrew
brew services start postgresql@17

# Of start handmatig
pg_ctl -D /opt/homebrew/var/postgresql@17 start
```

### 2. Maak een nieuwe server verbinding in pgAdmin

1. Open pgAdmin
2. Klik met rechts op **Servers** → **Create** → **Server**
3. Vul de volgende gegevens in:

**General tab:**
- **Name:** Bevax Database (of een andere naam naar keuze)

**Connection tab:**
- **Host name/address:** `localhost`
- **Port:** `5432`
- **Maintenance database:** `postgres` (of `bevax` als die bestaat)
- **Username:** `postgres`
- **Password:** `postgres`
- **Save password:** ✓ (optioneel, maar handig)

4. Klik op **Save**

### 3. Navigeer naar het probev schema

Na het verbinden:

1. Vouw de server uit: **Servers** → **Bevax Database**
2. Vouw **Databases** uit
3. Vouw **bevax** uit (als deze bestaat)
4. Vouw **Schemas** uit
5. Vouw **probev** uit
6. Klik op **Tables** om alle tabellen te zien

## Als de bevax database niet bestaat

Als je de `bevax` database niet ziet, moet deze eerst worden aangemaakt:

```bash
# Verbind met PostgreSQL
psql -h localhost -U postgres

# Maak de database aan
CREATE DATABASE bevax;

# Maak het schema aan (als het nog niet bestaat)
\c bevax
CREATE SCHEMA IF NOT EXISTS probev;

# Geef rechten aan de postgres gebruiker
GRANT ALL PRIVILEGES ON DATABASE bevax TO postgres;
GRANT ALL PRIVILEGES ON SCHEMA probev TO postgres;

# Exit
\q
```

## Als je het wachtwoord niet weet

Als het wachtwoord `postgres` niet werkt, kun je het resetten:

```bash
# Stop PostgreSQL eerst
brew services stop postgresql@17

# Start PostgreSQL in single-user mode (macOS)
/opt/homebrew/opt/postgresql@17/bin/postgres --single -D /opt/homebrew/var/postgresql@17 postgres

# In de PostgreSQL prompt:
ALTER USER postgres WITH PASSWORD 'postgres';
\q

# Start PostgreSQL weer normaal
brew services start postgresql@17
```

Of als je toegang hebt via een andere gebruiker:

```bash
psql -h localhost -U $(whoami) -d postgres
ALTER USER postgres WITH PASSWORD 'postgres';
```

## Verificatie

Test of de verbinding werkt:

```bash
# Test verbinding
psql -h localhost -U postgres -d bevax -c "\dn"

# Dit zou het probev schema moeten tonen
```

Of via SQL in pgAdmin:

```sql
-- Toon alle schemas in de bevax database
SELECT schema_name 
FROM information_schema.schemata 
WHERE catalog_name = 'bevax';

-- Toon alle tabellen in het probev schema
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'probev'
ORDER BY table_name;
```

## Belangrijke Tabellen in probev Schema

Volgens de documentatie bevat het probev schema 198 tabellen. Belangrijkste zijn:

- `pl` - Persoonslijst kleerhanger
- `inw_ax` - Inwoner (cat 1)
- `vb_ax` - Verblijf (cat 8)
- `huw_ax` - Huwelijk / GPS (cat 5)
- `nat_ax` - Nationaliteit (cat 4)
- `gezag_ax` - Gezag (cat 11)
- `reisd_ax` - Reisdocumenten (cat 12)
- En nog veel meer...

## Troubleshooting

### "Connection refused" fout
- Controleer of PostgreSQL draait: `brew services list | grep postgres`
- Start PostgreSQL: `brew services start postgresql@17`

### "Database bevax does not exist"
- Maak de database aan zoals beschreven boven

### "Schema probev does not exist"
- Maak het schema aan zoals beschreven boven

### "Password authentication failed"
- Reset het wachtwoord zoals beschreven boven
- Of gebruik je macOS gebruikersnaam zonder wachtwoord (als trust authentication is ingesteld)

### Database zichtbaar maar geen tabellen
- Controleer of je naar het juiste schema kijkt (probev, niet public)
- Controleer of de data is geïmporteerd

## Quick Reference

**Voor pgAdmin:**
- Server: `localhost:5432`
- Database: `bevax`
- Schema: `probev`
- Username: `postgres`
- Password: `postgres`

**Voor command line:**
```bash
psql -h localhost -U postgres -d bevax -c "SET search_path TO probev; SELECT COUNT(*) FROM inw_ax;"
```

