# Plan: Validaties Inbouwen in Open Register Architectuur

## Overzicht

Dit plan beschrijft hoe de validatieregels uit de vrijBRP Dossiers API kunnen worden geïntegreerd in de Open Register architectuur, conform de Common Ground 5-lagen model en de scheiding van verantwoordelijkheden.

**Architectuurprincipe:** Validatie hoort in **Laag 3 (vrijBRP Logica Service)**, niet in Laag 2 (Open Register). Open Register fungeert als coördinator en persisterende laag.

**Bron documenten:**
- [OPENREGISTER-BRP-ARCHITECTUUR.md](./OPENREGISTER-BRP-ARCHITECTUUR.md)
- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md)
- [VRJIBRP-MUTATIES-TECHNISCH.md](./VRJIBRP-MUTATIES-TECHNISCH.md)

---

## 1. Architectuur Positionering

### 1.1 Common Ground 5-Lagen Model

Volgens de architectuurvisie worden validaties gepositioneerd als volgt:

| Component | Laag | Verantwoordelijkheid |
|-----------|------|---------------------|
| **Open Register** | Laag 2: Componenten | Coördinatie, autorisatie check, persistente opslag, eventing |
| **vrijBRP Logica Service** | Laag 3: Diensten | **Validatie (syntactisch + semantisch), RVIG-regels, datatransformatie** |
| **ZGW-systeem** | Laag 4: Processen | Workflow orchestration, proceslogica |
| **UI/Interfaces** | Laag 5: Interactie | Gebruikersinteractie |

### 1.2 Scheiding van Verantwoordelijkheden

**Open Register (Laag 2):**
- ✅ Ontvangt mutatie requests
- ✅ Valideert autorisatie (JWT token, rechten)
- ✅ Coördineert mutatie flow
- ✅ Roept vrijBRP Logica Service aan voor validatie
- ✅ Slaat gevalideerde data op
- ✅ Genereert events
- ❌ **Geen** BRP-domeinlogica
- ❌ **Geen** RVIG-validaties
- ❌ **Geen** business rules

**vrijBRP Logica Service (Laag 3):**
- ✅ Syntactische validatie (JSON schema, formaten)
- ✅ Semantische validatie (BSN bestaat, obstructions, etc.)
- ✅ RVIG-regels implementatie
- ✅ Business rule validatie
- ✅ Datatransformatie (API formaat → Database formaat)
- ✅ Consistentiechecks
- ❌ **Geen** persistente opslag
- ❌ **Geen** eventing
- ❌ **Geen** autorisatie (dat doet Open Register)

---

## 2. Data Flow met Validatie

### 2.1 Mutatie Flow (conform architectuur)

```
[ZGW-systeem] (Laag 4)
    ↓
[POST /api/v1/relocations/intra]
    ↓
[Open Register] (Laag 2)
    ├─→ [Autorisatie Check] ← JWT token validatie
    ├─→ [Request Routing] ← Route naar juiste handler
    ↓
[vrijBRP Logica Service] (Laag 3) ← VALIDATIE HIER
    ├─→ [Syntactische Validatie] ← JSON schema, formaten
    ├─→ [Semantische Validatie] ← Database queries, business rules
    ├─→ [RVIG Validatie] ← Complexe BRP-regels
    ├─→ [Datatransformatie] ← API formaat → Database formaat
    ↓
[Validatie Resultaat]
    ├─→ [Success] → Getransformeerde data
    └─→ [Error] → Gestructureerde error response
    ↓
[Open Register] (Laag 2)
    ├─→ [Database Write] ← Atomair opslaan
    ├─→ [Versiebeheer] ← Historie bijwerken
    ├─→ [Eventing] ← Event genereren
    └─→ [Response] ← Dossier ID + status
```

### 2.2 Validatie Timing

**Stap 1: Open Register ontvangt request**
- Syntactische check: Is het geldig JSON?
- Autorisation check: Is JWT token geldig?
- Routing: Naar welke service moet dit?

**Stap 2: vrijBRP Logica Service valideert**
- Syntactische validatie (Laag 1)
- Semantische validatie (Laag 2)
- RVIG-validatie (Laag 3)
- Datatransformatie

**Stap 3: Open Register persisteert**
- Alleen als validatie succesvol is
- Atomair opslaan
- Event genereren

---

## 3. Implementatie Strategie

### 3.1 Fase 1: vrijBRP Logica Service Opzetten

**Doel:** Basisinfrastructuur voor validatie service

**Componenten:**
1. **Validatie Service Class**
   - `VrijBrpValidationService.php`
   - Methodes per validatie type
   - Error response builder

2. **Validatie Regel Classes**
   - `SyntacticValidator.php` - Syntactische validaties
   - `SemanticValidator.php` - Semantische validaties (met database queries)
   - `RvigValidator.php` - RVIG-specifieke validaties

3. **Database Service**
   - `BrpDatabaseService.php` - Database queries voor validatie
   - PostgreSQL connectie
   - Query builders

**Structuur:**
```
lib/
├── Service/
│   ├── Validation/
│   │   ├── VrijBrpValidationService.php
│   │   ├── SyntacticValidator.php
│   │   ├── SemanticValidator.php
│   │   └── RvigValidator.php
│   └── Database/
│       └── BrpDatabaseService.php
```

**Validatie Methodes:**
```php
class VrijBrpValidationService {
    public function validateRelocation(array $request): ValidationResult
    public function validateBirth(array $request): ValidationResult
    public function validateCommitment(array $request): ValidationResult
    public function validateDeath(array $request): ValidationResult
}
```

---

### 3.2 Fase 2: Syntactische Validaties Implementeren

**Doel:** Basis syntactische validaties (400 Bad Request)

**Implementatie:**
- JSON schema validatie
- Verplichte velden check
- Datatype validatie
- Formaat validatie (BSN, postcode, datum)

**Validatie Regels (uit VRJIBRP-ALLE-VALIDATIES.md):**

**BSN:**
- String, exact 9 cijfers, geen letters/speciale tekens

**Postcode:**
- String, formaat `1234AB` (4 cijfers + 2 letters)

**Datum:**
- String, ISO 8601 formaat (`YYYY-MM-DD`)

**Adres velden:**
- Straatnaam: string, niet leeg
- Huisnummer: string/number, positief
- Woonplaats: string, niet leeg

**Code Structuur:**
```php
class SyntacticValidator {
    public function validateBsn(string $bsn): ValidationError|null
    public function validatePostalCode(string $postcode): ValidationError|null
    public function validateDate(string $date): ValidationError|null
    public function validateRequiredFields(array $data, array $required): array
}
```

---

### 3.3 Fase 3: Semantische Validaties Implementeren

**Doel:** Database-gebaseerde validaties (422 Unprocessable Entity)

**Implementatie:**
- Database queries voor BSN-checks
- Database queries voor obstructions
- Database queries voor adresvalidatie
- Relatievalidatie

**Validatie Regels:**

**BSN Semantisch:**
- BSN bestaat in BRP (`probev.inw_ax`)
- BSN is niet geblokkeerd
- Persoon is niet overleden
- Persoon record is niet geschorst

**Relocator Semantisch:**
- Relocator bestaat in BRP
- Relocator is geschikt (`suitableForRelocation: true`)
- Geen obstructions:
  - `EXISTING_RELOCATION_CASE`
  - `DIFFERENT_ADDRESS`
  - `PERSON_IS_DECEASED`
  - `PERSON_RECORD_IS_BLOCKED`

**Adres Semantisch:**
- Postcode bestaat in adresregister
- Straat bestaat
- Woonplaats bestaat
- Combinatie is geldig

**Code Structuur:**
```php
class SemanticValidator {
    private BrpDatabaseService $db;
    
    public function validateBsnExists(string $bsn): ValidationError|null
    public function validateBsnNotBlocked(string $bsn): ValidationError|null
    public function validateRelocatorSuitable(string $bsn, string $declarantBsn): ValidationError|null
    public function validateAddressExists(array $address): ValidationError|null
    public function checkObstructions(string $bsn): array
}
```

**Database Queries:**
```php
class BrpDatabaseService {
    public function findPersonByBsn(string $bsn): ?Person
    public function findActiveRelocations(string $bsn): array
    public function findRelatives(string $bsn): array
    public function validateAddress(array $address): bool
}
```

---

### 3.4 Fase 4: Mutatie Endpoints in Open Register

**Doel:** Mutatie-endpoints die vrijBRP Logica Service aanroepen

**Endpoints:**
- `POST /api/v1/relocations/intra` - Verhuizing aanmaken
- `POST /api/v1/birth` - Geboorte aanmaken
- `POST /api/v1/commitment` - Partnerschap aanmaken
- `POST /api/v1/deaths/in-municipality` - Overlijden aanmaken

**Controller Structuur:**
```php
class VrijBrpDossiersController extends Controller {
    private VrijBrpValidationService $validationService;
    private ObjectService $objectService;
    
    public function createRelocation(array $request): JSONResponse {
        // 1. Autorisation check (Open Register)
        $this->checkAuthorization();
        
        // 2. Valideer via vrijBRP Logica Service
        $validationResult = $this->validationService->validateRelocation($request);
        if (!$validationResult->isValid()) {
            return $this->errorResponse($validationResult->getErrors(), 422);
        }
        
        // 3. Transformeer data (via validation service)
        $transformedData = $validationResult->getTransformedData();
        
        // 4. Sla op in Open Register
        $dossier = $this->objectService->create('dossiers', $transformedData);
        
        // 5. Genereer event
        $this->eventService->publish('dossier.created', $dossier);
        
        // 6. Return response
        return new JSONResponse([
            'dossierId' => $dossier->getUuid(),
            'status' => 'incomplete',
            'dossierType' => 'intra_mun_relocation'
        ], 201);
    }
}
```

---

### 3.5 Fase 5: Error Response Structuur

**Doel:** Gestandaardiseerde error responses conform vrijBRP Dossiers API

**Error Response Builder:**
```php
class ErrorResponseBuilder {
    public function buildSyntacticError(array $errors): JSONResponse
    public function buildSemanticError(array $errors): JSONResponse
    public function buildAuthorizationError(): JSONResponse
}
```

**Error Structuur:**
```php
// Syntactische fout (400)
{
    "status": 400,
    "title": "Bad Request",
    "detail": "Validation failed",
    "errors": [
        {
            "field": "declarant.bsn",
            "message": "BSN must be 9 digits"
        }
    ]
}

// Semantische fout (422)
{
    "status": 422,
    "title": "Unprocessable Entity",
    "detail": "Business rule violation",
    "errors": [
        {
            "field": "relocators[0]",
            "message": "Person is not suitable for relocation",
            "obstructions": ["EXISTING_RELOCATION_CASE"]
        }
    ]
}
```

---

## 4. Validatie Regels Mapping

### 4.1 Syntactische Validaties → Implementatie

| Validatie Regel | Implementatie Locatie | Methode |
|----------------|----------------------|---------|
| JSON structuur | `SyntacticValidator::validateJson()` | JSON decode check |
| BSN formaat | `SyntacticValidator::validateBsn()` | Regex: `^\\d{9}$` |
| Postcode formaat | `SyntacticValidator::validatePostalCode()` | Regex: `^\\d{4}[A-Z]{2}$` |
| Datum formaat | `SyntacticValidator::validateDate()` | ISO 8601 check |
| Verplichte velden | `SyntacticValidator::validateRequired()` | Array diff check |

### 4.2 Semantische Validaties → Implementatie

| Validatie Regel | Implementatie Locatie | Database Query |
|----------------|----------------------|----------------|
| BSN bestaat | `SemanticValidator::validateBsnExists()` | `SELECT * FROM probev.inw_ax WHERE bsn = :bsn` |
| BSN niet geblokkeerd | `SemanticValidator::validateBsnNotBlocked()` | Check `status = 'BLOCKED'` |
| Relocator geschikt | `SemanticValidator::validateRelocatorSuitable()` | `GET /api/v1/relatives/{bsn}` |
| Obstructions check | `SemanticValidator::checkObstructions()` | Multiple queries |
| Adres bestaat | `SemanticValidator::validateAddressExists()` | Adresregister queries |

### 4.3 RVIG Validaties → Implementatie

| Validatie Regel | Implementatie Locatie | Complexiteit |
|----------------|----------------------|--------------|
| RVIG-regels | `RvigValidator::validateRvigRules()` | Hoog (specifieke BRP-logica) |
| Historie-afhandeling | `RvigValidator::validateHistory()` | Hoog |
| Consistentiechecks | `RvigValidator::validateConsistency()` | Medium |

---

## 5. Technische Implementatie Details

### 5.1 Validatie Service Interface

```php
interface ValidationServiceInterface {
    public function validate(array $request, string $mutationType): ValidationResult;
}

class ValidationResult {
    private bool $isValid;
    private array $errors;
    private ?array $transformedData;
    
    public function isValid(): bool
    public function getErrors(): array
    public function getTransformedData(): ?array
}
```

### 5.2 Database Service Interface

```php
interface BrpDatabaseServiceInterface {
    public function findPersonByBsn(string $bsn): ?Person;
    public function findActiveRelocations(string $bsn): array;
    public function findRelatives(string $bsn): array;
    public function validateAddress(array $address): bool;
    public function checkObstructions(string $bsn): array;
}
```

### 5.3 Error Structuur

```php
class ValidationError {
    private string $field;
    private string $message;
    private ?string $code;
    private ?array $obstructions;
    
    public function toArray(): array {
        return [
            'field' => $this->field,
            'message' => $this->message,
            'code' => $this->code,
            'obstructions' => $this->obstructions
        ];
    }
}
```

---

## 6. Integratie met Open Register

### 6.1 Open Register als Coördinator

**Verantwoordelijkheden:**
1. **Request Ontvangst**
   - Ontvangt mutatie requests
   - Parse JSON
   - Route naar juiste handler

2. **Autorisatie**
   - Valideer JWT token
   - Check client rechten
   - Check bevoegdheid voor dossier type

3. **Coördinatie**
   - Roep vrijBRP Logica Service aan
   - Wacht op validatie resultaat
   - Handle success/error

4. **Persistentie**
   - Sla gevalideerde data op
   - Beheer versiehistorie
   - Genereer events

### 6.2 Open Register Controller Structuur

```php
class VrijBrpDossiersController extends Controller {
    public function __construct(
        private VrijBrpValidationService $validationService,
        private ObjectService $objectService,
        private EventService $eventService,
        private AuthorizationService $authService
    ) {}
    
    public function createRelocation(array $request): JSONResponse {
        // 1. Autorisation (Open Register)
        $this->authService->checkPermission('CREATE_RELOCATION');
        
        // 2. Validatie (vrijBRP Logica Service)
        $result = $this->validationService->validateRelocation($request);
        if (!$result->isValid()) {
            return $this->buildErrorResponse($result->getErrors(), 422);
        }
        
        // 3. Opslag (Open Register)
        $dossier = $this->objectService->create('relocations', $result->getTransformedData());
        
        // 4. Eventing (Open Register)
        $this->eventService->publish('relocation.created', $dossier);
        
        // 5. Response
        return new JSONResponse([
            'dossierId' => $dossier->getUuid(),
            'status' => 'incomplete'
        ], 201);
    }
}
```

---

## 7. Implementatie Fases

### Fase 1: Basis Infrastructuur (Week 1-2)

**Doel:** Opzetten van validatie service structuur

**Taken:**
1. ✅ Maak `VrijBrpValidationService` class
2. ✅ Maak `SyntacticValidator` class
3. ✅ Maak `SemanticValidator` class
4. ✅ Maak `BrpDatabaseService` class
5. ✅ Setup database connectie naar `probev` schema
6. ✅ Implementeer basis error response structuur

**Deliverables:**
- Validatie service classes
- Database service met PostgreSQL connectie
- Error response builder

---

### Fase 2: Syntactische Validaties (Week 3-4)

**Doel:** Implementeren van alle syntactische validaties

**Taken:**
1. ✅ BSN validatie (9 cijfers, string)
2. ✅ Postcode validatie (1234AB formaat)
3. ✅ Datum validatie (ISO 8601)
4. ✅ Adres velden validatie
5. ✅ Verplichte velden check
6. ✅ JSON schema validatie

**Deliverables:**
- Volledige syntactische validatie implementatie
- Unit tests voor elke validatie regel
- Error responses conform vrijBRP Dossiers API

---

### Fase 3: Semantische Validaties - BSN (Week 5-6)

**Doel:** Implementeren van BSN semantische validaties

**Taken:**
1. ✅ BSN bestaat in BRP (database query)
2. ✅ BSN niet geblokkeerd check
3. ✅ Persoon niet overleden check
4. ✅ Persoon record niet geschorst check
5. ✅ Obstructions detection

**Deliverables:**
- BSN semantische validatie
- Database queries voor BSN-checks
- Obstructions detection

---

### Fase 4: Semantische Validaties - Relocators (Week 7-8)

**Doel:** Implementeren van relocator validaties

**Taken:**
1. ✅ Relocator bestaat check
2. ✅ Relocator geschikt voor verhuizing check
3. ✅ Obstructions check (lopende verhuizingen, adres verschil)
4. ✅ Relatie type validatie
5. ✅ Integratie met `/api/v1/relatives/{bsn}` endpoint

**Deliverables:**
- Relocator validatie implementatie
- Obstructions detection voor relocators
- Integratie met relaties endpoint

---

### Fase 5: Semantische Validaties - Adressen (Week 9-10)

**Doel:** Implementeren van adresvalidaties

**Taken:**
1. ✅ Postcode bestaat check
2. ✅ Straat bestaat check
3. ✅ Woonplaats bestaat check
4. ✅ Combinatie validatie
5. ✅ Adres binnen gemeente check (voor intra-relocation)

**Deliverables:**
- Adresvalidatie implementatie
- Database queries voor adresregister
- Combinatie validatie

---

### Fase 6: Mutatie Endpoints (Week 11-12)

**Doel:** Implementeren van mutatie-endpoints in Open Register

**Taken:**
1. ✅ `POST /api/v1/relocations/intra` endpoint
2. ✅ `POST /api/v1/birth` endpoint
3. ✅ `POST /api/v1/commitment` endpoint
4. ✅ `POST /api/v1/deaths/in-municipality` endpoint
5. ✅ Integratie met validatie service
6. ✅ Error handling
7. ✅ Eventing

**Deliverables:**
- Mutatie-endpoints geïmplementeerd
- Integratie met validatie service
- Eventing bij mutaties

---

### Fase 7: RVIG Validaties (Week 13-16)

**Doel:** Implementeren van complexe RVIG-validaties

**Taken:**
1. ✅ RVIG-regels inventariseren
2. ✅ RVIG-validator implementeren
3. ✅ Historie-afhandeling validatie
4. ✅ Consistentiechecks
5. ✅ Datatransformatie (API → Database formaat)

**Deliverables:**
- RVIG-validator implementatie
- Complexe business rules
- Datatransformatie logica

---

### Fase 8: Testing & Documentatie (Week 17-18)

**Doel:** Testen en documenteren van implementatie

**Taken:**
1. ✅ Unit tests voor alle validaties
2. ✅ Integratie tests voor mutatie flow
3. ✅ Error scenario tests
4. ✅ Performance tests
5. ✅ API documentatie
6. ✅ Validatie documentatie

**Deliverables:**
- Test suite
- API documentatie
- Validatie documentatie

---

## 8. Bestandsstructuur

### 8.1 Nieuwe Bestanden

```
lib/
├── Controller/
│   └── VrijBrpDossiersController.php          # Mutatie endpoints
├── Service/
│   ├── Validation/
│   │   ├── VrijBrpValidationService.php      # Hoofd validatie service
│   │   ├── SyntacticValidator.php             # Syntactische validaties
│   │   ├── SemanticValidator.php               # Semantische validaties
│   │   ├── RvigValidator.php                  # RVIG validaties
│   │   ├── ValidationResult.php               # Validatie resultaat object
│   │   └── ValidationError.php                # Error object
│   ├── Database/
│   │   └── BrpDatabaseService.php             # Database queries
│   └── Authorization/
│       └── VrijBrpAuthorizationService.php    # Autorisation checks
└── Exception/
    └── ValidationException.php                # Custom exceptions
```

### 8.2 Configuratie Bestanden

```
config/
└── validation-rules.php                        # Validatie regels configuratie
```

---

## 9. Database Queries

### 9.1 BSN Validatie Queries

```sql
-- Check of BSN bestaat
SELECT 
    pl_id,
    bsn,
    status,
    overlijdensdatum,
    geschorst
FROM probev.inw_ax
WHERE bsn = :bsn
AND ax = 'A'
AND hist = 'A';

-- Check obstructions
SELECT 
    CASE 
        WHEN status = 'BLOCKED' THEN 'PERSON_RECORD_IS_BLOCKED'
        WHEN overlijdensdatum IS NOT NULL THEN 'PERSON_IS_DECEASED'
        WHEN geschorst = true THEN 'PERSONLIST_SUSPENDED'
    END as obstruction
FROM probev.inw_ax
WHERE bsn = :bsn
AND ax = 'A'
AND hist = 'A';
```

### 9.2 Relocator Validatie Queries

```sql
-- Check lopende verhuizingen
SELECT dossier_id, status
FROM dossiers
WHERE bsn = :relocator_bsn 
AND dossier_type = 'intra_mun_relocation'
AND status IN ('incomplete', 'processing');

-- Check adres verschil
SELECT 
    CASE 
        WHEN a1.adres != a2.adres THEN 'DIFFERENT_ADDRESS'
    END as obstruction
FROM adressen a1
JOIN adressen a2 ON a2.bsn = :declarant_bsn
WHERE a1.bsn = :relocator_bsn
AND a1.actueel = true
AND a2.actueel = true;
```

### 9.3 Adres Validatie Queries

```sql
-- Check postcode (conceptueel - afhankelijk van adresregister structuur)
SELECT * FROM adresregister.postcodes 
WHERE postcode = :postcode;

-- Check combinatie
SELECT * FROM adresregister.adressen 
WHERE straatnaam = :straatnaam
AND huisnummer = :huisnummer
AND postcode = :postcode
AND woonplaats = :woonplaats
AND actueel = true;
```

---

## 10. Error Handling

### 10.1 Error Response Structuur

**Syntactische Fout (400):**
```php
return new JSONResponse([
    'status' => 400,
    'title' => 'Bad Request',
    'detail' => 'Validation failed',
    'errors' => [
        [
            'field' => 'declarant.bsn',
            'message' => 'BSN must be 9 digits'
        ]
    ]
], 400);
```

**Semantische Fout (422):**
```php
return new JSONResponse([
    'status' => 422,
    'title' => 'Unprocessable Entity',
    'detail' => 'Business rule violation',
    'errors' => [
        [
            'field' => 'relocators[0]',
            'message' => 'Person is not suitable for relocation',
            'obstructions' => ['EXISTING_RELOCATION_CASE']
        ]
    ]
], 422);
```

**Autorisation Fout (403):**
```php
return new JSONResponse([
    'status' => 403,
    'title' => 'Forbidden',
    'detail' => 'Insufficient permissions'
], 403);
```

---

## 11. Integratie Punten

### 11.1 Open Register Integratie

**ObjectService:**
- Gebruik bestaande `ObjectService` voor opslag
- Maak gebruik van Open Register schema's
- Gebruik versiebeheer functionaliteit

**EventService:**
- Gebruik Open Register eventing voor notificaties
- Publiceer events bij mutaties
- Event types: `dossier.created`, `dossier.updated`, etc.

**AuthorizationService:**
- Implementeer JWT token validatie
- Check client rechten
- Check bevoegdheid voor dossier types

### 11.2 Database Integratie

**PostgreSQL Connectie:**
- Gebruik bestaande `probev` schema connectie
- Hergebruik database configuratie
- Gebruik PDO voor queries

**Query Performance:**
- Gebruik prepared statements
- Cache waar mogelijk
- Optimaliseer queries

---

## 12. Testing Strategie

### 12.1 Unit Tests

**Validatie Tests:**
- Test elke validatie regel apart
- Test error scenarios
- Test edge cases

**Voorbeeld:**
```php
class SyntacticValidatorTest {
    public function testBsnValidation() {
        $validator = new SyntacticValidator();
        
        // Test valid BSN
        $this->assertNull($validator->validateBsn('123456789'));
        
        // Test invalid BSN (te kort)
        $error = $validator->validateBsn('12345');
        $this->assertNotNull($error);
        $this->assertEquals('BSN must be 9 digits', $error->getMessage());
    }
}
```

### 12.2 Integratie Tests

**Mutatie Flow Tests:**
- Test volledige mutatie flow
- Test validatie integratie
- Test error handling
- Test eventing

**Voorbeeld:**
```php
class RelocationIntegrationTest {
    public function testCreateRelocationSuccess() {
        // Test succesvolle verhuizing
        $request = [...];
        $response = $this->post('/api/v1/relocations/intra', $request);
        
        $this->assertEquals(201, $response->getStatus());
        $this->assertNotNull($response->getData()['dossierId']);
    }
    
    public function testCreateRelocationValidationError() {
        // Test validatie fout
        $request = ['declarant' => ['bsn' => '123']]; // Invalid BSN
        $response = $this->post('/api/v1/relocations/intra', $request);
        
        $this->assertEquals(400, $response->getStatus());
        $this->assertArrayHasKey('errors', $response->getData());
    }
}
```

---

## 13. Risico's en Mitigaties

### 13.1 Technische Risico's

**Risico:** Database performance bij validatie queries
**Mitigatie:**
- Gebruik indexes op BSN, status velden
- Cache waar mogelijk
- Optimaliseer queries
- Overweeg read replicas

**Risico:** Complexiteit RVIG-validaties
**Mitigatie:**
- Start met basis validaties
- Implementeer RVIG-validaties gefaseerd
- Documenteer elke regel
- Test uitgebreid

**Risico:** Synchronisatie tussen Open Register en PostgreSQL
**Mitigatie:**
- Gebruik transacties
- Implementeer compensatie logica
- Monitor synchronisatie

### 13.2 Architectonische Risico's

**Risico:** Scheiding van verantwoordelijkheden niet duidelijk
**Mitigatie:**
- Duidelijke interface definities
- Documentatie per component
- Code reviews
- Architecture decision records

**Risico:** Afhankelijkheid van vrijBRP Logica Service
**Mitigatie:**
- Fallback mechanismen
- Graceful degradation
- Monitoring en alerting

---

## 14. Success Criteria

### 14.1 Functionele Criteria

- ✅ Alle syntactische validaties geïmplementeerd
- ✅ Alle semantische validaties geïmplementeerd
- ✅ RVIG-validaties geïmplementeerd
- ✅ Mutatie-endpoints werken correct
- ✅ Error responses conform vrijBRP Dossiers API
- ✅ Events worden gegenereerd bij mutaties

### 14.2 Niet-Functionele Criteria

- ✅ Validatie performance < 500ms (p95)
- ✅ Database queries geoptimaliseerd
- ✅ Code coverage > 80%
- ✅ API documentatie compleet
- ✅ Error handling robuust

---

## 15. Volgende Stappen

### 15.1 Directe Acties

1. **Review Plan** - Beoordeel dit plan met team
2. **Architectuur Goedkeuring** - Krijg goedkeuring voor architectuur
3. **Resource Planning** - Plan ontwikkelaars en tijd
4. **Start Fase 1** - Begin met basis infrastructuur

### 15.2 Afhankelijkheden

- ✅ PostgreSQL database toegang
- ✅ Open Register API documentatie
- ✅ vrijBRP database schema documentatie
- ✅ Validatie regels documentatie (VRJIBRP-ALLE-VALIDATIES.md)

---

## 16. Conclusie

Dit plan beschrijft een gefaseerde implementatie van validaties in de Open Register architectuur, waarbij de scheiding van verantwoordelijkheden wordt gerespecteerd:

- **Open Register (Laag 2):** Coördinatie, autorisatie, persistente opslag
- **vrijBRP Logica Service (Laag 3):** Validatie, RVIG-regels, datatransformatie

De implementatie volgt de Common Ground principes en zorgt voor een robuuste, onderhoudbare en schaalbare architectuur.

---

## Referenties

- [OPENREGISTER-BRP-ARCHITECTUUR.md](./OPENREGISTER-BRP-ARCHITECTUUR.md)
- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md)
- [VRJIBRP-MUTATIES-TECHNISCH.md](./VRJIBRP-MUTATIES-TECHNISCH.md)
- [VRJIBRP-DOSSIERS-API-VERGELIJKING.md](./VRJIBRP-DOSSIERS-API-VERGELIJKING.md)







