# Nextcloud Docker Setup met OpenRegister

Nextcloud geïnstalleerd via Docker, verbonden met een lokale PostgreSQL database, inclusief OpenRegister app met Solr en Ollama voor AI-functionaliteiten.

## Vereisten

- Docker en Docker Compose geïnstalleerd
- PostgreSQL draait lokaal op je machine
- PostgreSQL database en gebruiker aangemaakt (zie setup hieronder)
- Minimaal 8GB RAM beschikbaar (voor Solr en Ollama)
- 20GB vrije schijfruimte

## Setup

### 1. PostgreSQL Database Voorbereiden

Maak eerst een database en gebruiker aan in je lokale PostgreSQL:

```bash
# Verbind met PostgreSQL
psql -U postgres

# Maak database aan
CREATE DATABASE nextcloud;

# Maak gebruiker aan (vervang 'wachtwoord' met een veilig wachtwoord)
CREATE USER nextcloud_user WITH PASSWORD 'wachtwoord';

# Geef rechten aan gebruiker
GRANT ALL PRIVILEGES ON DATABASE nextcloud TO nextcloud_user;

# Voor PostgreSQL 15+ moet je ook connectie rechten geven
ALTER DATABASE nextcloud OWNER TO nextcloud_user;

# Exit PostgreSQL
\q
```

### 2. Environment Variabelen Configureren

Kopieer `.env.example` naar `.env` en pas de waarden aan:

```bash
cp .env.example .env
```

Pas in `.env` de volgende waarden aan:
- `POSTGRES_DB`: De naam van je PostgreSQL database (standaard: `nextcloud`)
- `POSTGRES_USER`: De PostgreSQL gebruiker die je hebt aangemaakt
- `POSTGRES_PASSWORD`: Het wachtwoord van de PostgreSQL gebruiker
- `NEXTCLOUD_ADMIN_USER`: Je gewenste Nextcloud admin gebruikersnaam
- `NEXTCLOUD_ADMIN_PASSWORD`: Je gewenste Nextcloud admin wachtwoord

### 3. PostgreSQL Toegang Configureren

Zorg ervoor dat je PostgreSQL database toegankelijk is vanaf Docker containers. Op macOS en Windows werkt `host.docker.internal` automatisch. Op Linux moet je mogelijk de PostgreSQL configuratie aanpassen:

**Voor Linux gebruikers:**

Pas `/etc/postgresql/[versie]/main/pg_hba.conf` aan:
```
host    all             all             172.17.0.0/16           md5
```

En pas `postgresql.conf` aan:
```
listen_addresses = 'localhost,172.17.0.1'
```

### 4. Nextcloud Starten

Start de Nextcloud container:

```bash
docker-compose up -d
```

### 5. Toegang tot Nextcloud

Open je browser en ga naar:
```
http://localhost:8080
```

Log in met de admin credentials die je hebt ingesteld in `.env`.

## OpenRegister Setup

OpenRegister wordt automatisch geïnstalleerd bij het starten van Nextcloud. De setup script draait op de achtergrond en installeert de app vanuit de Nextcloud App Store.

### Services

De docker-compose setup bevat de volgende services:

- **Nextcloud**: Nextcloud instance met OpenRegister app
- **Solr**: Full-text search engine (poort 8983)
- **Ollama**: Lokale LLM voor AI-functionaliteiten (poort 11434)
- **Nextcloud Cron**: Background jobs voor Nextcloud

### OpenRegister Configuratie

Na de eerste installatie moet je OpenRegister configureren:

1. **Solr Configuratie**:
   - Ga naar Nextcloud Admin → OpenRegister → Settings
   - Solr URL: `http://solr:8983`
   - Test de verbinding

2. **Ollama Configuratie**:
   - Ollama URL: `http://ollama:11434`
   - Download een model (bijv. `llama3.1`):
     ```bash
     docker exec openregister-ollama ollama pull llama3.1
     ```

### OpenRegister Logs Bekijken

```bash
# OpenRegister setup logs
docker exec nextcloud tail -f /var/log/openregister-setup.log

# Alle Nextcloud logs
docker-compose logs -f nextcloud

# Solr logs
docker-compose logs -f openregister-solr

# Ollama logs
docker-compose logs -f openregister-ollama
```

### OpenRegister Handmatig Installeren

Als de automatische installatie faalt:

```bash
# Installeer via Nextcloud CLI
docker exec -u 33 nextcloud php /var/www/html/occ app:install openregister

# Activeer de app
docker exec -u 33 nextcloud php /var/www/html/occ app:enable openregister
```

## Beheer

### Container Stoppen

```bash
docker-compose stop
```

### Container Starten

```bash
docker-compose start
```

### Container Herstarten

```bash
docker-compose restart
```

### Container Stoppen en Verwijderen

```bash
docker-compose down
```

**Let op:** Dit verwijdert alleen de container, niet de data volumes. Je data blijft behouden.

### Data Volledig Verwijderen

```bash
docker-compose down -v
```

**Waarschuwing:** Dit verwijdert alle Nextcloud data permanent!

### Logs Bekijken

```bash
# Nextcloud logs
docker-compose logs -f nextcloud

# Alle services
docker-compose logs -f

# Specifieke service
docker-compose logs -f openregister-solr
docker-compose logs -f openregister-ollama
```

## Troubleshooting

### Verbindingsproblemen met PostgreSQL

Als Nextcloud niet kan verbinden met PostgreSQL:

1. Controleer of PostgreSQL draait:
   ```bash
   psql -U postgres -c "SELECT version();"
   ```

2. Test de verbinding vanuit Docker:
   ```bash
   docker run --rm -it --network nextcloud_nextcloud_network postgres:latest psql -h host.docker.internal -U nextcloud_user -d nextcloud
   ```

3. Controleer firewall instellingen

### Poort Alleen in Gebruik

Als poorten al in gebruik zijn, pas dan `docker-compose.yml` aan:
```yaml
# Nextcloud poort wijzigen
ports:
  - "8081:80"  # Gebruik poort 8081 in plaats van 8080

# Solr poort wijzigen
openregister-solr:
  ports:
    - "8984:8983"  # Gebruik poort 8984 in plaats van 8983

# Ollama poort wijzigen
openregister-ollama:
  ports:
    - "11435:11434"  # Gebruik poort 11435 in plaats van 11434
```

### Solr Verbindingsproblemen

Als OpenRegister niet kan verbinden met Solr:

1. Controleer of Solr draait:
   ```bash
   docker-compose ps openregister-solr
   ```

2. Test Solr vanuit Nextcloud container:
   ```bash
   docker exec nextcloud curl -s http://solr:8983/solr/admin/info/system
   ```

3. Controleer Solr logs:
   ```bash
   docker-compose logs openregister-solr
   ```

### Ollama Verbindingsproblemen

Als OpenRegister niet kan verbinden met Ollama:

1. Controleer of Ollama draait:
   ```bash
   docker-compose ps openregister-ollama
   ```

2. Test Ollama vanuit Nextcloud container:
   ```bash
   docker exec nextcloud curl -s http://ollama:11434/api/tags
   ```

3. Controleer Ollama logs:
   ```bash
   docker-compose logs openregister-ollama
   ```

### Geheugen Problemen

Als services crashen door geheugengebrek:

1. Verhoog Docker geheugen limiet (Docker Desktop → Settings → Resources → Memory)
2. Verlaag Solr heap size in `docker-compose.yml`:
   ```yaml
   environment:
     - SOLR_HEAP=256m  # Verlaag van 512m naar 256m
   ```
3. Gebruik kleinere Ollama modellen (bijv. `phi3` in plaats van `llama3.1:70b`)

## Beveiliging

- **Wijzig altijd** de standaard wachtwoorden in `.env`
- Gebruik sterke wachtwoorden voor zowel PostgreSQL als Nextcloud admin account
- Overweeg een reverse proxy (nginx/traefik) voor productie gebruik
- Houd Nextcloud en Docker images up-to-date

## Updates

Om Nextcloud bij te werken:

```bash
docker-compose pull
docker-compose up -d
```

## Data Locatie

Data wordt opgeslagen in Docker volumes:

- **Nextcloud**: `nextcloud_data`
- **Solr**: `solr_data`
- **Ollama**: `ollama_data` (bevat gedownloade AI modellen)

Je kunt de locaties vinden met:

```bash
docker volume inspect nextcloud_nextcloud_data
docker volume inspect nextcloud_solr_data
docker volume inspect nextcloud_ollama_data
```

## OpenRegister Documentatie

Voor meer informatie over OpenRegister:
- [OpenRegister Documentatie](https://openregisters.app/docs)
- [Docker Development Setup](https://openregisters.app/docs/development/docker-setup)
- [GitHub Repository](https://github.com/conductionnl/openregister)

