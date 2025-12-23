# Routes Configuratie voor vrijBRP Dossiers API

## Overzicht

Routes zijn toegevoegd voor de vrijBRP Dossiers API mutatie endpoints.

## Routes Toegevoegd

De volgende routes zijn toegevoegd aan `routes_broken.php`:

```php
// vrijBRP Dossiers API - Mutatie endpoints
['name' => 'VrijBrpDossiers#createRelocation', 'url' => '/api/v1/relocations/intra', 'verb' => 'POST'],
['name' => 'VrijBrpDossiers#createBirth', 'url' => '/api/v1/birth', 'verb' => 'POST'],
['name' => 'VrijBrpDossiers#createCommitment', 'url' => '/api/v1/commitment', 'verb' => 'POST'],
['name' => 'VrijBrpDossiers#createDeath', 'url' => '/api/v1/deaths/in-municipality', 'verb' => 'POST'],
```

## Endpoints

### 1. Verhuizing Aanmaken
- **URL:** `/api/v1/relocations/intra`
- **Method:** `POST`
- **Controller:** `VrijBrpDossiersController::createRelocation()`
- **Beschrijving:** Maakt een nieuwe intra-gemeentelijke verhuizing aan

### 2. Geboorte Aanmaken
- **URL:** `/api/v1/birth`
- **Method:** `POST`
- **Controller:** `VrijBrpDossiersController::createBirth()`
- **Beschrijving:** Maakt een nieuwe geboorte aan

### 3. Partnerschap Aanmaken
- **URL:** `/api/v1/commitment`
- **Method:** `POST`
- **Controller:** `VrijBrpDossiersController::createCommitment()`
- **Beschrijving:** Maakt een nieuw partnerschap aan

### 4. Overlijden Aanmaken
- **URL:** `/api/v1/deaths/in-municipality`
- **Method:** `POST`
- **Controller:** `VrijBrpDossiersController::createDeath()`
- **Beschrijving:** Maakt een nieuw overlijden aan

## Routes Activeren

### Optie 1: Via Open Register App (aanbevolen)

Als je toegang hebt tot de Open Register app directory, voeg routes toe aan:
```
/var/www/html/custom_apps/openregister/appinfo/routes.php
```

Of als de app in een andere locatie staat:
```
{nextcloud_root}/custom_apps/openregister/appinfo/routes.php
```

### Optie 2: Via routes_broken.php

Het bestand `routes_broken.php` bevat alle routes. Dit bestand moet mogelijk worden gekopieerd naar de juiste locatie of geïmporteerd.

**Let op:** Het bestand heet `routes_broken.php`, wat suggereert dat het mogelijk niet actief is. Controleer of er een actief `routes.php` bestand is.

### Optie 3: Handmatig Toevoegen

Voeg de routes handmatig toe aan het actieve routes bestand in de Open Register app:

```php
return [
    'routes' => [
        // ... bestaande routes ...
        
        // vrijBRP Dossiers API - Mutatie endpoints
        ['name' => 'VrijBrpDossiers#createRelocation', 'url' => '/api/v1/relocations/intra', 'verb' => 'POST'],
        ['name' => 'VrijBrpDossiers#createBirth', 'url' => '/api/v1/birth', 'verb' => 'POST'],
        ['name' => 'VrijBrpDossiers#createCommitment', 'url' => '/api/v1/commitment', 'verb' => 'POST'],
        ['name' => 'VrijBrpDossiers#createDeath', 'url' => '/api/v1/deaths/in-municipality', 'verb' => 'POST'],
    ]
];
```

## Routes Verificeren

Na het toevoegen van routes, herstart Nextcloud om routes te laden:

```bash
docker restart nextcloud
```

Of als Nextcloud niet in Docker draait:
```bash
sudo systemctl restart php-fpm
# of
sudo service apache2 restart
```

## Testen

Test de endpoints met curl:

```bash
# Verhuizing
curl -X POST http://localhost:8080/apps/openregister/api/v1/relocations/intra \
  -H "Content-Type: application/json" \
  -d '{
    "declarant": {
      "bsn": "168149291"
    },
    "newAddress": {
      "street": "Teststraat",
      "houseNumber": "1",
      "postalCode": "1234AB",
      "city": "Amsterdam"
    }
  }'

# Geboorte
curl -X POST http://localhost:8080/apps/openregister/api/v1/birth \
  -H "Content-Type: application/json" \
  -d '{
    "child": {
      "firstName": "Test",
      "lastName": "Kind",
      "birthDate": "2024-01-01"
    },
    "mother": {
      "bsn": "168149291"
    }
  }'

# Partnerschap
curl -X POST http://localhost:8080/apps/openregister/api/v1/commitment \
  -H "Content-Type: application/json" \
  -d '{
    "partner1": {
      "bsn": "168149291"
    },
    "partner2": {
      "bsn": "987654321"
    },
    "commitmentDate": "2024-12-31"
  }'

# Overlijden
curl -X POST http://localhost:8080/apps/openregister/api/v1/deaths/in-municipality \
  -H "Content-Type: application/json" \
  -d '{
    "person": {
      "bsn": "168149291"
    },
    "deathDate": "2024-01-01"
  }'
```

## Controller Mapping

Nextcloud gebruikt de volgende naamconventie voor routes:
- `VrijBrpDossiers` → `VrijBrpDossiersController` class
- `#createRelocation` → `createRelocation()` methode

De controller moet zich bevinden in:
```
lib/Controller/VrijBrpDossiersController.php
```

Met namespace:
```php
namespace OCA\OpenRegister\Controller;
```

## Troubleshooting

### Routes werken niet

1. **Controleer of routes bestand correct is:**
   ```bash
   php -l routes.php
   ```

2. **Controleer of controller bestaat:**
   ```bash
   ls -la lib/Controller/VrijBrpDossiersController.php
   ```

3. **Controleer Nextcloud logs:**
   ```bash
   tail -f /var/www/html/data/nextcloud.log
   ```

4. **Controleer of app enabled is:**
   ```bash
   docker exec -u 33 nextcloud php /var/www/html/occ app:list | grep openregister
   ```

### 404 Not Found

- Controleer of de URL correct is: `/apps/openregister/api/v1/...`
- Controleer of Nextcloud is herstart na routes toevoegen
- Controleer of de app enabled is

### 500 Internal Server Error

- Controleer Nextcloud logs voor foutmeldingen
- Controleer of alle dependencies beschikbaar zijn
- Controleer database connectie

## Volgende Stappen

1. ✅ Routes toegevoegd aan `routes_broken.php`
2. ⚠️ Routes activeren in actieve routes bestand
3. ⚠️ Nextcloud herstarten
4. ⚠️ Endpoints testen
5. ⚠️ Open Register integratie implementeren (opslag van dossiers)

## Referenties

- [VrijBrpDossiersController.php](./lib/Controller/VrijBrpDossiersController.php)
- [routes_broken.php](./routes_broken.php) - Regel 297-300







