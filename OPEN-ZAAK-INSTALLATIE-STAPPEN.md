# Open Zaak Installatie Stappen

**Datum:** 2025-01-27  
**Status:** Klaar voor uitvoering

---

## ✅ Wat is Al Gedaan

1. ✅ Open Zaak service toegevoegd aan `docker-compose.yml`
2. ✅ Open Zaak database service toegevoegd
3. ✅ Redis service toegevoegd (voor caching)
4. ✅ Network configuratie aangepast
5. ✅ Volumes aangemaakt voor persistentie

---

## 📋 Stappen om Open Zaak te Starten

### Stap 1: Environment Variabelen Instellen

Maak een `.env` bestand aan (of voeg toe aan bestaand `.env`):

```bash
# Open Zaak Database Password
OPENZAAK_DB_PASSWORD=openzaak_secure_password_2024

# Open Zaak Secret Key (genereer een veilige key)
OPENZAAK_SECRET_KEY=$(python3 -c "import secrets; print(secrets.token_urlsafe(50))")

# Open Zaak Allowed Hosts
OPENZAAK_ALLOWED_HOSTS=localhost,127.0.0.1,openzaak,nextcloud

# Open Zaak Debug (False voor productie)
OPENZAAK_DEBUG=False

# Open Zaak CORS Origins (voor Nextcloud integratie)
OPENZAAK_CORS_ORIGINS=http://localhost:8080,http://nextcloud:80
```

**Of genereer SECRET_KEY handmatig:**

```bash
python3 -c "import secrets; print(secrets.token_urlsafe(50))"
```

Kopieer de output en gebruik deze als `OPENZAAK_SECRET_KEY`.

---

### Stap 2: Open Zaak Services Starten

```bash
# Start alleen Open Zaak services
docker-compose up -d openzaak-db redis openzaak

# Of start alles (inclusief bestaande services)
docker-compose up -d
```

**Check Status:**

```bash
# Check of containers draaien
docker ps | grep openzaak

# Check logs
docker-compose logs -f openzaak
```

---

### Stap 3: Database Migraties Uitvoeren

```bash
# Wacht tot database klaar is (healthcheck)
sleep 10

# Voer migraties uit
docker-compose exec openzaak python manage.py migrate

# Maak superuser aan (voor admin toegang)
docker-compose exec openzaak python manage.py createsuperuser
```

**Superuser Aanmaken:**
- Username: `admin`
- Email: `admin@example.com`
- Password: (kies een veilig wachtwoord)

---

### Stap 4: Open Zaak Testen

**Test API Endpoints:**

```bash
# Test root endpoint
curl http://localhost:8000/api/v1/

# Test zaken endpoint (moet leeg zijn of 401/403)
curl http://localhost:8000/api/v1/zaken

# Test admin interface
curl http://localhost:8000/admin/
```

**Test in Browser:**

Open in browser:
- API: `http://localhost:8000/api/v1/`
- Admin: `http://localhost:8000/admin/`

Login met superuser credentials.

---

### Stap 5: API Key Aanmaken

**Via Admin Interface:**

1. Ga naar `http://localhost:8000/admin/`
2. Login met superuser
3. Ga naar **Autorisaties** → **Applicaties**
4. Klik op **Add Application**
5. Vul in:
   - **Name:** `Nextcloud Open Register`
   - **Client type:** `Confidential`
   - **Authorization grant type:** `Client credentials`
6. Klik **Save**
7. Kopieer **Client ID** en **Secret**

**Via Command Line:**

```bash
docker-compose exec openzaak python manage.py shell
```

In Python shell:
```python
from oauth2_provider.models import Application

app = Application.objects.create(
    name="Nextcloud Open Register",
    client_type=Application.CLIENT_CONFIDENTIAL,
    authorization_grant_type=Application.GRANT_CLIENT_CREDENTIALS,
)

print(f"Client ID: {app.client_id}")
print(f"Secret: {app.client_secret}")
```

**Bewaar deze credentials!** Je hebt ze nodig voor Nextcloud configuratie.

---

### Stap 6: Nextcloud Configuratie

**Voeg toe aan `config/config.php`:**

```php
'openzaak' => [
    'base_url' => 'http://openzaak:8000',
    'api_root' => 'http://openzaak:8000/api/v1',
    'client_id' => 'YOUR_CLIENT_ID_HERE',
    'client_secret' => 'YOUR_CLIENT_SECRET_HERE',
    'timeout' => 30,
],
```

**Of gebruik environment variabelen:**

Voeg toe aan `.env`:
```env
OPENZAAK_CLIENT_ID=your_client_id_here
OPENZAAK_CLIENT_SECRET=your_client_secret_here
```

En in `config/config.php`:
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

### Stap 7: Network Verificatie

**Test of containers elkaar kunnen bereiken:**

```bash
# Test vanuit Nextcloud container naar Open Zaak
docker-compose exec nextcloud ping -c 2 openzaak

# Test vanuit Open Zaak container naar Nextcloud
docker-compose exec openzaak ping -c 2 nextcloud

# Test database connectie
docker-compose exec openzaak-db psql -U openzaak -d openzaak -c "SELECT 1;"
```

---

## ✅ Verificatie Checklist

- [ ] Open Zaak database container draait (`docker ps | grep openzaak-db`)
- [ ] Redis container draait (`docker ps | grep redis`)
- [ ] Open Zaak container draait (`docker ps | grep openzaak`)
- [ ] Database migraties uitgevoerd (`docker-compose exec openzaak python manage.py migrate`)
- [ ] Superuser aangemaakt (`docker-compose exec openzaak python manage.py createsuperuser`)
- [ ] API endpoints werken (`curl http://localhost:8000/api/v1/`)
- [ ] Admin interface toegankelijk (`http://localhost:8000/admin/`)
- [ ] API key aangemaakt (Client ID + Secret)
- [ ] Nextcloud configuratie toegevoegd
- [ ] Network configuratie correct (containers kunnen elkaar bereiken)

---

## 🔧 Troubleshooting

### Probleem: Open Zaak start niet

**Check logs:**
```bash
docker-compose logs openzaak
```

**Mogelijke oorzaken:**
- Database niet beschikbaar → Check `docker-compose ps openzaak-db`
- SECRET_KEY niet ingesteld → Check `.env` bestand
- Port 8000 al in gebruik → Check `lsof -i :8000`

**Oplossing:**
```bash
# Herstart services
docker-compose restart openzaak-db redis openzaak

# Check health status
docker-compose ps
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

**Oplossing:**
- Check `.env` bestand voor `OPENZAAK_DB_PASSWORD`
- Check of database container draait
- Check network configuratie

---

### Probleem: CORS errors

**Check CORS configuratie:**
```bash
docker-compose exec openzaak env | grep CORS
```

**Oplossing:**
Voeg Nextcloud URL toe aan `OPENZAAK_CORS_ORIGINS` in `.env`:
```env
OPENZAAK_CORS_ORIGINS=http://localhost:8080,http://nextcloud:80
```

Herstart Open Zaak:
```bash
docker-compose restart openzaak
```

---

## 📚 Volgende Stappen

Na succesvolle installatie:

1. **Test Open Zaak API** - Zie `OPEN-ZAAK-OPEN-REGISTER-INTEGRATIE-PLAN.md`
2. **Configureer Schema ID 20** - Voor zaken in Open Register
3. **Bouw Integratie Controller** - Zie Fase 3 in integratieplan

---

## 🎯 Quick Start Commands

```bash
# 1. Start Open Zaak
docker-compose up -d openzaak-db redis openzaak

# 2. Wacht tot database klaar is
sleep 10

# 3. Voer migraties uit
docker-compose exec openzaak python manage.py migrate

# 4. Maak superuser aan
docker-compose exec openzaak python manage.py createsuperuser

# 5. Test API
curl http://localhost:8000/api/v1/

# 6. Check logs
docker-compose logs -f openzaak
```

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Installatie handleiding compleet







