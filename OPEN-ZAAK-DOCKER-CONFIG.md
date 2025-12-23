# Open Zaak Docker Configuratie

**Doel:** Open Zaak installeren en configureren in Docker omgeving

---

## Stap 1: Docker Compose Configuratie

### Open Zaak Service Toevoegen

Voeg de volgende service toe aan je `docker-compose.yml`:

```yaml
services:
  # ... bestaande services ...
  
  openzaak-db:
    image: postgres:15-alpine
    container_name: openzaak-db
    environment:
      POSTGRES_DB: openzaak
      POSTGRES_USER: openzaak
      POSTGRES_PASSWORD: openzaak_password
    volumes:
      - openzaak_db_data:/var/lib/postgresql/data
    networks:
      - nextcloud-network
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U openzaak"]
      interval: 10s
      timeout: 5s
      retries: 5

  openzaak:
    image: openzaak/open-zaak:latest
    container_name: openzaak
    depends_on:
      openzaak-db:
        condition: service_healthy
    environment:
      # Database
      DATABASE_URL: postgresql://openzaak:openzaak_password@openzaak-db:5432/openzaak
      
      # Django Settings
      SECRET_KEY: ${OPENZAAK_SECRET_KEY:-change-this-secret-key-in-production}
      ALLOWED_HOSTS: ${OPENZAAK_ALLOWED_HOSTS:-localhost,127.0.0.1,openzaak}
      DEBUG: ${OPENZAAK_DEBUG:-False}
      
      # API Settings
      API_ROOT: http://localhost:8000/api/v1
      
      # CORS Settings (voor Nextcloud integratie)
      CORS_ALLOWED_ORIGINS: ${OPENZAAK_CORS_ORIGINS:-http://localhost:8080}
      
      # Redis (voor caching, optioneel)
      REDIS_URL: ${REDIS_URL:-redis://redis:6379/0}
      
    ports:
      - "8000:8000"
    volumes:
      - openzaak_media:/app/media
      - openzaak_static:/app/static
    networks:
      - nextcloud-network
    command: >
      sh -c "
        python manage.py migrate &&
        python manage.py collectstatic --noinput &&
        gunicorn openzaak.wsgi:application --bind 0.0.0.0:8000 --workers 4
      "
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8000/api/v1/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  # Redis (optioneel, voor caching)
  redis:
    image: redis:7-alpine
    container_name: openzaak-redis
    networks:
      - nextcloud-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  # ... bestaande volumes ...
  openzaak_db_data:
  openzaak_media:
  openzaak_static:

networks:
  nextcloud-network:
    # ... bestaande network configuratie ...
```

---

## Stap 2: Environment Variabelen

Maak een `.env` bestand aan (of voeg toe aan bestaand `.env` bestand):

```env
# Open Zaak Configuratie
OPENZAAK_SECRET_KEY=your-super-secret-key-change-this-in-production
OPENZAAK_ALLOWED_HOSTS=localhost,127.0.0.1,openzaak,nextcloud
OPENZAAK_DEBUG=False
OPENZAAK_CORS_ORIGINS=http://localhost:8080,http://nextcloud:80

# Database (wordt gebruikt door Open Zaak)
OPENZAAK_DB_NAME=openzaak
OPENZAAK_DB_USER=openzaak
OPENZAAK_DB_PASSWORD=openzaak_password
OPENZAAK_DB_HOST=openzaak-db
OPENZAAK_DB_PORT=5432

# Redis (optioneel)
REDIS_URL=redis://redis:6379/0
```

**⚠️ Belangrijk:** Genereer een veilige SECRET_KEY:

```bash
python3 -c "import secrets; print(secrets.token_urlsafe(50))"
```

---

## Stap 3: Open Zaak Starten

### Start Open Zaak Services

```bash
# Start alleen Open Zaak services
docker-compose up -d openzaak-db redis openzaak

# Of start alles
docker-compose up -d
```

### Check Status

```bash
# Check of containers draaien
docker ps | grep openzaak

# Check logs
docker-compose logs -f openzaak
```

---

## Stap 4: Database Migraties

### Voer Migraties Uit

```bash
# Migraties uitvoeren
docker-compose exec openzaak python manage.py migrate

# Maak superuser aan (voor admin toegang)
docker-compose exec openzaak python manage.py createsuperuser
```

**Superuser Aanmaken:**
- Username: `admin`
- Email: `admin@example.com`
- Password: (kies een veilig wachtwoord)

---

## Stap 5: Open Zaak Testen

### Test API Endpoints

```bash
# Test root endpoint
curl http://localhost:8000/api/v1/

# Test zaken endpoint (moet leeg zijn)
curl http://localhost:8000/api/v1/zaken

# Test met authenticatie (na API key aanmaken)
curl -H "Authorization: Bearer YOUR_API_KEY" http://localhost:8000/api/v1/zaken
```

### Test Admin Interface

Open in browser:
```
http://localhost:8000/admin/
```

Login met superuser credentials.

---

## Stap 6: API Key Aanmaken

### Via Admin Interface

1. Ga naar `http://localhost:8000/admin/`
2. Login met superuser
3. Ga naar **Autorisaties** → **Applicaties**
4. Maak nieuwe applicatie aan
5. Kopieer **Client ID** en **Secret**

### Via Command Line

```bash
# Maak applicatie aan
docker-compose exec openzaak python manage.py shell

# In Python shell:
from oauth2_provider.models import Application
app = Application.objects.create(
    name="Nextcloud Open Register",
    client_type=Application.CLIENT_CONFIDENTIAL,
    authorization_grant_type=Application.GRANT_CLIENT_CREDENTIALS,
)
print(f"Client ID: {app.client_id}")
print(f"Secret: {app.client_secret}")
```

---

## Stap 7: Nextcloud Configuratie

### Voeg Configuratie Toe aan Nextcloud

Voeg toe aan `config/config.php`:

```php
'openzaak' => [
    'base_url' => 'http://openzaak:8000',
    'api_root' => 'http://openzaak:8000/api/v1',
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'timeout' => 30,
],
```

**Of gebruik environment variabelen:**

```php
'openzaak' => [
    'base_url' => getenv('OPENZAAK_BASE_URL') ?: 'http://openzaak:8000',
    'api_root' => getenv('OPENZAAK_API_ROOT') ?: 'http://openzaak:8000/api/v1',
    'client_id' => getenv('OPENZAAK_CLIENT_ID'),
    'client_secret' => getenv('OPENZAAK_CLIENT_SECRET'),
    'timeout' => 30,
],
```

---

## Stap 8: Network Configuratie

### Zorg dat Containers Elkaar Kunnen Bereiken

**Check Network:**

```bash
# Check of containers in hetzelfde network zitten
docker network inspect nextcloud-network

# Check of Open Zaak bereikbaar is vanuit Nextcloud container
docker-compose exec nextcloud ping -c 2 openzaak
```

**Als containers niet in hetzelfde network zitten:**

```bash
# Voeg Nextcloud toe aan Open Zaak network
docker network connect nextcloud-network nextcloud

# Of voeg Open Zaak toe aan Nextcloud network
docker network connect nextcloud-network openzaak
```

---

## Troubleshooting

### Probleem: Open Zaak start niet

**Check logs:**
```bash
docker-compose logs openzaak
```

**Mogelijke oorzaken:**
- Database niet beschikbaar
- SECRET_KEY niet ingesteld
- Port 8000 al in gebruik

**Oplossing:**
```bash
# Check database
docker-compose ps openzaak-db

# Check port
lsof -i :8000

# Herstart services
docker-compose restart openzaak
```

---

### Probleem: Database connectie faalt

**Check database:**
```bash
# Test database connectie
docker-compose exec openzaak-db psql -U openzaak -d openzaak -c "SELECT 1;"
```

**Check environment variabelen:**
```bash
docker-compose exec openzaak env | grep DATABASE
```

---

### Probleem: CORS errors

**Check CORS configuratie:**
```bash
# Check CORS_ALLOWED_ORIGINS
docker-compose exec openzaak env | grep CORS
```

**Oplossing:**
Voeg Nextcloud URL toe aan `CORS_ALLOWED_ORIGINS` in `.env`:
```env
OPENZAAK_CORS_ORIGINS=http://localhost:8080,http://nextcloud:80
```

---

## Verificatie Checklist

- [ ] Open Zaak container draait (`docker ps | grep openzaak`)
- [ ] Database migraties uitgevoerd (`docker-compose exec openzaak python manage.py migrate`)
- [ ] Superuser aangemaakt (`docker-compose exec openzaak python manage.py createsuperuser`)
- [ ] API endpoints werken (`curl http://localhost:8000/api/v1/`)
- [ ] Admin interface toegankelijk (`http://localhost:8000/admin/`)
- [ ] API key aangemaakt (Client ID + Secret)
- [ ] Nextcloud configuratie toegevoegd
- [ ] Network configuratie correct (containers kunnen elkaar bereiken)

---

## Volgende Stappen

Na installatie:

1. **Test Open Zaak API** - Zie `OPEN-ZAAK-OPEN-REGISTER-INTEGRATIE-PLAN.md`
2. **Configureer Schema ID 20** - Voor zaken in Open Register
3. **Bouw Integratie Controller** - Zie Fase 3 in integratieplan

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Configuratie handleiding compleet







