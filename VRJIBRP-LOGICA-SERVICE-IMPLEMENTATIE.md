# Implementatie: vrijBRP Logica Service in Open Register Project

## Overzicht

Dit document beschrijft hoe de vrijBRP Logica Service kan worden geïmplementeerd in het huidige Open Register project, conform de architectuurvisie.

**Project Locatie:** `/Users/mikederuiter/Nextcloud/`

**Namespace:** `OCA\OpenRegister\Service\Validation`

---

## 1. Project Structuur

### 1.1 Huidige Structuur

```
/Users/mikederuiter/Nextcloud/
├── lib/
│   └── Controller/
│       ├── HaalCentraalBrpController.php
│       ├── HaalCentraalTestPageController.php
│       └── PrefillTestPageController.php
```

### 1.2 Nieuwe Structuur (na implementatie)

```
/Users/mikederuiter/Nextcloud/
├── lib/
│   ├── Controller/
│       ├── HaalCentraalBrpController.php
│       ├── VrijBrpDossiersController.php          # NIEUW: Mutatie endpoints
│       └── ...
│   └── Service/
│       └── Validation/                            # NIEUW: Validatie services
│           ├── VrijBrpValidationService.php       # Hoofd service
│           ├── SyntacticValidator.php             # Syntactische validaties
│           ├── SemanticValidator.php              # Semantische validaties
│           ├── RvigValidator.php                  # RVIG validaties
│           ├── ValidationResult.php               # Resultaat object
│           └── ValidationError.php                # Error object
│       └── Database/                              # NIEUW: Database services
│           └── BrpDatabaseService.php             # PostgreSQL queries
│       └── Authorization/                         # NIEUW: Autorisation
│           └── VrijBrpAuthorizationService.php     # JWT & rechten
```

---

## 2. Implementatie Stappen

### Stap 1: Directory Structuur Aanmaken

**Actie:** Maak de directory structuur aan

```bash
cd /Users/mikederuiter/Nextcloud

# Maak Service directories
mkdir -p lib/Service/Validation
mkdir -p lib/Service/Database
mkdir -p lib/Service/Authorization
```

**Resultaat:**
- `lib/Service/Validation/` - Validatie services
- `lib/Service/Database/` - Database services
- `lib/Service/Authorization/` - Autorisation services

---

### Stap 2: Basis Classes Aanmaken

**Actie:** Maak de basis classes aan met juiste namespace

#### 2.1 ValidationError.php

**Locatie:** `lib/Service/Validation/ValidationError.php`

**Code:**
```php
<?php
/**
 * Validation Error Object
 * 
 * Representeert een validatie fout met veld, message, code en obstructions
 */

namespace OCA\OpenRegister\Service\Validation;

class ValidationError {
    private string $field;
    private string $message;
    private ?string $code;
    private ?array $obstructions;
    
    public function __construct(
        string $field,
        string $message,
        ?string $code = null,
        ?array $obstructions = null
    ) {
        $this->field = $field;
        $this->message = $message;
        $this->code = $code;
        $this->obstructions = $obstructions;
    }
    
    public function getField(): string {
        return $this->field;
    }
    
    public function getMessage(): string {
        return $this->message;
    }
    
    public function getCode(): ?string {
        return $this->code;
    }
    
    public function getObstructions(): ?array {
        return $this->obstructions;
    }
    
    public function toArray(): array {
        $result = [
            'field' => $this->field,
            'message' => $this->message
        ];
        
        if ($this->code !== null) {
            $result['code'] = $this->code;
        }
        
        if ($this->obstructions !== null && !empty($this->obstructions)) {
            $result['obstructions'] = $this->obstructions;
        }
        
        return $result;
    }
}
```

#### 2.2 ValidationResult.php

**Locatie:** `lib/Service/Validation/ValidationResult.php`

**Code:**
```php
<?php
/**
 * Validation Result Object
 * 
 * Bevat het resultaat van een validatie: isValid, errors, transformedData
 */

namespace OCA\OpenRegister\Service\Validation;

class ValidationResult {
    private bool $isValid;
    private array $errors;
    private ?array $transformedData;
    
    public function __construct(
        bool $isValid,
        array $errors = [],
        ?array $transformedData = null
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->transformedData = $transformedData;
    }
    
    public function isValid(): bool {
        return $this->isValid;
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
    
    public function getTransformedData(): ?array {
        return $this->transformedData;
    }
    
    public function addError(ValidationError $error): void {
        $this->errors[] = $error;
        $this->isValid = false;
    }
    
    public function setTransformedData(array $data): void {
        $this->transformedData = $data;
    }
    
    public function toErrorArray(): array {
        return array_map(function($error) {
            return $error instanceof ValidationError 
                ? $error->toArray() 
                : $error;
        }, $this->errors);
    }
}
```

---

### Stap 3: Database Service Aanmaken

**Actie:** Maak BrpDatabaseService voor PostgreSQL queries

#### 3.1 BrpDatabaseService.php

**Locatie:** `lib/Service/Database/BrpDatabaseService.php`

**Code:**
```php
<?php
/**
 * BRP Database Service
 * 
 * Database queries voor validatie tegen probev schema
 */

namespace OCA\OpenRegister\Service\Database;

use PDO;
use PDOException;

class BrpDatabaseService {
    private ?PDO $pdo = null;
    
    private function getConnection(): PDO {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                    'postgres',
                    'postgres',
                    [
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                error_log("PostgreSQL connection error: " . $e->getMessage());
                throw new \RuntimeException("Database connection failed", 0, $e);
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Check of BSN bestaat in BRP
     */
    public function findPersonByBsn(string $bsn): ?array {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    pl_id,
                    bsn,
                    status,
                    overlijdensdatum,
                    geschorst
                FROM inw_ax
                WHERE bsn = :bsn
                AND ax = 'A'
                AND hist = 'A'
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error finding person by BSN: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check obstructions voor een BSN
     */
    public function checkObstructions(string $bsn): array {
        $obstructions = [];
        
        try {
            $person = $this->findPersonByBsn($bsn);
            
            if (!$person) {
                $obstructions[] = 'NO_PERSON_RECORD_FOUND';
                return $obstructions;
            }
            
            // Check status
            if (isset($person['status']) && $person['status'] === 'BLOCKED') {
                $obstructions[] = 'PERSON_RECORD_IS_BLOCKED';
            }
            
            // Check overlijden
            if (isset($person['overlijdensdatum']) && $person['overlijdensdatum'] !== null) {
                $obstructions[] = 'PERSON_IS_DECEASED';
            }
            
            // Check geschorst
            if (isset($person['geschorst']) && $person['geschorst'] === true) {
                $obstructions[] = 'PERSONLIST_SUSPENDED';
            }
            
        } catch (PDOException $e) {
            error_log("Error checking obstructions: " . $e->getMessage());
        }
        
        return $obstructions;
    }
    
    /**
     * Check of er lopende verhuizingen zijn
     */
    public function findActiveRelocations(string $bsn): array {
        // TODO: Implementeer wanneer dossiers register bestaat
        // Voor nu: return lege array
        return [];
    }
    
    /**
     * Haal relaties op voor een BSN
     */
    public function findRelatives(string $bsn): array {
        // Gebruik bestaande relaties endpoint logica
        // TODO: Implementeer of gebruik HaalCentraalBrpController logica
        return [];
    }
    
    /**
     * Valideer adres (conceptueel - afhankelijk van adresregister)
     */
    public function validateAddress(array $address): bool {
        // TODO: Implementeer wanneer adresregister beschikbaar is
        return true;
    }
}
```

---

### Stap 4: Syntactic Validator Aanmaken

**Actie:** Maak SyntacticValidator voor syntactische validaties

#### 4.1 SyntacticValidator.php

**Locatie:** `lib/Service/Validation/SyntacticValidator.php`

**Code:**
```php
<?php
/**
 * Syntactic Validator
 * 
 * Syntactische validaties: JSON schema, formaten, verplichte velden
 */

namespace OCA\OpenRegister\Service\Validation;

class SyntacticValidator {
    
    /**
     * Valideer BSN formaat
     */
    public function validateBsn(?string $bsn): ?ValidationError {
        if ($bsn === null || $bsn === '') {
            return new ValidationError('bsn', 'BSN is required');
        }
        
        if (!is_string($bsn)) {
            return new ValidationError('bsn', 'BSN must be a string');
        }
        
        if (!preg_match('/^\d{9}$/', $bsn)) {
            return new ValidationError('bsn', 'BSN must be exactly 9 digits');
        }
        
        return null;
    }
    
    /**
     * Valideer postcode formaat
     */
    public function validatePostalCode(?string $postcode): ?ValidationError {
        if ($postcode === null || $postcode === '') {
            return new ValidationError('postalCode', 'Postal code is required');
        }
        
        if (!is_string($postcode)) {
            return new ValidationError('postalCode', 'Postal code must be a string');
        }
        
        if (!preg_match('/^\d{4}[A-Z]{2}$/', $postcode)) {
            return new ValidationError(
                'postalCode', 
                'Postal code format is invalid (expected: 1234AB)'
            );
        }
        
        return null;
    }
    
    /**
     * Valideer datum formaat (ISO 8601)
     */
    public function validateDate(?string $date, string $fieldName = 'date'): ?ValidationError {
        if ($date === null || $date === '') {
            return new ValidationError($fieldName, 'Date is required');
        }
        
        if (!is_string($date)) {
            return new ValidationError($fieldName, 'Date must be a string');
        }
        
        // Check ISO 8601 formaat (YYYY-MM-DD of YYYY-MM-DDTHH:MM:SSZ)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z?)?$/', $date)) {
            return new ValidationError(
                $fieldName,
                'Date format is invalid (expected: YYYY-MM-DD)'
            );
        }
        
        // Check of datum geldig is
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return new ValidationError($fieldName, 'Date is not a valid date');
        }
        
        return null;
    }
    
    /**
     * Valideer verplichte velden
     */
    public function validateRequiredFields(array $data, array $requiredFields): array {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            $value = $this->getNestedValue($data, $field);
            
            if ($value === null || $value === '') {
                $errors[] = new ValidationError(
                    $field,
                    ucfirst(str_replace('.', ' ', $field)) . ' is required'
                );
            }
        }
        
        return $errors;
    }
    
    /**
     * Haal geneste waarde op uit array (bijv. "declarant.bsn")
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
    
    /**
     * Valideer JSON structuur
     */
    public function validateJson($data): ?ValidationError {
        if (!is_array($data)) {
            return new ValidationError('request', 'Request body must be a JSON object');
        }
        
        return null;
    }
}
```

---

### Stap 5: Semantic Validator Aanmaken

**Actie:** Maak SemanticValidator voor semantische validaties met database queries

#### 5.1 SemanticValidator.php

**Locatie:** `lib/Service/Validation/SemanticValidator.php`

**Code:**
```php
<?php
/**
 * Semantic Validator
 * 
 * Semantische validaties: database checks, business rules, obstructions
 */

namespace OCA\OpenRegister\Service\Validation;

use OCA\OpenRegister\Service\Database\BrpDatabaseService;

class SemanticValidator {
    private BrpDatabaseService $dbService;
    
    public function __construct(BrpDatabaseService $dbService) {
        $this->dbService = $dbService;
    }
    
    /**
     * Valideer of BSN bestaat in BRP
     */
    public function validateBsnExists(string $bsn, string $fieldName = 'bsn'): ?ValidationError {
        $person = $this->dbService->findPersonByBsn($bsn);
        
        if ($person === null) {
            return new ValidationError(
                $fieldName,
                'BSN does not exist in BRP',
                'BSN_NOT_FOUND'
            );
        }
        
        return null;
    }
    
    /**
     * Valideer of BSN niet geblokkeerd is
     */
    public function validateBsnNotBlocked(string $bsn, string $fieldName = 'bsn'): ?ValidationError {
        $obstructions = $this->dbService->checkObstructions($bsn);
        
        if (in_array('PERSON_RECORD_IS_BLOCKED', $obstructions)) {
            return new ValidationError(
                $fieldName,
                'Person record is blocked',
                'PERSON_BLOCKED',
                ['PERSON_RECORD_IS_BLOCKED']
            );
        }
        
        if (in_array('PERSON_IS_DECEASED', $obstructions)) {
            return new ValidationError(
                $fieldName,
                'Person is deceased',
                'PERSON_DECEASED',
                ['PERSON_IS_DECEASED']
            );
        }
        
        if (in_array('PERSONLIST_SUSPENDED', $obstructions)) {
            return new ValidationError(
                $fieldName,
                'Person list is suspended',
                'PERSONLIST_SUSPENDED',
                ['PERSONLIST_SUSPENDED']
            );
        }
        
        return null;
    }
    
    /**
     * Valideer relocator geschiktheid
     */
    public function validateRelocatorSuitable(
        string $relocatorBsn,
        string $declarantBsn,
        string $fieldName = 'relocator'
    ): ?ValidationError {
        // Check of relocator bestaat
        $existsError = $this->validateBsnExists($relocatorBsn, $fieldName);
        if ($existsError !== null) {
            return $existsError;
        }
        
        // Check obstructions
        $obstructions = $this->dbService->checkObstructions($relocatorBsn);
        
        // Check lopende verhuizingen
        $activeRelocations = $this->dbService->findActiveRelocations($relocatorBsn);
        if (!empty($activeRelocations)) {
            $obstructions[] = 'EXISTING_RELOCATION_CASE';
        }
        
        if (!empty($obstructions)) {
            return new ValidationError(
                $fieldName,
                'Person is not suitable for relocation',
                'RELOCATOR_NOT_SUITABLE',
                $obstructions
            );
        }
        
        return null;
    }
    
    /**
     * Valideer adres bestaat
     */
    public function validateAddressExists(array $address, string $fieldPrefix = 'address'): ?ValidationError {
        $isValid = $this->dbService->validateAddress($address);
        
        if (!$isValid) {
            return new ValidationError(
                $fieldPrefix . '.postalCode',
                'Address does not exist in address register'
            );
        }
        
        return null;
    }
}
```

---

### Stap 6: Hoofd Validatie Service Aanmaken

**Actie:** Maak VrijBrpValidationService die alle validaties coördineert

#### 6.1 VrijBrpValidationService.php

**Locatie:** `lib/Service/Validation/VrijBrpValidationService.php`

**Code:**
```php
<?php
/**
 * vrijBRP Validation Service
 * 
 * Hoofd service voor validatie van mutatie requests
 * Coördineert syntactische en semantische validaties
 */

namespace OCA\OpenRegister\Service\Validation;

use OCA\OpenRegister\Service\Database\BrpDatabaseService;

class VrijBrpValidationService {
    private SyntacticValidator $syntacticValidator;
    private SemanticValidator $semanticValidator;
    private BrpDatabaseService $dbService;
    
    public function __construct(
        SyntacticValidator $syntacticValidator,
        SemanticValidator $semanticValidator,
        BrpDatabaseService $dbService
    ) {
        $this->syntacticValidator = $syntacticValidator;
        $this->semanticValidator = $semanticValidator;
        $this->dbService = $dbService;
    }
    
    /**
     * Valideer verhuizing (intra-relocation)
     */
    public function validateRelocation(array $request): ValidationResult {
        $errors = [];
        
        // Syntactische validaties
        $jsonError = $this->syntacticValidator->validateJson($request);
        if ($jsonError !== null) {
            $errors[] = $jsonError;
        }
        
        // Verplichte velden
        $requiredFields = [
            'declarant.bsn',
            'newAddress.street',
            'newAddress.houseNumber',
            'newAddress.postalCode',
            'newAddress.city'
        ];
        $requiredErrors = $this->syntacticValidator->validateRequiredFields($request, $requiredFields);
        $errors = array_merge($errors, $requiredErrors);
        
        // BSN validatie
        if (isset($request['declarant']['bsn'])) {
            $bsnError = $this->syntacticValidator->validateBsn($request['declarant']['bsn']);
            if ($bsnError !== null) {
                $bsnError = new ValidationError(
                    'declarant.bsn',
                    $bsnError->getMessage()
                );
                $errors[] = $bsnError;
            }
        }
        
        // Postcode validatie
        if (isset($request['newAddress']['postalCode'])) {
            $postcodeError = $this->syntacticValidator->validatePostalCode(
                $request['newAddress']['postalCode']
            );
            if ($postcodeError !== null) {
                $postcodeError = new ValidationError(
                    'newAddress.postalCode',
                    $postcodeError->getMessage()
                );
                $errors[] = $postcodeError;
            }
        }
        
        // Als syntactische validatie faalt, stop hier
        if (!empty($errors)) {
            return new ValidationResult(false, $errors);
        }
        
        // Semantische validaties (alleen als syntactisch OK)
        $declarantBsn = $request['declarant']['bsn'] ?? null;
        
        if ($declarantBsn) {
            // Check of declarant bestaat
            $existsError = $this->semanticValidator->validateBsnExists(
                $declarantBsn,
                'declarant.bsn'
            );
            if ($existsError !== null) {
                $errors[] = $existsError;
            }
            
            // Check of declarant niet geblokkeerd is
            $blockedError = $this->semanticValidator->validateBsnNotBlocked(
                $declarantBsn,
                'declarant.bsn'
            );
            if ($blockedError !== null) {
                $errors[] = $blockedError;
            }
        }
        
        // Valideer relocators
        if (isset($request['relocators']) && is_array($request['relocators'])) {
            foreach ($request['relocators'] as $index => $relocator) {
                if (isset($relocator['bsn'])) {
                    $relocatorError = $this->semanticValidator->validateRelocatorSuitable(
                        $relocator['bsn'],
                        $declarantBsn,
                        "relocators[$index]"
                    );
                    if ($relocatorError !== null) {
                        $errors[] = $relocatorError;
                    }
                }
            }
        }
        
        // Valideer adres
        if (isset($request['newAddress'])) {
            $addressError = $this->semanticValidator->validateAddressExists(
                $request['newAddress'],
                'newAddress'
            );
            if ($addressError !== null) {
                $errors[] = $addressError;
            }
        }
        
        // Transformeer data (voor nu: return originele data)
        $transformedData = $this->transformRelocationData($request);
        
        return new ValidationResult(
            empty($errors),
            $errors,
            $transformedData
        );
    }
    
    /**
     * Valideer geboorte
     */
    public function validateBirth(array $request): ValidationResult {
        // TODO: Implementeer geboorte validatie
        return new ValidationResult(true, [], $request);
    }
    
    /**
     * Valideer partnerschap
     */
    public function validateCommitment(array $request): ValidationResult {
        // TODO: Implementeer partnerschap validatie
        return new ValidationResult(true, [], $request);
    }
    
    /**
     * Valideer overlijden
     */
    public function validateDeath(array $request): ValidationResult {
        // TODO: Implementeer overlijden validatie
        return new ValidationResult(true, [], $request);
    }
    
    /**
     * Transformeer verhuizing data van API formaat naar database formaat
     */
    private function transformRelocationData(array $request): array {
        // Voor nu: return originele data
        // Later: transformeer naar probev database formaat
        return $request;
    }
}
```

---

### Stap 7: Controller Aanmaken

**Actie:** Maak VrijBrpDossiersController voor mutatie endpoints

#### 7.1 VrijBrpDossiersController.php

**Locatie:** `lib/Controller/VrijBrpDossiersController.php`

**Code:**
```php
<?php
/**
 * vrijBRP Dossiers Controller
 * 
 * Mutatie endpoints voor dossiers (verhuizingen, geboorten, etc.)
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\OpenRegister\Service\Validation\VrijBrpValidationService;
use OCA\OpenRegister\Service\Validation\SyntacticValidator;
use OCA\OpenRegister\Service\Validation\SemanticValidator;
use OCA\OpenRegister\Service\Database\BrpDatabaseService;

class VrijBrpDossiersController extends Controller {
    
    private VrijBrpValidationService $validationService;
    
    public function __construct(
        $appName,
        IRequest $request,
        VrijBrpValidationService $validationService
    ) {
        parent::__construct($appName, $request);
        $this->validationService = $validationService;
    }
    
    /**
     * POST /api/v1/relocations/intra
     * Nieuwe verhuizing aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createRelocation(): JSONResponse {
        try {
            // Haal request body op
            $request = json_decode($this->request->getRawInput(), true);
            
            if ($request === null) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid JSON',
                    'errors' => [
                        [
                            'field' => 'request',
                            'message' => 'Request body must be valid JSON'
                        ]
                    ]
                ], 400);
            }
            
            // Valideer via vrijBRP Logica Service
            $result = $this->validationService->validateRelocation($request);
            
            if (!$result->isValid()) {
                return new JSONResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $result->toErrorArray()
                ], 422);
            }
            
            // TODO: Sla op in Open Register
            // TODO: Genereer event
            // TODO: Maak tasks aan indien nodig
            
            // Voor nu: return success response
            return new JSONResponse([
                'dossierId' => 'temp-' . uniqid(),
                'status' => 'incomplete',
                'dossierType' => 'intra_mun_relocation',
                'createdAt' => date('c')
            ], 201);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/v1/birth
     * Nieuwe geboorte aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createBirth(): JSONResponse {
        // TODO: Implementeer geboorte endpoint
        return new JSONResponse(['message' => 'Not implemented'], 501);
    }
    
    /**
     * POST /api/v1/commitment
     * Nieuw partnerschap aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createCommitment(): JSONResponse {
        // TODO: Implementeer partnerschap endpoint
        return new JSONResponse(['message' => 'Not implemented'], 501);
    }
}
```

---

### Stap 8: Dependency Injection Setup

**Actie:** Zorg dat services kunnen worden geïnjecteerd

**Opmerking:** Nextcloud gebruikt dependency injection via constructor. Services moeten worden geregistreerd in `appinfo/services.php` of via autowiring.

**Voorbeeld services.php:**
```php
<?php
// appinfo/services.php (als dit bestand bestaat)

use OCA\OpenRegister\Service\Validation\VrijBrpValidationService;
use OCA\OpenRegister\Service\Validation\SyntacticValidator;
use OCA\OpenRegister\Service\Validation\SemanticValidator;
use OCA\OpenRegister\Service\Database\BrpDatabaseService;

$container->registerService(BrpDatabaseService::class, function($c) {
    return new BrpDatabaseService();
});

$container->registerService(SyntacticValidator::class, function($c) {
    return new SyntacticValidator();
});

$container->registerService(SemanticValidator::class, function($c) {
    return new SemanticValidator(
        $c->query(BrpDatabaseService::class)
    );
});

$container->registerService(VrijBrpValidationService::class, function($c) {
    return new VrijBrpValidationService(
        $c->query(SyntacticValidator::class),
        $c->query(SemanticValidator::class),
        $c->query(BrpDatabaseService::class)
    );
});
```

**Alternatief:** Gebruik constructor injection direct in controller (Nextcloud ondersteunt autowiring).

---

### Stap 9: Routes Toevoegen

**Actie:** Voeg routes toe voor mutatie endpoints

**Locatie:** `appinfo/routes.php` (in Open Register app) of maak custom routes

**Voorbeeld routes:**
```php
// routes.php
return [
    'routes' => [
        // Mutatie endpoints
        ['name' => 'VrijBrpDossiers#createRelocation', 'url' => '/api/v1/relocations/intra', 'verb' => 'POST'],
        ['name' => 'VrijBrpDossiers#createBirth', 'url' => '/api/v1/birth', 'verb' => 'POST'],
        ['name' => 'VrijBrpDossiers#createCommitment', 'url' => '/api/v1/commitment', 'verb' => 'POST'],
    ]
];
```

**Opmerking:** Als routes.php niet bestaat, moet je deze aanmaken of routes toevoegen aan bestaand bestand.

---

## 3. Implementatie Checklist

### Fase 1: Basis Infrastructuur

- [ ] Maak directory structuur (`lib/Service/Validation/`, etc.)
- [ ] Maak `ValidationError.php`
- [ ] Maak `ValidationResult.php`
- [ ] Maak `BrpDatabaseService.php`
- [ ] Test database connectie

### Fase 2: Validators

- [ ] Maak `SyntacticValidator.php`
- [ ] Implementeer BSN validatie
- [ ] Implementeer postcode validatie
- [ ] Implementeer datum validatie
- [ ] Maak `SemanticValidator.php`
- [ ] Implementeer BSN exists check
- [ ] Implementeer obstructions check

### Fase 3: Hoofd Service

- [ ] Maak `VrijBrpValidationService.php`
- [ ] Implementeer `validateRelocation()`
- [ ] Test validatie flow

### Fase 4: Controller

- [ ] Maak `VrijBrpDossiersController.php`
- [ ] Implementeer `createRelocation()` endpoint
- [ ] Voeg routes toe
- [ ] Test endpoint

---

## 4. Testing

### 4.1 Unit Tests

**Voorbeeld test:**
```php
// tests/Unit/Service/Validation/SyntacticValidatorTest.php

use OCA\OpenRegister\Service\Validation\SyntacticValidator;

class SyntacticValidatorTest extends TestCase {
    public function testBsnValidation() {
        $validator = new SyntacticValidator();
        
        // Valid BSN
        $this->assertNull($validator->validateBsn('123456789'));
        
        // Invalid BSN (te kort)
        $error = $validator->validateBsn('12345');
        $this->assertNotNull($error);
        $this->assertEquals('BSN must be exactly 9 digits', $error->getMessage());
    }
}
```

### 4.2 Integratie Tests

**Test mutatie endpoint:**
```bash
curl -X POST http://localhost:8080/apps/openregister/api/v1/relocations/intra \
  -H "Content-Type: application/json" \
  -d '{
    "declarant": {
      "bsn": "123456789"
    },
    "newAddress": {
      "street": "Teststraat",
      "houseNumber": "1",
      "postalCode": "1234AB",
      "city": "Amsterdam"
    }
  }'
```

---

## 5. Volgende Stappen

1. **Start met Fase 1** - Maak basis infrastructuur
2. **Test database connectie** - Zorg dat PostgreSQL bereikbaar is
3. **Implementeer syntactische validaties** - Start met BSN validatie
4. **Implementeer semantische validaties** - Start met BSN exists check
5. **Bouw mutatie endpoint** - Test volledige flow

---

## 6. Belangrijke Opmerkingen

### 6.1 Namespace

**Gebruik:** `OCA\OpenRegister\Service\Validation`

**Reden:** Volgt Nextcloud naming convention en past bij bestaande controllers.

### 6.2 Database Connectie

**Huidige configuratie:**
- Host: `host.docker.internal`
- Port: `5432`
- Database: `bevax`
- Schema: `probev`
- User: `postgres`
- Password: `postgres`

**Pas aan indien nodig** via environment variables of configuratie.

### 6.3 Error Handling

**Gebruik:** Gestructureerde error responses conform vrijBRP Dossiers API.

**Formaat:**
- 400: Syntactische fouten
- 422: Semantische fouten
- 403: Autorisation fouten
- 500: Server errors

---

## Referenties

- [OPENREGISTER-VALIDATIE-IMPLEMENTATIE-PLAN.md](./OPENREGISTER-VALIDATIE-IMPLEMENTATIE-PLAN.md)
- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md)
- [lib/Controller/HaalCentraalBrpController.php](./lib/Controller/HaalCentraalBrpController.php)







