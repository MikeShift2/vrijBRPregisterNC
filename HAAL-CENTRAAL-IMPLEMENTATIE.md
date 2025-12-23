# Haal Centraal BRP Bevragen Implementatie

## ✅ Status

**Ja, je kunt een Haal Centraal BRP Bevragen service bouwen tegen je OpenRegister schema!**

Ik heb een basis implementatie gemaakt die je kunt uitbreiden.

## Wat is er gemaakt

### 1. Custom Controller

**Bestand:** `/var/www/html/custom_apps/openregister/lib/Controller/HaalCentraalBrpController.php`

Deze controller implementeert:
- `GET /ingeschrevenpersonen` - Lijst alle ingeschreven personen
- `GET /ingeschrevenpersonen/{burgerservicenummer}` - Specifieke persoon op BSN

### 2. Routes Toegevoegd

Routes zijn toegevoegd aan `appinfo/routes.php`:
- `/ingeschrevenpersonen` → `HaalCentraalBrp#getIngeschrevenPersonen`
- `/ingeschrevenpersonen/{burgerservicenummer}` → `HaalCentraalBrp#getIngeschrevenPersoon`

## Data Transformatie

De controller transformeert automatisch OpenRegister data naar Haal Centraal formaat:

| OpenRegister | Haal Centraal |
|--------------|---------------|
| `bsn` | `burgerservicenummer` |
| `voornamen` (string) | `naam.voornamen[]` (array) |
| `geslachtsnaam` | `naam.geslachtsnaam` |
| `voorvoegsel` | `naam.voorvoegsel` |
| `geboortedatum` (19820308) | `geboorte.datum.datum` (1982-03-08) |
| `geslacht` (V/M/O) | `geslachtsaanduiding` (vrouw/man/onbekend) |
| `anr` | `aNummer` |

## Testen

### 1. Herstart Nextcloud (om routes te laden)

```bash
docker restart nextcloud
```

### 2. Test de API

```bash
# Lijst personen
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen?_limit=5"

# Specifieke persoon op BSN
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
```

## Uitbreiden

### Extra Endpoints Toevoegen

Voeg meer endpoints toe volgens de [Haal Centraal specificatie](https://github.com/BRP-API/Haal-Centraal-BRP-bevragen):

1. **Partners**: `GET /ingeschrevenpersonen/{bsn}/partners`
2. **Kinderen**: `GET /ingeschrevenpersonen/{bsn}/kinderen`
3. **Ouders**: `GET /ingeschrevenpersonen/{bsn}/ouders`
4. **Verblijfplaats**: `GET /ingeschrevenpersonen/{bsn}/verblijfplaats`
5. **Nationaliteiten**: `GET /ingeschrevenpersonen/{bsn}/nationaliteiten`

### Voorbeeld: Partners Endpoint

```php
/**
 * GET /ingeschrevenpersonen/{burgerservicenummer}/partners
 */
public function getPartners(string $burgerservicenummer): JSONResponse {
    // 1. Haal persoon op
    // 2. Zoek partners via huw_ax tabel in PostgreSQL
    // 3. Transformeer naar Haal Centraal formaat
    // 4. Return JSON response
}
```

## Authenticatie

Haal Centraal gebruikt API keys. Voeg authenticatie toe:

```php
private function validateApiKey(): bool {
    $apiKey = $this->request->getHeader('X-Api-Key');
    // Valideer tegen database of configuratie
    return $this->isValidApiKey($apiKey);
}
```

## Volgende Stappen

1. **Test de basis endpoints** met de curl commando's hierboven
2. **Download Haal Centraal OpenAPI spec** van GitHub
3. **Implementeer extra endpoints** (partners, kinderen, ouders)
4. **Voeg authenticatie toe** (API keys)
5. **Test tegen Haal Centraal test suite** (Cucumber tests)

## Referenties

- [Haal Centraal BRP Bevragen GitHub](https://github.com/BRP-API/Haal-Centraal-BRP-bevragen)
- [Haal Centraal Documentatie](https://brp-api.github.io/Haal-Centraal-BRP-bevragen/)
- [OpenRegister API Guide](./OPENREGISTER-API-GUIDE.md)








