# Bevragen (Lezen) naar 100% - Actieplan

**Huidige Score:** 75%  
**Doel:** 100%  
**Gap:** 25%

---

## Executive Summary

Om Bevragen (Lezen) van 75% naar 100% te brengen, moeten de volgende onderdelen worden geïmplementeerd:

1. **Query Parameters & Filters** (10%) - Volledige ondersteuning voor `fields`, `expand`, geavanceerde filters
2. **OpenAPI Specificatie** (5%) - Volledige OpenAPI 3.0 specificatie genereren en beschikbaar maken
3. **Response Validatie** (5%) - Validatie tegen Haal Centraal OpenAPI specificatie
4. **Error Handling** (3%) - Volledige Haal Centraal-compliant error responses
5. **Performance & Caching** (2%) - Caching en query optimalisatie

**Geschatte tijd:** 2-3 weken  
**Prioriteit:** 🟡 Belangrijk (niet kritiek voor PoC, wel voor productie)

---

## 1. Query Parameters & Filters (10%)

### 1.1 Field Selection (`fields` parameter)

**Huidige Status:** ❌ Niet geïmplementeerd

**Vereist:**
- Ondersteuning voor `fields` parameter om specifieke velden te selecteren
- Bijvoorbeeld: `?fields=burgerservicenummer,naam,geboorte`
- Moet werken voor alle endpoints

**Implementatie:**

```php
// lib/Controller/HaalCentraalBrpController.php

/**
 * Parse fields parameter en filter response
 */
private function applyFieldSelection(array $data, ?string $fieldsParam): array {
    if (empty($fieldsParam)) {
        return $data;
    }
    
    $fields = array_map('trim', explode(',', $fieldsParam));
    $filtered = [];
    
    foreach ($fields as $field) {
        $value = $this->getNestedValue($data, $field);
        if ($value !== null) {
            $this->setNestedValue($filtered, $field, $value);
        }
    }
    
    return $filtered;
}

/**
 * Haal geneste waarde op (bijv. "naam.voornamen")
 */
private function getNestedValue(array $data, string $path) {
    $keys = explode('.', $path);
    $value = $data;
    
    foreach ($keys as $key) {
        if (is_array($value) && isset($value[$key])) {
            $value = $value[$key];
        } else {
            return null;
        }
    }
    
    return $value;
}
```

**Test Cases:**
- `?fields=burgerservicenummer` - Alleen BSN teruggeven
- `?fields=naam,geboorte` - Alleen naam en geboorte teruggeven
- `?fields=naam.voornamen,naam.geslachtsnaam` - Geneste velden

---

### 1.2 Expand Functionaliteit (`expand` parameter)

**Huidige Status:** ❌ Niet geïmplementeerd

**Vereist:**
- Ondersteuning voor `expand` parameter om relaties automatisch op te halen
- Bijvoorbeeld: `?expand=partners,kinderen,ouders`
- Moet werken voor alle endpoints

**Implementatie:**

```php
/**
 * Parse expand parameter en haal relaties op
 */
private function applyExpand(array $data, ?string $expandParam, string $bsn): array {
    if (empty($expandParam)) {
        return $data;
    }
    
    $expands = array_map('trim', explode(',', $expandParam));
    
    if (in_array('partners', $expands)) {
        $data['_embedded']['partners'] = $this->getPartners($bsn);
    }
    
    if (in_array('kinderen', $expands)) {
        $data['_embedded']['kinderen'] = $this->getKinderen($bsn);
    }
    
    if (in_array('ouders', $expands)) {
        $data['_embedded']['ouders'] = $this->getOuders($bsn);
    }
    
    if (in_array('verblijfplaats', $expands)) {
        $data['_embedded']['verblijfplaats'] = $this->getVerblijfplaats($bsn);
    }
    
    if (in_array('nationaliteiten', $expands)) {
        $data['_embedded']['nationaliteiten'] = $this->getNationaliteiten($bsn);
    }
    
    return $data;
}
```

**Test Cases:**
- `?expand=partners` - Partners automatisch meenemen
- `?expand=partners,kinderen` - Partners en kinderen automatisch meenemen
- `?expand=*` - Alle relaties automatisch meenemen

---

### 1.3 Geavanceerde Filters

**Huidige Status:** ⚠️ Gedeeltelijk (basis filters werken)

**Vereist:**
- Volledige ondersteuning voor alle Haal Centraal filter parameters
- Combinatie van meerdere filters
- Datum ranges (van/tot)
- Geavanceerde zoekopdrachten

**Huidige Filters:**
- ✅ `bsn` - BSN filter
- ✅ `achternaam` - Achternaam filter
- ✅ `geboortedatum` - Geboortedatum filter
- ⚠️ `geboortedatumVan` - Van datum (niet geïmplementeerd)
- ⚠️ `geboortedatumTot` - Tot datum (niet geïmplementeerd)
- ⚠️ `verblijfplaats.postcode` - Postcode filter (niet geïmplementeerd)
- ⚠️ `verblijfplaats.woonplaats` - Woonplaats filter (niet geïmplementeerd)

**Implementatie:**

```php
/**
 * Geavanceerde filters toepassen
 */
private function applyAdvancedFilters($qb, array $filters): void {
    // Geboortedatum range
    if (isset($filters['geboortedatumVan'])) {
        $qb->andWhere($qb->expr()->gte(
            $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboorte.datum.datum') . '))'),
            $qb->createNamedParameter($filters['geboortedatumVan'])
        ));
    }
    
    if (isset($filters['geboortedatumTot'])) {
        $qb->andWhere($qb->expr()->lte(
            $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboorte.datum.datum') . '))'),
            $qb->createNamedParameter($filters['geboortedatumTot'])
        ));
    }
    
    // Verblijfplaats filters
    if (isset($filters['verblijfplaats.postcode'])) {
        // Join met adressen register of gebruik relatie
        // TODO: Implementeer join met adressen
    }
    
    if (isset($filters['verblijfplaats.woonplaats'])) {
        // Join met adressen register of gebruik relatie
        // TODO: Implementeer join met adressen
    }
}
```

---

### 1.4 Sortering (`sort` parameter)

**Huidige Status:** ⚠️ Gedeeltelijk (alleen default sortering)

**Vereist:**
- Ondersteuning voor `sort` parameter
- Bijvoorbeeld: `?sort=naam.geslachtsnaam,geboorte.datum.datum`
- Ondersteuning voor ascending/descending (`+` of `-`)

**Implementatie:**

```php
/**
 * Parse sort parameter en pas sortering toe
 */
private function applySorting($qb, ?string $sortParam): void {
    if (empty($sortParam)) {
        $qb->orderBy('created', 'DESC');
        return;
    }
    
    $sorts = array_map('trim', explode(',', $sortParam));
    
    foreach ($sorts as $sort) {
        $direction = 'ASC';
        if (strpos($sort, '-') === 0) {
            $direction = 'DESC';
            $sort = substr($sort, 1);
        } elseif (strpos($sort, '+') === 0) {
            $sort = substr($sort, 1);
        }
        
        // Map Haal Centraal veldnamen naar database velden
        $dbField = $this->mapHaalCentraalFieldToDb($sort);
        if ($dbField) {
            $qb->addOrderBy($dbField, $direction);
        }
    }
}

/**
 * Map Haal Centraal veldnaam naar database veld
 */
private function mapHaalCentraalFieldToDb(string $field): ?string {
    $mapping = [
        'naam.geslachtsnaam' => "JSON_UNQUOTE(JSON_EXTRACT(object, '$.geslachtsnaam'))",
        'geboorte.datum.datum' => "JSON_UNQUOTE(JSON_EXTRACT(object, '$.geboorte.datum.datum'))",
        'burgerservicenummer' => "JSON_UNQUOTE(JSON_EXTRACT(object, '$.bsn'))",
    ];
    
    return $mapping[$field] ?? null;
}
```

---

## 2. OpenAPI Specificatie (5%)

### 2.1 OpenAPI 3.0 Specificatie Genereren

**Huidige Status:** ❌ Niet beschikbaar

**Vereist:**
- Volledige OpenAPI 3.0 specificatie genereren
- Alle endpoints documenteren
- Alle query parameters documenteren
- Alle response formats documenteren
- Alle error responses documenteren

**Implementatie:**

**Optie 1: Handmatig OpenAPI spec maken**

```yaml
# openapi.yaml
openapi: 3.0.0
info:
  title: Haal Centraal BRP Bevragen API
  version: 2.0.0
  description: API voor het bevragen van BRP gegevens volgens Haal Centraal specificatie

servers:
  - url: https://api.example.com
    description: Productie server
  - url: https://test-api.example.com
    description: Test server

paths:
  /ingeschrevenpersonen:
    get:
      summary: Lijst ingeschreven personen
      parameters:
        - name: fields
          in: query
          schema:
            type: string
          description: Selecteer specifieke velden (comma-separated)
        - name: expand
          in: query
          schema:
            type: string
          description: Haal relaties automatisch op (comma-separated)
        - name: sort
          in: query
          schema:
            type: string
          description: Sorteer resultaten (comma-separated, prefix met - voor descending)
        - name: bsn
          in: query
          schema:
            type: string
            pattern: '^[0-9]{9}$'
          description: Filter op BSN
        # ... andere parameters
      responses:
        '200':
          description: Succesvolle response
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/IngeschrevenPersonen'
        '400':
          $ref: '#/components/responses/BadRequest'
        '422':
          $ref: '#/components/responses/UnprocessableEntity'
```

**Optie 2: Automatisch genereren met annotations**

```php
/**
 * GET /ingeschrevenpersonen
 * 
 * @OA\Get(
 *     path="/ingeschrevenpersonen",
 *     summary="Lijst ingeschreven personen",
 *     tags={"IngeschrevenPersonen"},
 *     @OA\Parameter(
 *         name="fields",
 *         in="query",
 *         description="Selecteer specifieke velden",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="expand",
 *         in="query",
 *         description="Haal relaties automatisch op",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Succesvolle response",
 *         @OA\JsonContent(ref="#/components/schemas/IngeschrevenPersonen")
 *     )
 * )
 */
public function getIngeschrevenPersonen(): JSONResponse {
    // ...
}
```

**Tools:**
- Swagger PHP annotations
- OpenAPI Generator
- Swagger UI voor documentatie

---

### 2.2 Swagger UI Beschikbaar Maken

**Vereist:**
- Swagger UI endpoint beschikbaar maken
- Bijvoorbeeld: `/api/docs` of `/swagger-ui`
- Interactieve API documentatie

**Implementatie:**

```php
// lib/Controller/HaalCentraalDocsController.php

/**
 * GET /api/docs
 * Swagger UI pagina
 */
public function getDocs(): TemplateResponse {
    return new TemplateResponse('openregister', 'swagger-ui');
}
```

---

## 3. Response Validatie (5%)

### 3.1 Validatie tegen OpenAPI Specificatie

**Huidige Status:** ❌ Niet geïmplementeerd

**Vereist:**
- Validatie van responses tegen OpenAPI specificatie
- Zorgen dat alle vereiste velden aanwezig zijn
- Zorgen dat datatypes correct zijn
- Zorgen dat formats correct zijn (ISO 8601 voor datums)

**Implementatie:**

```php
// lib/Service/Validation/HaalCentraalResponseValidator.php

class HaalCentraalResponseValidator {
    private array $openApiSpec;
    
    public function __construct(string $openApiSpecPath) {
        $this->openApiSpec = json_decode(file_get_contents($openApiSpecPath), true);
    }
    
    /**
     * Valideer response tegen OpenAPI specificatie
     */
    public function validateResponse(array $data, string $endpoint, string $method = 'GET'): ValidationResult {
        $errors = [];
        
        // Haal schema op uit OpenAPI spec
        $schema = $this->getResponseSchema($endpoint, $method);
        
        if (!$schema) {
            return new ValidationResult(true); // Geen schema = geen validatie nodig
        }
        
        // Valideer vereiste velden
        $requiredFields = $schema['required'] ?? [];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $errors[] = new ValidationError(
                    $field,
                    "Required field '{$field}' is missing",
                    'REQUIRED_FIELD_MISSING'
                );
            }
        }
        
        // Valideer datatypes
        $properties = $schema['properties'] ?? [];
        foreach ($properties as $field => $property) {
            if (isset($data[$field])) {
                $typeError = $this->validateType($data[$field], $property);
                if ($typeError) {
                    $errors[] = $typeError;
                }
            }
        }
        
        return new ValidationResult(empty($errors), $errors);
    }
    
    private function validateType($value, array $property): ?ValidationError {
        $expectedType = $property['type'] ?? null;
        
        if ($expectedType === 'string' && !is_string($value)) {
            return new ValidationError('type', 'Expected string', 'INVALID_TYPE');
        }
        
        if ($expectedType === 'integer' && !is_int($value)) {
            return new ValidationError('type', 'Expected integer', 'INVALID_TYPE');
        }
        
        // Valideer format (bijv. ISO 8601 voor datums)
        if (isset($property['format'])) {
            if ($property['format'] === 'date' && !$this->isValidDate($value)) {
                return new ValidationError('format', 'Invalid date format (expected ISO 8601)', 'INVALID_FORMAT');
            }
        }
        
        return null;
    }
}
```

---

### 3.2 Test Suite tegen Haal Centraal Cucumber Tests

**Vereist:**
- Testen tegen officiële Haal Centraal Cucumber test suite
- Zorgen dat alle tests slagen
- Compliance rapport genereren

**Implementatie:**

```bash
# Setup Cucumber tests
git clone https://github.com/VNG-Realisatie/Haal-Centraal-BRP-bevragen
cd Haal-Centraal-BRP-bevragen
bundle install

# Run tests tegen onze API
API_URL=http://localhost:8080/apps/openregister bundle exec cucumber
```

---

## 4. Error Handling (3%)

### 4.1 Volledige Haal Centraal-Compliant Error Responses

**Huidige Status:** ⚠️ Gedeeltelijk (basis error handling werkt)

**Vereist:**
- Alle Haal Centraal error codes ondersteunen
- Gestructureerde error responses
- Correcte HTTP status codes
- Error logging en monitoring

**Haal Centraal Error Codes:**
- `400` - Bad Request (ongeldige parameters)
- `401` - Unauthorized (authenticatie vereist)
- `403` - Forbidden (geen rechten)
- `404` - Not Found (resource niet gevonden)
- `422` - Unprocessable Entity (validatie fout)
- `429` - Too Many Requests (rate limit)
- `500` - Internal Server Error

**Implementatie:**

```php
/**
 * Genereer Haal Centraal-compliant error response
 */
private function createErrorResponse(
    int $statusCode,
    string $title,
    string $detail,
    ?array $errors = null,
    ?string $instance = null
): JSONResponse {
    $response = [
        'status' => $statusCode,
        'title' => $title,
        'detail' => $detail,
    ];
    
    if ($instance) {
        $response['instance'] = $instance;
    }
    
    if ($errors) {
        $response['errors'] = $errors;
    }
    
    // Log error
    error_log(sprintf(
        "Haal Centraal API Error: %s - %s - %s",
        $statusCode,
        $title,
        $detail
    ));
    
    return new JSONResponse($response, $statusCode);
}
```

---

## 5. Performance & Caching (2%)

### 5.1 Caching Implementeren

**Huidige Status:** ❌ Niet geïmplementeerd

**Vereist:**
- Caching voor veelgebruikte queries
- Cache invalidation bij mutaties
- Cache headers in responses

**Implementatie:**

```php
// lib/Service/Cache/HaalCentraalCacheService.php

class HaalCentraalCacheService {
    private \OCP\ICache $cache;
    private int $ttl = 3600; // 1 uur
    
    /**
     * Haal data uit cache of database
     */
    public function get(string $key, callable $callback): array {
        $cached = $this->cache->get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }
        
        $data = $callback();
        $this->cache->set($key, json_encode($data), $this->ttl);
        
        return $data;
    }
    
    /**
     * Invalideer cache voor BSN
     */
    public function invalidateBsn(string $bsn): void {
        $keys = [
            "ingeschrevenpersoon:{$bsn}",
            "partners:{$bsn}",
            "kinderen:{$bsn}",
            "ouders:{$bsn}",
            "verblijfplaats:{$bsn}",
            "nationaliteiten:{$bsn}",
        ];
        
        foreach ($keys as $key) {
            $this->cache->remove($key);
        }
    }
}
```

---

### 5.2 Query Optimalisatie

**Vereist:**
- Database indexes optimaliseren
- Query performance verbeteren
- N+1 query problemen voorkomen

**Implementatie:**

```sql
-- Indexes toevoegen voor veelgebruikte queries
CREATE INDEX idx_objects_bsn ON oc_openregister_objects((JSON_EXTRACT(object, '$.bsn')));
CREATE INDEX idx_objects_geslachtsnaam ON oc_openregister_objects((JSON_EXTRACT(object, '$.geslachtsnaam')));
CREATE INDEX idx_objects_geboortedatum ON oc_openregister_objects((JSON_EXTRACT(object, '$.geboorte.datum.datum')));
```

---

## 6. Implementatie Tijdlijn

### Week 1: Query Parameters & Filters
- **Dag 1-2:** Field selection (`fields` parameter)
- **Dag 3-4:** Expand functionaliteit (`expand` parameter)
- **Dag 5:** Geavanceerde filters en sortering

### Week 2: OpenAPI & Validatie
- **Dag 1-2:** OpenAPI specificatie genereren
- **Dag 3:** Swagger UI beschikbaar maken
- **Dag 4-5:** Response validatie implementeren

### Week 3: Error Handling & Performance
- **Dag 1-2:** Error handling verbeteren
- **Dag 3-4:** Caching implementeren
- **Dag 5:** Query optimalisatie en testen

**Totaal:** 2-3 weken

---

## 7. Success Criteria

✅ **Functioneel:**
- Alle Haal Centraal query parameters ondersteund (`fields`, `expand`, `sort`, etc.)
- Volledige OpenAPI specificatie beschikbaar
- Swagger UI beschikbaar
- Response validatie werkt

✅ **Technisch:**
- Caching geïmplementeerd
- Query performance geoptimaliseerd
- Error handling volledig Haal Centraal-compliant

✅ **Compliance:**
- Test suite tegen Haal Centraal Cucumber tests slagen
- Volledige compliance met Haal Centraal specificatie
- Bevragen compliance: 75% → **100%**

---

## 8. Prioriteit

**Prioriteit:** 🟡 Belangrijk (niet kritiek voor PoC, wel voor productie)

**Impact:**
- Verbeterde API gebruiksvriendelijkheid
- Volledige compliance met Haal Centraal specificatie
- Betere performance en caching
- Volledige documentatie beschikbaar

**Risico's:**
- Laag risico (geen breaking changes)
- Backward compatible (bestaande endpoints blijven werken)
- Incrementele implementatie mogelijk

---

**Status:** ✅ Klaar voor implementatie  
**Volgende Actie:** Start met Week 1 (Query Parameters & Filters)







