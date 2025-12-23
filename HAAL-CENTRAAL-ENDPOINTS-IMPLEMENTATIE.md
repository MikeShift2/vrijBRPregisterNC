# Haal Centraal Endpoints Implementatie - Samenvatting

## ✅ Geïmplementeerde Endpoints

Alle ontbrekende Haal Centraal BRP Bevragen endpoints zijn nu geïmplementeerd:

### 1. Partners ✅
**Endpoint:** `GET /ingeschrevenpersonen/{burgerservicenummer}/partners`

**Implementatie:**
- Haalt partners op via `huw_ax` tabel in PostgreSQL
- Gebruikt `a1_ref`, `a2_ref`, `a3_ref` om partner te vinden
- Retourneert volledige persoongegevens in Haal Centraal-formaat

**Response formaat:**
```json
{
  "_embedded": {
    "partners": [
      {
        "burgerservicenummer": "...",
        "naam": {...},
        "geboorte": {...},
        ...
      }
    ]
  }
}
```

### 2. Kinderen ✅
**Endpoint:** `GET /ingeschrevenpersonen/{burgerservicenummer}/kinderen`

**Implementatie:**
- Haalt kinderen op via `afst_ax` tabel
- Gebruikt `pl_id` om kinderen te vinden waar de persoon ouder is
- Retourneert volledige persoongegevens in Haal Centraal-formaat

**Response formaat:**
```json
{
  "_embedded": {
    "kinderen": [...]
  }
}
```

### 3. Ouders ✅
**Endpoint:** `GET /ingeschrevenpersonen/{burgerservicenummer}/ouders`

**Implementatie:**
- Haalt ouders op via `mdr_ax` (ouder 1) en `vdr_ax` (ouder 2) tabellen
- Retourneert beide ouders als beschikbaar
- Retourneert volledige persoongegevens in Haal Centraal-formaat

**Response formaat:**
```json
{
  "_embedded": {
    "ouders": [...]
  }
}
```

### 4. Verblijfplaats ✅
**Endpoint:** `GET /ingeschrevenpersonen/{burgerservicenummer}/verblijfplaats`

**Implementatie:**
- Gebruikt bestaande `getAdresFromAdressenSchema()` methode
- Fallback naar adresgegevens uit persoon-object
- Retourneert alleen verblijfplaats-gegevens (geen volledige persoon)

**Response formaat:**
```json
{
  "straatnaam": "...",
  "huisnummer": 123,
  "huisnummertoevoeging": "...",
  "postcode": "...",
  "woonplaatsnaam": "..."
}
```

### 5. Nationaliteiten ✅
**Endpoint:** `GET /ingeschrevenpersonen/{burgerservicenummer}/nationaliteiten`

**Implementatie:**
- Haalt nationaliteiten op via `nat_ax` tabel
- Joined met `natio` tabel voor omschrijving
- Retourneert array van nationaliteiten met code en omschrijving

**Response formaat:**
```json
{
  "_embedded": {
    "nationaliteiten": [
      {
        "nationaliteit": {
          "code": "1",
          "omschrijving": "Nederlandse"
        }
      }
    ]
  }
}
```

## 🔧 Technische Details

### Database Queries

Alle endpoints gebruiken directe PostgreSQL queries via `shell_exec()` omdat:
- Open Register-objecten mogelijk niet alle relaties bevatten
- Directe database-queries zijn efficiënter voor relaties
- Probev-database heeft genormaliseerde relatie-structuur

### Data Flow

1. **BSN validatie** → Controleer 9-cijferig formaat
2. **Persoon ophalen** → Via `getPersonByBsnFromDatabase()` om `pl_id` te krijgen
3. **Relaties ophalen** → Directe PostgreSQL queries op probev-tabellen
4. **Transformeren** → Via `transformToHaalCentraal()` voor consistente output
5. **Response** → Haal Centraal-compliant JSON

### Routes

Alle routes zijn geregistreerd in `/appinfo/routes.php`:

```php
["name" => "HaalCentraalBrp#getPartners", "url" => "/ingeschrevenpersonen/{burgerservicenummer}/partners", "verb" => "GET"],
["name" => "HaalCentraalBrp#getKinderen", "url" => "/ingeschrevenpersonen/{burgerservicenummer}/kinderen", "verb" => "GET"],
["name" => "HaalCentraalBrp#getOuders", "url" => "/ingeschrevenpersonen/{burgerservicenummer}/ouders", "verb" => "GET"],
["name" => "HaalCentraalBrp#getVerblijfplaats", "url" => "/ingeschrevenpersonen/{burgerservicenummer}/verblijfplaats", "verb" => "GET"],
["name" => "HaalCentraalBrp#getNationaliteiten", "url" => "/ingeschrevenpersonen/{burgerservicenummer}/nationaliteiten", "verb" => "GET"],
```

## 🧪 Testen

### Test Commando's

```bash
# Partners
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners"

# Kinderen
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen"

# Ouders
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders"

# Verblijfplaats
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/verblijfplaats"

# Nationaliteiten
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten"
```

## 📝 Opmerkingen

1. **Performance**: Directe PostgreSQL queries kunnen traag zijn bij grote datasets. Overweeg caching voor productie.

2. **Error Handling**: Alle endpoints hebben try-catch blokken en retourneren gestructureerde error responses.

3. **BSN Validatie**: Alle endpoints valideren BSN-formaat (9 cijfers) voordat queries worden uitgevoerd.

4. **Lege Resultaten**: Endpoints retourneren lege arrays in plaats van 404 wanneer geen relaties gevonden worden (behalve verblijfplaats).

5. **pl_id Requirement**: Alle relatie-endpoints vereisen `pl_id` in persoon-object. Zorg dat de view `v_personen_compleet_haal_centraal` dit veld bevat.

## ✅ Status

Alle endpoints zijn geïmplementeerd en geregistreerd. Klaar voor testen!







