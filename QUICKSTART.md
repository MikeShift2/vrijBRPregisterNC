# Quick Start - Nextcloud met OpenRegister Setup

## Stap 1: Database Aanmaken

Voer dit commando uit om de database aan te maken (je wordt gevraagd om je PostgreSQL wachtwoord):

```bash
psql -U postgres -f init-database.sql
```

Of als je een andere PostgreSQL gebruiker gebruikt:

```bash
psql -U [jouw_gebruiker] -f init-database.sql
```

## Stap 2: Services Starten

Start alle services (Nextcloud, Solr, Ollama):

```bash
docker-compose up -d
```

**Eerste start duurt 2-5 minuten** - wacht tot alle services klaar zijn.

Controleer de status:
```bash
docker-compose ps
```

## Stap 3: Toegang

Open je browser en ga naar: http://localhost:8080

Log in met:
- Gebruikersnaam: `admin`
- Wachtwoord: `admin_secure_pass_2024`

**⚠️ Belangrijk:** Wijzig deze wachtwoorden direct na de eerste login!

## Stap 4: OpenRegister Configureren

OpenRegister wordt automatisch geïnstalleerd. Na de eerste login:

1. **Controleer installatie**:
   ```bash
   docker exec -u 33 nextcloud php /var/www/html/occ app:list | grep openregister
   ```

2. **Configureer Solr** (via Nextcloud Admin → OpenRegister → Settings):
   - Solr URL: `http://solr:8983`
   - Test de verbinding

3. **Configureer Ollama** (optioneel, voor AI features):
   - Ollama URL: `http://ollama:11434`
   - Download een model:
     ```bash
     docker exec openregister-ollama ollama pull llama3.1
     ```

## Service URLs

- **Nextcloud**: http://localhost:8080
- **Solr Admin**: http://localhost:8983/solr
- **Ollama API**: http://localhost:11434

## Troubleshooting

### Database Verbindingsproblemen

Als Nextcloud niet kan verbinden met de database, controleer:
1. Of de database bestaat: `psql -U postgres -l | grep nextcloud`
2. Of de gebruiker bestaat: `psql -U postgres -c "\du" | grep nextcloud_user`
3. Of de wachtwoorden in `.env` overeenkomen met de database

### OpenRegister Installatie Problemen

Controleer de setup logs:
```bash
docker exec nextcloud tail -f /var/log/openregister-setup.log
```

Handmatig installeren:
```bash
docker exec -u 33 nextcloud php /var/www/html/occ app:install openregister
docker exec -u 33 nextcloud php /var/www/html/occ app:enable openregister
```

### Service Status

Controleer of alle services draaien:
```bash
docker-compose ps
docker-compose logs -f nextcloud
docker-compose logs -f openregister-solr
docker-compose logs -f openregister-ollama
```

