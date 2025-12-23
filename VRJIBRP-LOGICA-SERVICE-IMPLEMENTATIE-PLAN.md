# Implementatie Plan: vrijBRP Logica Service

## Overzicht

Dit plan beschrijft de concrete stappen om de vrijBRP Logica Service te implementeren in het Open Register project, conform de Common Ground architectuurvisie.

**Doel:** Implementeren van validatieservice die mutatie requests valideert volgens vrijBRP Dossiers API standaarden.

**Architectuur:** Validatie hoort in Laag 3 (vrijBRP Logica Service), Open Register (Laag 2) fungeert als coördinator.

---

## Fase 1: Basis Infrastructuur Opzetten

### Doel
Basis directory structuur en foundation classes aanmaken.

### Stappen

#### Stap 1.1: Directory Structuur Aanmaken

**Actie:**
```bash
cd /Users/mikederuiter/Nextcloud
mkdir -p lib/Service/Validation
mkdir -p lib/Service/Database
mkdir -p lib/Service/Authorization
```

**Resultaat:**
- `lib/Service/Validation/` directory bestaat
- `lib/Service/Database/` directory bestaat
- `lib/Service/Authorization/` directory bestaat

**Tijd:** 5 minuten

---

#### Stap 1.2: ValidationError Class Aanmaken

**Bestand:** `lib/Service/Validation/ValidationError.php`

**Functionaliteit:**
- Representeert een validatie fout
- Bevat: field, message, code, obstructions
- Methode: `toArray()` voor error response

**Code structuur:**
```php
namespace OCA\OpenRegister\Service\Validation;

class ValidationError {
    private string $field;
    private string $message;
    private ?string $code;
    private ?array $obstructions;
    
    // Constructor, getters, toArray()
}
```

**Tijd:** 30 minuten

**Deliverable:** ValidationError.php met volledige implementatie

---

#### Stap 1.3: ValidationResult Class Aanmaken

**Bestand:** `lib/Service/Validation/ValidationResult.php`

**Functionaliteit:**
- Bevat validatie resultaat: isValid, errors, transformedData
- Methodes: `isValid()`, `getErrors()`, `addError()`, `toErrorArray()`

**Code structuur:**
```php
namespace OCA\OpenRegister\Service\Validation;

class ValidationResult {
    private bool $isValid;
    private array $errors;
    private ?array $transformedData;
    
    // Constructor, getters, setters
}
```

**Tijd:** 30 minuten

**Deliverable:** ValidationResult.php met volledige implementatie

---

#### Stap 1.4: BrpDatabaseService Class Aanmaken

**Bestand:** `lib/Service/Database/BrpDatabaseService.php`

**Functionaliteit:**
- PostgreSQL connectie naar `probev` schema
- Database queries voor validatie
- Methodes: `findPersonByBsn()`, `checkObstructions()`, etc.

**Code structuur:**
```php
namespace OCA\OpenRegister\Service\Database;

class BrpDatabaseService {
    private ?PDO $pdo = null;
    
    private function getConnection(): PDO
    public function findPersonByBsn(string $bsn): ?array
    public function checkObstructions(string $bsn): array
    // ... meer methodes
}
```

**Database connectie:**
- Host: `host.docker.internal`
- Port: `5432`
- Database: `bevax`
- Schema: `probev`
- User: `postgres`
- Password: `postgres`

**Tijd:** 1 uur

**Deliverable:** BrpDatabaseService.php met database connectie en basis queries

**Test:** Database connectie testen met eenvoudige query

---

### Fase 1 Deliverables

- ✅ Directory structuur aangemaakt
- ✅ ValidationError.php
- ✅ ValidationResult.php
- ✅ BrpDatabaseService.php met werkende database connectie

**Totaal tijd:** ~2 uur

---

## Fase 2: Syntactische Validaties Implementeren

### Doel
Implementeren van alle syntactische validaties (400 Bad Request).

### Stappen

#### Stap 2.1: SyntacticValidator Class Aanmaken

**Bestand:** `lib/Service/Validation/SyntacticValidator.php`

**Functionaliteit:**
- Syntactische validaties zonder database queries
- JSON schema validatie
- Formaat validaties (BSN, postcode, datum)
- Verplichte velden check

**Methodes:**
```php
public function validateBsn(?string $bsn): ?ValidationError
public function validatePostalCode(?string $postcode): ?ValidationError
public function validateDate(?string $date, string $fieldName = 'date'): ?ValidationError
public function validateRequiredFields(array $data, array $requiredFields): array
public function validateJson($data): ?ValidationError
```

**Validatie regels:**

**BSN:**
- Moet string zijn
- Exact 9 cijfers
- Geen letters/speciale tekens

**Postcode:**
- Moet string zijn
- Formaat: `1234AB` (4 cijfers + 2 letters)
- Hoofdletters

**Datum:**
- Moet string zijn
- ISO 8601 formaat (`YYYY-MM-DD`)
- Geldige datum

**Tijd:** 2 uur

**Deliverable:** SyntacticValidator.php met alle syntactische validaties

**Test:** Unit tests voor elke validatie regel

---

### Fase 2 Deliverables

- ✅ SyntacticValidator.php
- ✅ Alle syntactische validaties geïmplementeerd
- ✅ Unit tests

**Totaal tijd:** ~2 uur

---

## Fase 3: Semantische Validaties - BSN Implementeren

### Doel
Implementeren van BSN semantische validaties met database queries.

### Stappen

#### Stap 3.1: SemanticValidator Class Aanmaken

**Bestand:** `lib/Service/Validation/SemanticValidator.php`

**Functionaliteit:**
- Semantische validaties met database queries
- BSN bestaat check
- BSN obstructions check
- Relocator geschiktheid check

**Methodes:**
```php
public function validateBsnExists(string $bsn, string $fieldName = 'bsn'): ?ValidationError
public function validateBsnNotBlocked(string $bsn, string $fieldName = 'bsn'): ?ValidationError
public function validateRelocatorSuitable(string $relocatorBsn, string $declarantBsn, string $fieldName = 'relocator'): ?ValidationError
```

**Database queries:**

**BSN bestaat:**
```sql
SELECT pl_id, bsn, status, overlijdensdatum, geschorst
FROM probev.inw_ax
WHERE bsn = :bsn
AND ax = 'A'
AND hist = 'A'
LIMIT 1
```

**Obstructions check:**
- Check status = 'BLOCKED' → `PERSON_RECORD_IS_BLOCKED`
- Check overlijdensdatum IS NOT NULL → `PERSON_IS_DECEASED`
- Check geschorst = true → `PERSONLIST_SUSPENDED`

**Tijd:** 2 uur

**Deliverable:** SemanticValidator.php met BSN validaties

**Test:** Integratie tests met echte database

---

### Fase 3 Deliverables

- ✅ SemanticValidator.php
- ✅ BSN bestaat validatie
- ✅ BSN obstructions check
- ✅ Database queries werkend

**Totaal tijd:** ~2 uur

---

## Fase 4: Hoofd Validatie Service Implementeren

### Doel
VrijBrpValidationService die alle validaties coördineert.

### Stappen

#### Stap 4.1: VrijBrpValidationService Class Aanmaken

**Bestand:** `lib/Service/Validation/VrijBrpValidationService.php`

**Functionaliteit:**
- Coördineert syntactische en semantische validaties
- Per mutatie type: validateRelocation(), validateBirth(), etc.
- Datatransformatie (API → Database formaat)

**Methodes:**
```php
public function validateRelocation(array $request): ValidationResult
public function validateBirth(array $request): ValidationResult
public function validateCommitment(array $request): ValidationResult
public function validateDeath(array $request): ValidationResult
```

**Validatie flow voor verhuizing:**
1. Syntactische validatie (JSON, verplichte velden, formaten)
2. Als OK → Semantische validatie (BSN bestaat, obstructions)
3. Als OK → Datatransformatie
4. Return ValidationResult

**Tijd:** 3 uur

**Deliverable:** VrijBrpValidationService.php met validateRelocation() geïmplementeerd

**Test:** End-to-end test van validatie flow

---

### Fase 4 Deliverables

- ✅ VrijBrpValidationService.php
- ✅ validateRelocation() volledig geïmplementeerd
- ✅ Validatie flow getest

**Totaal tijd:** ~3 uur

---

## Fase 5: Mutatie Endpoints Implementeren

### Doel
Mutatie endpoints in controller die validatie service gebruiken.

### Stappen

#### Stap 5.1: VrijBrpDossiersController Aanmaken

**Bestand:** `lib/Controller/VrijBrpDossiersController.php`

**Functionaliteit:**
- Mutatie endpoints voor dossiers
- Gebruikt VrijBrpValidationService
- Error handling conform vrijBRP Dossiers API

**Endpoints:**
```php
public function createRelocation(): JSONResponse  // POST /api/v1/relocations/intra
public function createBirth(): JSONResponse      // POST /api/v1/birth
public function createCommitment(): JSONResponse // POST /api/v1/commitment
```

**Request flow:**
1. Parse JSON request body
2. Roep validationService aan
3. Als validatie faalt → Return error response (400 of 422)
4. Als OK → Sla op in Open Register (TODO)
5. Return success response (201)

**Tijd:** 2 uur

**Deliverable:** VrijBrpDossiersController.php met createRelocation() endpoint

---

#### Stap 5.2: Routes Toevoegen

**Actie:** Routes toevoegen voor mutatie endpoints

**Locatie:** Routes moeten worden toegevoegd aan Open Register app routes

**Routes:**
```php
['name' => 'VrijBrpDossiers#createRelocation', 'url' => '/api/v1/relocations/intra', 'verb' => 'POST']
['name' => 'VrijBrpDossiers#createBirth', 'url' => '/api/v1/birth', 'verb' => 'POST']
['name' => 'VrijBrpDossiers#createCommitment', 'url' => '/api/v1/commitment', 'verb' => 'POST']
```

**Opmerking:** Routes moeten worden toegevoegd aan Open Register app `appinfo/routes.php` of via custom routing.

**Tijd:** 30 minuten

**Deliverable:** Routes toegevoegd en werkend

---

#### Stap 5.3: Dependency Injection Setup

**Actie:** Zorg dat services kunnen worden geïnjecteerd in controller

**Methode 1: Constructor Injection (aanbevolen)**
```php
public function __construct(
    $appName,
    IRequest $request,
    VrijBrpValidationService $validationService
) {
    parent::__construct($appName, $request);
    $this->validationService = $validationService;
}
```

**Methode 2: Manual Instantiation (fallback)**
```php
private function getValidationService(): VrijBrpValidationService {
    $dbService = new BrpDatabaseService();
    $syntacticValidator = new SyntacticValidator();
    $semanticValidator = new SemanticValidator($dbService);
    return new VrijBrpValidationService($syntacticValidator, $semanticValidator, $dbService);
}
```

**Tijd:** 30 minuten

**Deliverable:** Dependency injection werkend

---

### Fase 5 Deliverables

- ✅ VrijBrpDossiersController.php
- ✅ createRelocation() endpoint werkend
- ✅ Routes toegevoegd
- ✅ Dependency injection werkend

**Totaal tijd:** ~3 uur

---

## Fase 6: Testing & Validatie

### Doel
Testen van volledige validatie flow.

### Stappen

#### Stap 6.1: Unit Tests Schrijven

**Test bestanden:**
- `tests/Unit/Service/Validation/SyntacticValidatorTest.php`
- `tests/Unit/Service/Validation/SemanticValidatorTest.php`
- `tests/Unit/Service/Validation/VrijBrpValidationServiceTest.php`

**Test cases:**

**SyntacticValidator:**
- Test valid BSN → geen error
- Test invalid BSN (te kort) → error
- Test invalid BSN (letters) → error
- Test valid postcode → geen error
- Test invalid postcode → error
- Test valid datum → geen error
- Test invalid datum → error

**SemanticValidator:**
- Test BSN bestaat → geen error
- Test BSN bestaat niet → error
- Test BSN geblokkeerd → error met obstruction
- Test BSN overleden → error met obstruction

**Tijd:** 3 uur

**Deliverable:** Unit tests voor alle validaties

---

#### Stap 6.2: Integratie Tests

**Test:** Volledige mutatie flow

**Test scenario's:**

**Scenario 1: Succesvolle verhuizing**
```bash
curl -X POST http://localhost:8080/apps/openregister/api/v1/relocations/intra \
  -H "Content-Type: application/json" \
  -d '{
    "declarant": {"bsn": "168149291"},
    "newAddress": {
      "street": "Teststraat",
      "houseNumber": "1",
      "postalCode": "1234AB",
      "city": "Amsterdam"
    }
  }'
```

**Verwachte response:** 201 Created met dossierId

**Scenario 2: Syntactische fout (invalid BSN)**
```bash
curl -X POST http://localhost:8080/apps/openregister/api/v1/relocations/intra \
  -H "Content-Type: application/json" \
  -d '{
    "declarant": {"bsn": "123"},
    "newAddress": {...}
  }'
```

**Verwachte response:** 400 Bad Request met error details

**Scenario 3: Semantische fout (BSN bestaat niet)**
```bash
curl -X POST http://localhost:8080/apps/openregister/api/v1/relocations/intra \
  -H "Content-Type: application/json" \
  -d '{
    "declarant": {"bsn": "999999999"},
    "newAddress": {...}
  }'
```

**Verwachte response:** 422 Unprocessable Entity met error details

**Tijd:** 2 uur

**Deliverable:** Integratie tests werkend

---

### Fase 6 Deliverables

- ✅ Unit tests voor alle validaties
- ✅ Integratie tests voor mutatie flow
- ✅ Alle test scenario's werkend

**Totaal tijd:** ~5 uur

---

## Fase 7: Uitbreidingen (Optioneel)

### 7.1 Relocator Validaties

**Doel:** Volledige relocator validatie implementeren

**Taken:**
- Check lopende verhuizingen
- Check adres verschil
- Check relatie type
- Integratie met `/api/v1/relatives/{bsn}` endpoint

**Tijd:** 3 uur

---

### 7.2 Adres Validaties

**Doel:** Adresvalidatie implementeren

**Taken:**
- Postcode bestaat check
- Straat bestaat check
- Woonplaats bestaat check
- Combinatie validatie

**Tijd:** 2 uur

---

### 7.3 Overige Mutatie Types

**Doel:** Geboorte, partnerschap, overlijden endpoints

**Taken:**
- Implementeer validateBirth()
- Implementeer validateCommitment()
- Implementeer validateDeath()
- Voeg endpoints toe aan controller

**Tijd:** 6 uur

---

## Implementatie Tijdlijn

### Week 1: Basis Infrastructuur

**Dag 1-2:**
- Fase 1: Basis infrastructuur (2 uur)
- Fase 2: Syntactische validaties (2 uur)

**Deliverables:**
- Directory structuur
- ValidationError, ValidationResult
- BrpDatabaseService
- SyntacticValidator

---

### Week 2: Semantische Validaties

**Dag 3-4:**
- Fase 3: BSN semantische validaties (2 uur)
- Fase 4: Hoofd validatie service (3 uur)

**Deliverables:**
- SemanticValidator
- VrijBrpValidationService
- validateRelocation() werkend

---

### Week 3: Endpoints & Testing

**Dag 5-7:**
- Fase 5: Mutatie endpoints (3 uur)
- Fase 6: Testing (5 uur)

**Deliverables:**
- VrijBrpDossiersController
- Routes werkend
- Unit tests
- Integratie tests

---

## Success Criteria

### Functioneel

- ✅ Syntactische validaties werken (400 errors)
- ✅ Semantische validaties werken (422 errors)
- ✅ BSN validatie werkt met database queries
- ✅ Obstructions worden correct gedetecteerd
- ✅ Error responses conform vrijBRP Dossiers API
- ✅ Mutatie endpoint werkt end-to-end

### Technisch

- ✅ Code volgt Nextcloud coding standards
- ✅ Namespace correct: `OCA\OpenRegister\Service\Validation`
- ✅ Database connectie stabiel
- ✅ Error handling robuust
- ✅ Code is testbaar (unit tests mogelijk)

### Performance

- ✅ Validatie < 500ms (p95)
- ✅ Database queries geoptimaliseerd
- ✅ Geen memory leaks

---

## Risico's & Mitigaties

### Risico 1: Database Connectie Problemen

**Risico:** PostgreSQL niet bereikbaar of verkeerde credentials

**Mitigatie:**
- Test database connectie eerst
- Gebruik environment variables voor configuratie
- Implementeer connection pooling
- Fallback mechanismen

---

### Risico 2: Dependency Injection Complexiteit

**Risico:** Services kunnen niet worden geïnjecteerd

**Mitigatie:**
- Start met manual instantiation
- Migreer naar DI later
- Documenteer beide methodes

---

### Risico 3: Routes Niet Werkend

**Risico:** Routes niet geregistreerd in Open Register app

**Mitigatie:**
- Check Open Register app structuur
- Gebruik custom routing indien nodig
- Test routes direct na toevoegen

---

## Volgende Stappen

### Directe Acties

1. **Start Fase 1** - Maak directory structuur
2. **Implementeer ValidationError** - Basis error object
3. **Implementeer ValidationResult** - Basis resultaat object
4. **Test database connectie** - Zorg dat PostgreSQL bereikbaar is

### Afhankelijkheden

- ✅ PostgreSQL database moet draaien
- ✅ `probev` schema moet bestaan
- ✅ Database credentials bekend
- ✅ Open Register app geïnstalleerd

---

## Conclusie

Dit plan beschrijft een gefaseerde implementatie van de vrijBRP Logica Service in het Open Register project. De implementatie volgt de Common Ground architectuur waarbij validatie in Laag 3 (Services) wordt geplaatst, terwijl Open Register (Laag 2) fungeert als coördinator.

**Totale geschatte tijd:** ~17 uur (exclusief optionele uitbreidingen)

**Prioriteit:** Start met Fase 1-5 voor basis functionaliteit, Fase 6 voor kwaliteit, Fase 7 voor volledige functionaliteit.

---

## Referenties

- [OPENREGISTER-VALIDATIE-IMPLEMENTATIE-PLAN.md](./OPENREGISTER-VALIDATIE-IMPLEMENTATIE-PLAN.md)
- [VRJIBRP-LOGICA-SERVICE-IMPLEMENTATIE.md](./VRJIBRP-LOGICA-SERVICE-IMPLEMENTATIE.md)
- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md)







