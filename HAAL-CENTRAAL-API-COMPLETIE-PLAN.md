# Haal Centraal API Completering - Actieplan

## Huidige Status

### ✅ Wat Werkt (Basis Endpoints)

| Endpoint | Status | Details |
|----------|--------|---------|
| `GET /ingeschrevenpersonen` | ✅ Werkt | Lijst personen met paginering en filters |
| `GET /ingeschrevenpersonen/{bsn}` | ✅ Werkt | Specifieke persoon op BSN |
| `GET /ingeschrevenpersonen/{bsn}/partners` | ✅ Werkt | Partners via huw_ax tabel |
| `GET /ingeschrevenpersonen/{bsn}/kinderen` | ✅ Werkt | Via afst_ax tabel |
| `GET /ingeschrevenpersonen/{bsn}/ouders` | ✅ Werkt | Via mdr_ax en vdr_ax tabellen |
| `GET /ingeschrevenpersonen/{bsn}/verblijfplaats` | ✅ Werkt | Via v_vb_ax_haal_centraal view |
| `GET /ingeschrevenpersonen/{bsn}/nationaliteiten` | ✅ Werkt | Via nat_ax tabel |

### ⚠️ Wat Ontbreekt of Onvolledig

#### 1. Authenticatie & Autorisatie ❌

**Probleem:**
- Alleen Nextcloud authenticatie (gebruiker moet ingelogd zijn)
- Geen JWT/Bearer token authenticatie
- Geen API key systeem voor externe toegang
- Geen client credentials flow

**Impact:** 🔴 **Kritiek** - Externe systemen kunnen API niet gebruiken

**Vereist:**
- JWT/Bearer token authenticatie volgens Haal Centraal-specificatie
- API key systeem voor externe toegang
- Client credentials OAuth2 flow
- Rate limiting per API key

---

#### 2. Query Parameters & Filters ⚠️

**Probleem:**
- Basis filters werken (bsn, achternaam, geboortedatum)
- Niet alle Haal Centraal query parameters ondersteund
- Geen volledige field selection (fields parameter)
- Geen volledige expand functionaliteit

**Impact:** 🟡 **Belangrijk** - Beperkte zoekfunctionaliteit

**Vereist:**
- Volledige ondersteuning voor alle Haal Centraal query parameters
- Field selection (`fields` parameter)
- Expand functionaliteit (`expand` parameter)
- Geavanceerde filters en sortering

---

#### 3. Response Formaat Validatie ⚠️

**Probleem:**
- Data transformatie werkt maar niet volledig gevalideerd
- Mogelijk ontbrekende velden volgens Haal Centraal-specificatie
- Datum-formaten mogelijk niet altijd correct
- Geen validatie tegen OpenAPI-specificatie

**Impact:** 🟡 **Belangrijk** - Mogelijke incompatibiliteit met afnemers

**Vereist:**
- Volledige validatie tegen Haal Centraal OpenAPI-specificatie
- Test suite tegen Haal Centraal Cucumber-tests
- Alle vereiste velden aanwezig
- Correcte datum-formaten (ISO 8601)

---

#### 4. Error Handling & Status Codes ⚠️

**Probleem:**
- Basis error handling aanwezig
- Mogelijk niet alle Haal Centraal error codes
- Error responses mogelijk niet volledig Haal Centraal-compliant

**Impact:** 🟡 **Belangrijk** - Moeilijkere debugging voor afnemers

**Vereist:**
- Volledige error handling volgens Haal Centraal-specificatie
- Correcte HTTP status codes
- Gestructureerde error responses
- Error logging en monitoring

---

#### 5. Performance & Caching ⚠️

**Probleem:**
- Geen caching geïmplementeerd
- Directe PostgreSQL queries kunnen traag zijn
- Geen rate limiting
- Geen query optimization

**Impact:** 🟡 **Belangrijk** - Performance issues bij grote datasets

**Vereist:**
- Caching voor veelgebruikte queries
- Query optimization
- Rate limiting per API key
- Performance monitoring

---

#### 6. Documentatie & OpenAPI Spec ⚠️

**Probleem:**
- Geen OpenAPI-specificatie beschikbaar
- Geen API-documentatie voor afnemers
- Geen voorbeelden of testdata

**Impact:** 🟢 **Laag** - Moeilijker voor afnemers om te integreren

**Vereist:**
- OpenAPI-specificatie genereren
- API-documentatie beschikbaar maken
- Testdata en voorbeelden
- Swagger UI of vergelijkbaar

---

#### 7. Compliance & Validatie ❌

**Probleem:**
- Niet getest tegen Haal Centraal test suite
- Geen validatie tegen volledige specificatie
- Mogelijk niet volledig compliant

**Impact:** 🔴 **Kritiek** - Niet geschikt voor productie

**Vereist:**
- Testen tegen Haal Centraal Cucumber-tests
-validatie tegen volledige OpenAPI-specificatie
- Compliance-rapport
- Certificering indien nodig

---

## Actieplan per Prioriteit

### 🔴 Prioriteit Hoog (Kritiek voor PoC)

#### 1. Authenticatie Implementeren

**Doel:** JWT/Bearer token authenticatie voor externe toegang

**Acties:**
1. ✅ Implementeer JWT token generatie
2. ✅ Implementeer Bearer token validatie
3. ✅ Maak API key systeem
4. ✅ Implementeer client credentials OAuth2 flow
5. ✅ Voeg rate limiting toe per API key

**Geschatte tijd:** 2-3 weken

**Scripts/Code nodig:**
- JWT library voor JWT handling (bijv. firebase/php-jwt)
- API key management systeem
- OAuth2 server implementatie

---

#### 2. Compliance & Validatie

**Doel:** Volledige compliance met Haal Centraal-specificatie

**Acties:**
1. ✅ Download Haal Centraal OpenAPI-specificatie
2. ✅ Valideer alle endpoints tegen specificatie
3. ✅ Test tegen Haal Centraal Cucumber-tests
4. ✅ Fix alle gevonden issues
5. ✅ Genereer compliance-rapport

**Geschatte tijd:** 1-2 weken

**Tools nodig:**
- Haal Centraal Cucumber test suite
- OpenAPI validator
- Postman/Insomnia voor handmatige tests

---

### 🟡 Prioriteit Medium (Belangrijk voor volledige functionaliteit)

#### 3. Query Parameters & Filters Uitbreiden

**Doel:** Volledige ondersteuning voor alle Haal Centraal query parameters

**Acties:**
1. ✅ Implementeer `fields` parameter voor field selection
2. ✅ Implementeer `expand` parameter voor relaties
3. ✅ Voeg geavanceerde filters toe
4. ✅ Implementeer sortering (`_ ✅ Documenteer alle query parameters

**Geschatte tijd:** 1-2 weken

---

#### 4. Response Formaat Validatie

**Doel:** Volledige validatie van response-formaat

**Acties:**
1. ✅ Valideer alle response velden tegen OpenAPI spec
2. ✅ Fix datum-formaten (ISO 8601)
3. ✅ Zorg dat alle vereiste velden aanwezig zijn
4. ✅ Test met verschillende BSN's en edge cases

**Geschatte tijd:** 1 week

---

#### 5. Error Handling Verbeteren

**Doel:** Volledige error handling volgens Haal Centraal-specificatie

**Acties:**
1. ✅ Implementeer alle Haal Centraal error codes
2. ✅ Zorg voor correcte HTTP status codes
3. ✅ Maak gestructureerde error responses
4. ✅ Voeg error logging toe

**Geschatte tijd:** 1 week

---

### 🟢 Prioriteit Laag (Nice to have)

#### 6. Performance & Caching

**Doel:** Optimaliseren van performance

**Acties:**
1. ✅ Implementeer caching voor veelgebruikte queries
2. ✅ Optimaliseer database queries
3. ✅ Voeg query result caching toe
4. ✅ Monitor performance metrics

**Geschatte tijd:** 1-2 weken

---

#### 7. Documentatie & OpenAPI Spec

**Doel:** Volledige API-documentatie

**Acties:**
1. ✅ Genereer OpenAPI-specificatie
2. ✅ Maak API-documentatie beschikbaar
3. ✅ Voeg voorbeelden en testdata toe
4. ✅ Maak Swagger UI beschikbaar

**Geschatte tijd:** 1 week

---

## Gedetailleerde Implementatie Stappen

### Stap 1: Authenticatie (🔴 Hoog)

#### 1.1 JWT Library Installeren

```bash
composer require firebase/php-jwt
```

#### 1.2 API Key Management Systeem

**Database tabel aanmaken:**
```sql
CREATE TABLE oc_openregister_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    secret VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) UNIQUE NOT NULL,
    rate_limit INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 1.3 JWT Token Generatie

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

private function generateJwtToken(string $clientId): string {
    $payload = [
        'iss' => 'openregister', // Issuer
        'aud' => 'haal-centraal', // Audience
        'iat' => time(), // Issued at
        'exp' => time() + 3600, // Expiration (1 hour)
        'client_id' => $clientId
    ];
    
    return JWT::encode($payload, $this->getSecretKey(), 'HS256');
}
```

#### 1.4 Bearer Token Validatie

```php
private function validateBearerToken(): bool {
    $authHeader = $this->request->getHeader('Authorization');
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    
    try {
        $decoded = JWT::decode($token, new Key($this->getSecretKey(), 'HS256'));
        return true;
    } catch (\Exception $e) {
        return false;
    }
}
```

#### 1.5 Rate Limiting

```php
private function checkRateLimit(string $apiKey): bool {
    // Check rate limit per API key
    // Implementeer met Redis of database
    // Return true als onder limiet, false als over limiet
}
```

---

### Stap 2: Query Parameters Uitbreiden (🟡 Medium)

#### 2.1 Fields Parameter

```php
private function applyFieldSelection(array $data, ?string $fields): array {
    if (!$fields) {
        return $data;
    }
    
    $selectedFields = explode(',', $fields);
    $result = [];
    
    foreach ($selectedFields as $field) {
        $field = trim($field);
        if (isset($data[$field])) {
            $result[$field] = $data[$field];
        }
    }
    
    return $result;
}
```

#### 2.2 Expand Parameter

```php
private function applyExpand(array $data, ?string $expand): array {
    if (!$expand) {
        return $data;
    }
    
    $expandFields = explode(',', $expand);
    
    foreach ($expandFields as $field) {
        $field = trim($field);
        if ($field === 'partners' && isset($data['burgerservicenummer'])) {
            $data['_embedded']['partners'] = $this->getPartners($data['burgerservicenummer']);
        }
        // etc.
    }
    
    return $data;
}
```

---

### Stap 3: Response Validatie (🟡 Medium)

#### 3.1 OpenAPI Validator

```php
use League\OpenAPIValidation\PSR7\ValidatorBuilder;

private function validateResponse(array $response, string $endpoint): bool {
    $validator = (new ValidatorBuilder)
        ->fromYamlFile('path/to/haal-centraal-openapi.yaml')
        ->getResponseValidator();
    
    // Valideer response tegen OpenAPI spec
    // Return true als valid, false als invalid
}
```

---

## Test Plan

### Test 1: Authenticatie

```bash
# Test zonder token (moet 401 retourneren)
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"

# Test met Bearer token
curl -H "Authorization: Bearer {token}" \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
```

### Test 2: Query Parameters

```bash
# Test fields parameter
curl -H "Authorization: Bearer {token}" \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291?fields=burgerservicenummer,naam"

# Test expand parameter
curl -H "Authorization: Bearer {token}" \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291?expand=partners,kinderen"
```

### Test 3: Haal Centraal Cucumber Tests

```bash
# Run Haal Centraal test suite
cd haal-centraal-brp-bevragen
bundle exec cucumber
```

---

## Success Criteria

### Per Component

✅ **Authenticatie:**
- JWT/Bearer token authenticatie werkt
- API key systeem functioneel
- Rate limiting werkt
- OAuth2 client credentials flow werkt

✅ **Compliance:**
- Alle endpoints voldoen aan Haal Centraal-specificatie
- Test suite passeert 100%
- OpenAPI validatie slaagt

✅ **Query Parameters:**
- Alle Haal Centraal query parameters ondersteund
- Field selection werkt
- Expand functionaliteit werkt

✅ **Response Validatie:**
- Alle responses zijn Haal Centraal-compliant
- Alle vereiste velden aanwezig
- Datum-formaten correct

---

## Geschatte Tijdlijn

| Fase | Component | Tijd | Prioriteit |
|------|-----------|------|------------|
| 1 | Authenticatie | 2-3 weken | 🔴 Hoog |
| 2 | Compliance & Validatie | 1-2 weken | 🔴 Hoog |
| 3 | Query Parameters | 1-2 weken | 🟡 Medium |
| 4 | Response Validatie | 1 week | 🟡 Medium |
| 5 | Error Handling | 1 week | 🟡 Medium |
| 6 | Performance & Caching | 1-2 weken | 🟢 Laag |
| 7 | Documentatie | 1 week | 🟢 Laag |

**Totaal:** 8-12 weken (2-3 maanden)

---

## Conclusie

**Wat moet er nog gebeuren:**

1. **🔴 Authenticatie implementeren** (2-3 weken) - Kritiek voor externe toegang
2. **🔴 Compliance & Validatie** (1-2 weken) - Kritiek voor productie
3. **🟡 Query Parameters uitbreiden** (1-2 weken) - Belangrijk voor volledige functionaliteit
4. **🟡 Response Validatie** (1 week) - Belangrijk voor compatibiliteit
5. **🟡 Error Handling** (1 week) - Belangrijk voor debugging
6. **🟢 Performance & Caching** (1-2 weken) - Nice to have
7. **🟢 Documentatie** (1 week) - Nice to have

**Start met:** Authenticatie implementeren (kritiek voor externe toegang)

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Actieplan klaar voor uitvoering







