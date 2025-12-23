# Implementatieplan: Mutaties naar BRP

**Datum:** 2025-01-27  
**Prioriteit:** 🟡 Prioriteit 3  
**Geschatte tijd:** 3-4 weken  
**Impact:** Mutaties compliance: 20% → 35% (+15%)

---

## Executive Summary

Dit plan beschrijft de implementatie van mutatie-functionaliteit voor BRP (Basisregistratie Personen) volgens de Common Ground architectuur. Mutaties worden gecoördineerd door Open Register (Laag 2) en gevalideerd door de vrijBRP Logica Service (Laag 3).

**Architectuur:**
```
[ZGW-systeem] → [Open Register API] → [vrijBRP Logica Service] → [Database]
     (Laag 4)         (Laag 2)              (Laag 3)              (Laag 1)
```

---

## 1. Architectuur Overzicht

### 1.1 Common Ground 5-Lagen Model

| Laag | Component | Verantwoordelijkheid |
|------|-----------|---------------------|
| **Laag 1** | PostgreSQL bevax | Data opslag (probev schema) |
| **Laag 2** | Open Register | Coördinatie, autorisatie, persistente opslag, eventing |
| **Laag 3** | vrijBRP Logica Service | **Validatie, RVIG-regels, datatransformatie** |
| **Laag 4** | ZGW-systeem | Workflow orchestration, proceslogica |
| **Laag 5** | UI/Interfaces | Gebruikersinteractie |

### 1.2 Mutatie Flow

```
[ZGW-systeem] (Laag 4)
    ↓ POST /api/v1/relocations/intra
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

---

## 2. Huidige Status

### 2.1 Wat bestaat al?

✅ **VrijBrpValidationService.php** - Basis structuur aanwezig
- SyntacticValidator en SemanticValidator classes
- Basis validatie methodes
- Database service integratie

✅ **Open Register infrastructuur**
- CRUD operaties voor objecten
- Versiebeheer out-of-the-box
- Eventing systeem

✅ **Database structuur**
- PostgreSQL bevax database
- probev schema met 198 tabellen
- Views voor Haal Centraal compatibiliteit

### 2.2 Wat ontbreekt?

❌ **Mutatie endpoints**
- Geen POST/PUT/DELETE endpoints voor BRP mutaties
- Geen mutatie controllers

❌ **Volledige validatie**
- Niet alle RVIG-regels geïmplementeerd
- Geen volledige syntactische validatie
- Geen volledige semantische validatie

❌ **Datatransformatie**
- Geen transformatie van API formaat naar database formaat
- Geen historie-afhandeling

❌ **Error handling**
- Geen gestructureerde error responses
- Geen Haal Centraal-compliant error codes

---

## 3. Implementatie Stappen

### Fase 1: Validatie Service Uitbreiden (Week 1)

#### 3.1 Syntactische Validatie Completeren

**Doel:** Alle syntactische validaties implementeren

**Validaties:**
- ✅ JSON schema validatie
- ✅ Verplichte velden check
- ✅ Datatype validatie
- ✅ Formaat validatie (BSN, postcode, datum, etc.)

**Implementatie:**

```php
// lib/Service/Validation/SyntacticValidator.php
class SyntacticValidator {
    /**
     * Valideer BSN formaat
     */
    public function validateBsn(string $bsn): ?ValidationError {
        if (!preg_match('/^\d{9}$/', $bsn)) {
            return new ValidationError(
                'bsn',
                'BSN must be exactly 9 digits',
                'INVALID_BSN_FORMAT'
            );
        }
        
        // 11-proef check
        if (!$this->validateBsn11Proef($bsn)) {
            return new ValidationError(
                'bsn',
                'BSN does not pass 11-proef validation',
                'INVALID_BSN_CHECKSUM'
            );
        }
        
        return null;
    }
    
    /**
     * Valideer postcode formaat
     */
    public function validatePostalCode(string $postalCode): ?ValidationError {
        if (!preg_match('/^\d{4}[A-Z]{2}$/', $postalCode)) {
            return new ValidationError(
                'postalCode',
                'Postal code must be in format 1234AB',
                'INVALID_POSTAL_CODE_FORMAT'
            );
        }
        return null;
    }
    
    /**
     * Valideer datum formaat (ISO 8601)
     */
    public function validateDate(string $date): ?ValidationError {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            return new ValidationError(
                'date',
                'Date must be in ISO 8601 format (YYYY-MM-DD)',
                'INVALID_DATE_FORMAT'
            );
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
                    "Required field '{$field}' is missing",
                    'REQUIRED_FIELD_MISSING'
                );
            }
        }
        return $errors;
    }
}
```

#### 3.2 Semantische Validatie Completeren

**Doel:** Business rules en database checks implementeren

**Validaties:**
- ✅ BSN bestaat in BRP
- ✅ BSN is niet geblokkeerd
- ✅ Adres bestaat en is geldig
- ✅ Relaties zijn geldig
- ✅ Geen obstructions

**Implementatie:**

```php
// lib/Service/Validation/SemanticValidator.php
class SemanticValidator {
    private BrpDatabaseService $dbService;
    
    /**
     * Valideer dat BSN bestaat in BRP
     */
    public function validateBsnExists(string $bsn): ?ValidationError {
        $person = $this->dbService->getPersonByBsn($bsn);
        if (!$person) {
            return new ValidationError(
                'bsn',
                "BSN '{$bsn}' does not exist in BRP",
                'BSN_NOT_FOUND'
            );
        }
        return null;
    }
    
    /**
     * Valideer dat BSN niet geblokkeerd is
     */
    public function validateBsnNotBlocked(string $bsn): ?ValidationError {
        $person = $this->dbService->getPersonByBsn($bsn);
        if ($person && $person['status'] === 'blocked') {
            return new ValidationError(
                'bsn',
                "BSN '{$bsn}' is blocked",
                'BSN_BLOCKED'
            );
        }
        return null;
    }
    
    /**
     * Valideer adres bestaat en is geldig
     */
    public function validateAddress(array $address): ?ValidationError {
        $postalCode = $address['postalCode'] ?? null;
        $houseNumber = $address['houseNumber'] ?? null;
        $street = $address['street'] ?? null;
        $city = $address['city'] ?? null;
        
        if (!$this->dbService->addressExists($postalCode, $houseNumber, $street, $city)) {
            return new ValidationError(
                'address',
                'Address does not exist or is invalid',
                'INVALID_ADDRESS'
            );
        }
        return null;
    }
    
    /**
     * Valideer relocator (voor verhuizing)
     */
    public function validateRelocator(string $bsn): ?ValidationError {
        $person = $this->dbService->getPersonByBsn($bsn);
        if (!$person) {
            return new ValidationError(
                'relocator.bsn',
                'Relocator BSN does not exist',
                'RELOCATOR_NOT_FOUND'
            );
        }
        
        // Check of persoon geschikt is voor verhuizing
        if (!$person['suitableForRelocation']) {
            return new ValidationError(
                'relocator.bsn',
                'Relocator is not suitable for relocation',
                'RELOCATOR_NOT_SUITABLE'
            );
        }
        
        // Check obstructions
        $obstructions = $this->dbService->getObstructions($bsn);
        if (!empty($obstructions)) {
            return new ValidationError(
                'relocator.bsn',
                'Relocator has obstructions: ' . implode(', ', $obstructions),
                'RELOCATOR_HAS_OBSTRUCTIONS',
                $obstructions
            );
        }
        
        return null;
    }
}
```

#### 3.3 RVIG Validatie Implementeren

**Doel:** Complexe BRP-regels implementeren volgens RVIG

**Belangrijkste RVIG-regels:**
- ✅ Geboortedatum mag niet in de toekomst liggen
- ✅ Overlijdensdatum mag niet voor geboortedatum liggen
- ✅ Verhuizing mag niet naar hetzelfde adres
- ✅ Partnerschap mag alleen tussen volwassenen
- ✅ Gezagsverhoudingen alleen voor minderjarigen

**Implementatie:**

```php
// lib/Service/Validation/RvigValidator.php
class RvigValidator {
    private BrpDatabaseService $dbService;
    
    /**
     * Valideer geboorte mutatie volgens RVIG
     */
    public function validateBirth(array $request): array {
        $errors = [];
        
        // Geboortedatum mag niet in de toekomst liggen
        $birthDate = $request['birthDate'] ?? null;
        if ($birthDate) {
            $birthDateTime = new DateTime($birthDate);
            $now = new DateTime();
            if ($birthDateTime > $now) {
                $errors[] = new ValidationError(
                    'birthDate',
                    'Birth date cannot be in the future',
                    'BIRTH_DATE_IN_FUTURE'
                );
            }
        }
        
        // Moeder moet bestaan en vrouw zijn
        $motherBsn = $request['mother']['bsn'] ?? null;
        if ($motherBsn) {
            $mother = $this->dbService->getPersonByBsn($motherBsn);
            if (!$mother) {
                $errors[] = new ValidationError(
                    'mother.bsn',
                    'Mother BSN does not exist',
                    'MOTHER_NOT_FOUND'
                );
            } elseif ($mother['gender'] !== 'V') {
                $errors[] = new ValidationError(
                    'mother.bsn',
                    'Mother must be female',
                    'MOTHER_NOT_FEMALE'
                );
            }
        }
        
        // Vader moet bestaan en man zijn
        $fatherBsn = $request['father']['bsn'] ?? null;
        if ($fatherBsn) {
            $father = $this->dbService->getPersonByBsn($fatherBsn);
            if (!$father) {
                $errors[] = new ValidationError(
                    'father.bsn',
                    'Father BSN does not exist',
                    'FATHER_NOT_FOUND'
                );
            } elseif ($father['gender'] !== 'M') {
                $errors[] = new ValidationError(
                    'father.bsn',
                    'Father must be male',
                    'FATHER_NOT_MALE'
                );
            }
        }
        
        return $errors;
    }
    
    /**
     * Valideer verhuizing mutatie volgens RVIG
     */
    public function validateRelocation(array $request): array {
        $errors = [];
        
        $declarantBsn = $request['declarant']['bsn'] ?? null;
        $newAddress = $request['newAddress'] ?? null;
        
        if ($declarantBsn && $newAddress) {
            // Haal huidig adres op
            $currentAddress = $this->dbService->getCurrentAddress($declarantBsn);
            
            // Verhuizing mag niet naar hetzelfde adres
            if ($currentAddress) {
                $sameAddress = (
                    $currentAddress['postalCode'] === $newAddress['postalCode'] &&
                    $currentAddress['houseNumber'] === $newAddress['houseNumber'] &&
                    $currentAddress['street'] === $newAddress['street']
                );
                
                if ($sameAddress) {
                    $errors[] = new ValidationError(
                        'newAddress',
                        'Cannot relocate to the same address',
                        'SAME_ADDRESS_RELOCATION'
                    );
                }
            }
            
            // Verhuisdatum mag niet in de toekomst liggen
            $relocationDate = $request['relocationDate'] ?? null;
            if ($relocationDate) {
                $relocationDateTime = new DateTime($relocationDate);
                $now = new DateTime();
                if ($relocationDateTime > $now) {
                    $errors[] = new ValidationError(
                        'relocationDate',
                        'Relocation date cannot be in the future',
                        'RELOCATION_DATE_IN_FUTURE'
                    );
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Valideer partnerschap mutatie volgens RVIG
     */
    public function validatePartnership(array $request): array {
        $errors = [];
        
        $partner1Bsn = $request['partner1']['bsn'] ?? null;
        $partner2Bsn = $request['partner2']['bsn'] ?? null;
        
        if ($partner1Bsn && $partner2Bsn) {
            $partner1 = $this->dbService->getPersonByBsn($partner1Bsn);
            $partner2 = $this->dbService->getPersonByBsn($partner2Bsn);
            
            // Beide partners moeten bestaan
            if (!$partner1) {
                $errors[] = new ValidationError(
                    'partner1.bsn',
                    'Partner 1 BSN does not exist',
                    'PARTNER1_NOT_FOUND'
                );
            }
            
            if (!$partner2) {
                $errors[] = new ValidationError(
                    'partner2.bsn',
                    'Partner 2 BSN does not exist',
                    'PARTNER2_NOT_FOUND'
                );
            }
            
            // Partners mogen niet dezelfde persoon zijn
            if ($partner1Bsn === $partner2Bsn) {
                $errors[] = new ValidationError(
                    'partners',
                    'Partners cannot be the same person',
                    'SAME_PERSON_PARTNERSHIP'
                );
            }
            
            // Partners moeten volwassen zijn (18+)
            if ($partner1 && !$this->isAdult($partner1)) {
                $errors[] = new ValidationError(
                    'partner1.bsn',
                    'Partner 1 must be 18 years or older',
                    'PARTNER1_NOT_ADULT'
                );
            }
            
            if ($partner2 && !$this->isAdult($partner2)) {
                $errors[] = new ValidationError(
                    'partner2.bsn',
                    'Partner 2 must be 18 years or older',
                    'PARTNER2_NOT_ADULT'
                );
            }
        }
        
        return $errors;
    }
    
    private function isAdult(array $person): bool {
        $birthDate = new DateTime($person['birthDate']);
        $now = new DateTime();
        $age = $now->diff($birthDate)->y;
        return $age >= 18;
    }
}
```

---

### Fase 2: Datatransformatie (Week 2)

#### 4.1 Transformatie Service

**Doel:** Transformeer API formaat naar database formaat

**Implementatie:**

```php
// lib/Service/Transformation/DataTransformationService.php
class DataTransformationService {
    private BrpDatabaseService $dbService;
    
    /**
     * Transformeer verhuizing API request naar database formaat
     */
    public function transformRelocation(array $apiRequest): array {
        $dbData = [];
        
        // Basis gegevens
        $dbData['declarant_bsn'] = $apiRequest['declarant']['bsn'];
        $dbData['relocation_date'] = $apiRequest['relocationDate'];
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Nieuw adres
        $newAddress = $apiRequest['newAddress'];
        $dbData['new_postal_code'] = $newAddress['postalCode'];
        $dbData['new_house_number'] = $newAddress['houseNumber'];
        $dbData['new_street'] = $newAddress['street'];
        $dbData['new_city'] = $newAddress['city'];
        $dbData['new_country'] = $newAddress['country'] ?? 'NL';
        
        // Relocator (indien aanwezig)
        if (isset($apiRequest['relocator'])) {
            $dbData['relocator_bsn'] = $apiRequest['relocator']['bsn'];
            $dbData['relocator_relationship'] = $apiRequest['relocator']['relationship'];
        }
        
        // Metadata voor historie
        $dbData['mutation_type'] = 'relocation';
        $dbData['mutation_date'] = date('Y-m-d H:i:s');
        $dbData['status'] = 'pending';
        
        return $dbData;
    }
    
    /**
     * Transformeer geboorte API request naar database formaat
     */
    public function transformBirth(array $apiRequest): array {
        $dbData = [];
        
        // Basis gegevens
        $dbData['birth_date'] = $apiRequest['birthDate'];
        $dbData['birth_place'] = $apiRequest['birthPlace'];
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Persoon gegevens
        $person = $apiRequest['person'];
        $dbData['first_names'] = implode(' ', $person['firstNames']);
        $dbData['last_name'] = $person['lastName'];
        $dbData['gender'] = $person['gender'];
        
        // Ouders
        if (isset($apiRequest['mother'])) {
            $dbData['mother_bsn'] = $apiRequest['mother']['bsn'];
        }
        if (isset($apiRequest['father'])) {
            $dbData['father_bsn'] = $apiRequest['father']['bsn'];
        }
        
        // Metadata
        $dbData['mutation_type'] = 'birth';
        $dbData['mutation_date'] = date('Y-m-d H:i:s');
        $dbData['status'] = 'pending';
        
        return $dbData;
    }
    
    /**
     * Transformeer partnerschap API request naar database formaat
     */
    public function transformPartnership(array $apiRequest): array {
        $dbData = [];
        
        // Basis gegevens
        $dbData['partnership_date'] = $apiRequest['commitmentDate'];
        $dbData['partnership_place'] = $apiRequest['commitmentPlace'];
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Partners
        $dbData['partner1_bsn'] = $apiRequest['partner1']['bsn'];
        $dbData['partner2_bsn'] = $apiRequest['partner2']['bsn'];
        
        // Metadata
        $dbData['mutation_type'] = 'partnership';
        $dbData['mutation_date'] = date('Y-m-d H:i:s');
        $dbData['status'] = 'pending';
        
        return $dbData;
    }
}
```

---

### Fase 3: Mutatie Controllers (Week 3)

#### 5.1 Mutatie Controller Structuur

**Doel:** POST/PUT/DELETE endpoints voor BRP mutaties

**Implementatie:**

```php
// lib/Controller/BrpMutationController.php
namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCA\OpenRegister\Service\Validation\VrijBrpValidationService;
use OCA\OpenRegister\Service\Transformation\DataTransformationService;
use OCA\OpenRegister\Service\Database\BrpDatabaseService;
use OCA\OpenRegister\Service\ObjectService;

class BrpMutationController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request,
        private VrijBrpValidationService $validationService,
        private DataTransformationService $transformationService,
        private BrpDatabaseService $dbService,
        private ObjectService $objectService
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * POST /api/v1/relocations/intra
     * Verhuizing (intra-relocation) mutatie
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createRelocation(): DataResponse {
        try {
            $requestData = $this->request->getParams();
            
            // Stap 1: Validatie
            $validationResult = $this->validationService->validateRelocation($requestData);
            
            if (!$validationResult->isValid()) {
                return new DataResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $validationResult->getErrors()
                ], 422);
            }
            
            // Stap 2: Datatransformatie
            $dbData = $this->transformationService->transformRelocation($requestData);
            
            // Stap 3: Database write (in transactie)
            $dossierId = $this->dbService->createRelocation($dbData);
            
            // Stap 4: Event genereren
            $this->objectService->emitEvent('relocation.created', [
                'dossierId' => $dossierId,
                'declarantBsn' => $requestData['declarant']['bsn']
            ]);
            
            // Stap 5: Response
            return new DataResponse([
                'dossierId' => $dossierId,
                'status' => 'pending',
                'referenceId' => $requestData['referenceId'] ?? null
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createRelocation: " . $e->getMessage());
            return new DataResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/v1/births
     * Geboorte mutatie
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createBirth(): DataResponse {
        try {
            $requestData = $this->request->getParams();
            
            // Validatie
            $validationResult = $this->validationService->validateBirth($requestData);
            
            if (!$validationResult->isValid()) {
                return new DataResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $validationResult->getErrors()
                ], 422);
            }
            
            // Datatransformatie
            $dbData = $this->transformationService->transformBirth($requestData);
            
            // Database write
            $dossierId = $this->dbService->createBirth($dbData);
            
            // Event genereren
            $this->objectService->emitEvent('birth.created', [
                'dossierId' => $dossierId,
                'birthDate' => $requestData['birthDate']
            ]);
            
            return new DataResponse([
                'dossierId' => $dossierId,
                'status' => 'pending',
                'referenceId' => $requestData['referenceId'] ?? null
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createBirth: " . $e->getMessage());
            return new DataResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/v1/partnerships
     * Partnerschap mutatie
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createPartnership(): DataResponse {
        try {
            $requestData = $this->request->getParams();
            
            // Validatie
            $validationResult = $this->validationService->validatePartnership($requestData);
            
            if (!$validationResult->isValid()) {
                return new DataResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $validationResult->getErrors()
                ], 422);
            }
            
            // Datatransformatie
            $dbData = $this->transformationService->transformPartnership($requestData);
            
            // Database write
            $dossierId = $this->dbService->createPartnership($dbData);
            
            // Event genereren
            $this->objectService->emitEvent('partnership.created', [
                'dossierId' => $dossierId,
                'partner1Bsn' => $requestData['partner1']['bsn'],
                'partner2Bsn' => $requestData['partner2']['bsn']
            ]);
            
            return new DataResponse([
                'dossierId' => $dossierId,
                'status' => 'pending',
                'referenceId' => $requestData['referenceId'] ?? null
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createPartnership: " . $e->getMessage());
            return new DataResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /api/v1/relocations/{dossierId}
     * Verhuizing mutatie bijwerken
     */
    public function updateRelocation(string $dossierId): DataResponse {
        // Implementatie vergelijkbaar met createRelocation
        // Maar met update logica
    }
    
    /**
     * DELETE /api/v1/relocations/{dossierId}
     * Verhuizing mutatie verwijderen
     */
    public function deleteRelocation(string $dossierId): DataResponse {
        // Implementatie voor verwijderen
    }
}
```

---

### Fase 4: Database Service Uitbreiden (Week 3-4)

#### 6.1 Database Mutatie Methodes

**Doel:** Database operaties voor mutaties

**Implementatie:**

```php
// lib/Service/Database/BrpDatabaseService.php (uitbreiden)
class BrpDatabaseService {
    
    /**
     * Maak verhuizing mutatie aan
     */
    public function createRelocation(array $data): string {
        // Start transactie
        $this->db->beginTransaction();
        
        try {
            // Genereer dossier ID
            $dossierId = $this->generateDossierId();
            
            // Sla mutatie op in mutaties tabel
            $qb = $this->db->getQueryBuilder();
            $qb->insert('mutaties')
                ->setValue('dossier_id', $qb->createNamedParameter($dossierId))
                ->setValue('mutation_type', $qb->createNamedParameter('relocation'))
                ->setValue('declarant_bsn', $qb->createNamedParameter($data['declarant_bsn']))
                ->setValue('relocation_date', $qb->createNamedParameter($data['relocation_date']))
                ->setValue('new_postal_code', $qb->createNamedParameter($data['new_postal_code']))
                ->setValue('new_house_number', $qb->createNamedParameter($data['new_house_number']))
                ->setValue('new_street', $qb->createNamedParameter($data['new_street']))
                ->setValue('new_city', $qb->createNamedParameter($data['new_city']))
                ->setValue('status', $qb->createNamedParameter('pending'))
                ->setValue('created_at', $qb->createNamedParameter(date('Y-m-d H:i:s')));
            
            $qb->executeStatement();
            
            // Commit transactie
            $this->db->commit();
            
            return $dossierId;
            
        } catch (\Exception $e) {
            // Rollback bij fout
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Maak geboorte mutatie aan
     */
    public function createBirth(array $data): string {
        // Vergelijkbaar met createRelocation
    }
    
    /**
     * Maak partnerschap mutatie aan
     */
    public function createPartnership(array $data): string {
        // Vergelijkbaar met createRelocation
    }
    
    /**
     * Haal persoon op bij BSN
     */
    public function getPersonByBsn(string $bsn): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('v_personen_compleet_haal_centraal')
           ->where($qb->expr()->eq('bsn', $qb->createNamedParameter($bsn)))
           ->setMaxResults(1);
        
        $result = $qb->executeQuery();
        $row = $result->fetch();
        
        return $row ?: null;
    }
    
    /**
     * Check of adres bestaat
     */
    public function addressExists(string $postalCode, string $houseNumber, string $street, string $city): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->createFunction('COUNT(*)'))
           ->from('v_vb_ax_haal_centraal')
           ->where($qb->expr()->eq('postcode', $qb->createNamedParameter($postalCode)))
           ->andWhere($qb->expr()->eq('huisnummer', $qb->createNamedParameter($houseNumber)))
           ->andWhere($qb->expr()->eq('straat', $qb->createNamedParameter($street)))
           ->andWhere($qb->expr()->eq('woonplaats', $qb->createNamedParameter($city)));
        
        $result = $qb->executeQuery();
        $count = $result->fetchOne();
        
        return $count > 0;
    }
    
    /**
     * Haal huidig adres op voor BSN
     */
    public function getCurrentAddress(string $bsn): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('v_vb_ax_haal_centraal')
           ->where($qb->expr()->eq('bsn', $qb->createNamedParameter($bsn)))
           ->orderBy('ingangsdatum', 'DESC')
           ->setMaxResults(1);
        
        $result = $qb->executeQuery();
        $row = $result->fetch();
        
        return $row ?: null;
    }
    
    /**
     * Haal obstructions op voor BSN
     */
    public function getObstructions(string $bsn): array {
        // Implementatie voor obstructions check
        // Bijvoorbeeld: lopende zaken, geblokkeerde status, etc.
        return [];
    }
    
    /**
     * Genereer uniek dossier ID
     */
    private function generateDossierId(): string {
        return 'DOSSIER-' . date('Ymd') . '-' . uniqid();
    }
}
```

---

### Fase 5: Routes & Error Handling (Week 4)

#### 7.1 Routes Toevoegen

**Doel:** Routes registreren voor mutatie endpoints

**Implementatie:**

```php
// appinfo/routes.php (toevoegen)
return [
    'routes' => [
        // ... bestaande routes ...
        
        // BRP Mutatie endpoints
        ['name' => 'BrpMutation#createRelocation', 'url' => '/api/v1/relocations/intra', 'verb' => 'POST'],
        ['name' => 'BrpMutation#updateRelocation', 'url' => '/api/v1/relocations/{dossierId}', 'verb' => 'PUT'],
        ['name' => 'BrpMutation#deleteRelocation', 'url' => '/api/v1/relocations/{dossierId}', 'verb' => 'DELETE'],
        
        ['name' => 'BrpMutation#createBirth', 'url' => '/api/v1/births', 'verb' => 'POST'],
        ['name' => 'BrpMutation#updateBirth', 'url' => '/api/v1/births/{dossierId}', 'verb' => 'PUT'],
        ['name' => 'BrpMutation#deleteBirth', 'url' => '/api/v1/births/{dossierId}', 'verb' => 'DELETE'],
        
        ['name' => 'BrpMutation#createPartnership', 'url' => '/api/v1/partnerships', 'verb' => 'POST'],
        ['name' => 'BrpMutation#updatePartnership', 'url' => '/api/v1/partnerships/{dossierId}', 'verb' => 'PUT'],
        ['name' => 'BrpMutation#deletePartnership', 'url' => '/api/v1/partnerships/{dossierId}', 'verb' => 'DELETE'],
    ],
];
```

#### 7.2 Error Handling Verbeteren

**Doel:** Gestructureerde error responses volgens Haal Centraal

**Implementatie:**

```php
// lib/Service/Validation/ValidationError.php
class ValidationError {
    private string $field;
    private string $message;
    private string $code;
    private array $obstructions;
    
    public function __construct(string $field, string $message, string $code, array $obstructions = []) {
        $this->field = $field;
        $this->message = $message;
        $this->code = $code;
        $this->obstructions = $obstructions;
    }
    
    public function toArray(): array {
        return [
            'field' => $this->field,
            'message' => $this->message,
            'code' => $this->code,
            'obstructions' => $this->obstructions
        ];
    }
}

// lib/Service/Validation/ValidationResult.php
class ValidationResult {
    private bool $isValid;
    private array $errors;
    
    public function __construct(bool $isValid = true, array $errors = []) {
        $this->isValid = $isValid;
        $this->errors = $errors;
    }
    
    public function isValid(): bool {
        return $this->isValid && empty($this->errors);
    }
    
    public function getErrors(): array {
        return array_map(function($error) {
            return $error instanceof ValidationError ? $error->toArray() : $error;
        }, $this->errors);
    }
    
    public function addError(ValidationError $error): void {
        $this->isValid = false;
        $this->errors[] = $error;
    }
}
```

---

## 8. Database Schema

### 8.1 Mutaties Tabel

```sql
CREATE TABLE IF NOT EXISTS oc_openregister_mutaties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_id VARCHAR(255) UNIQUE NOT NULL,
    mutation_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    reference_id VARCHAR(255),
    
    -- Verhuizing velden
    declarant_bsn VARCHAR(9),
    relocation_date DATE,
    new_postal_code VARCHAR(6),
    new_house_number VARCHAR(10),
    new_street VARCHAR(255),
    new_city VARCHAR(255),
    new_country VARCHAR(2) DEFAULT 'NL',
    relocator_bsn VARCHAR(9),
    relocator_relationship VARCHAR(50),
    
    -- Geboorte velden
    birth_date DATE,
    birth_place VARCHAR(255),
    first_names VARCHAR(255),
    last_name VARCHAR(255),
    gender VARCHAR(1),
    mother_bsn VARCHAR(9),
    father_bsn VARCHAR(9),
    
    -- Partnerschap velden
    partnership_date DATE,
    partnership_place VARCHAR(255),
    partner1_bsn VARCHAR(9),
    partner2_bsn VARCHAR(9),
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    
    INDEX idx_dossier_id (dossier_id),
    INDEX idx_status (status),
    INDEX idx_mutation_type (mutation_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 9. Test Strategie

### 9.1 Unit Tests

**Doel:** Test individuele componenten

**Tests:**
- ✅ SyntacticValidator tests
- ✅ SemanticValidator tests
- ✅ RvigValidator tests
- ✅ DataTransformationService tests
- ✅ BrpDatabaseService tests

### 9.2 Integration Tests

**Doel:** Test volledige mutatie flow

**Tests:**
- ✅ Verhuizing mutatie end-to-end
- ✅ Geboorte mutatie end-to-end
- ✅ Partnerschap mutatie end-to-end
- ✅ Error handling tests
- ✅ Validatie tests

### 9.3 Test Data

**Doel:** Realistische test scenarios

**Test Cases:**
- ✅ Geldige verhuizing
- ✅ Verhuizing met invalid BSN
- ✅ Verhuizing naar zelfde adres (moet falen)
- ✅ Geboorte met toekomstige datum (moet falen)
- ✅ Partnerschap tussen minderjarigen (moet falen)

---

## 10. Implementatie Tijdlijn

### Week 1: Validatie Service Uitbreiden
- **Dag 1-2:** Syntactische validatie completeren
- **Dag 3-4:** Semantische validatie completeren
- **Dag 5:** RVIG validatie implementeren (basis set)

### Week 2: Datatransformatie
- **Dag 1-2:** DataTransformationService bouwen
- **Dag 3-4:** Transformatie methodes per mutatie type
- **Dag 5:** Testen en debuggen

### Week 3: Mutatie Controllers
- **Dag 1-2:** BrpMutationController bouwen
- **Dag 3:** Database service uitbreiden
- **Dag 4-5:** Routes toevoegen en integratie testen

### Week 4: Afronding & Testen
- **Dag 1-2:** Error handling verbeteren
- **Dag 3-4:** Integration tests schrijven
- **Dag 5:** Documentatie en code review

**Totaal:** 3-4 weken

---

## 11. Risico's & Mitigatie

### Risico 1: Complexiteit RVIG-regels
**Risico:** RVIG-regels zijn zeer complex en kunnen onvolledig worden geïmplementeerd  
**Mitigatie:** Start met basis set regels, uitbreid iteratief

### Risico 2: Database Performance
**Risico:** Mutaties kunnen database performance beïnvloeden  
**Mitigatie:** Gebruik transacties, optimaliseer queries, gebruik indexes

### Risico 3: Data Consistentie
**Risico:** Mutaties kunnen data inconsistenties veroorzaken  
**Mitigatie:** Gebruik transacties, implementeer rollback mechanisme

---

## 12. Success Criteria

✅ **Functioneel:**
- Alle mutatie endpoints werken (POST/PUT/DELETE)
- Validatie werkt correct
- Datatransformatie werkt correct
- Error handling is gestructureerd

✅ **Technisch:**
- Code is getest (unit + integration)
- Performance is acceptabel
- Code is gedocumenteerd

✅ **Compliance:**
- Mutaties compliance: 20% → 35%
- Error responses zijn Haal Centraal-compliant
- API endpoints zijn gedocumenteerd

---

## 13. Volgende Stappen

1. ✅ **Start met Week 1:** Validatie Service Uitbreiden
2. ✅ **Review architectuur:** Bevestig dat architectuur klopt
3. ✅ **Setup test omgeving:** Zorg voor test database
4. ✅ **Begin implementatie:** Start met SyntacticValidator

---

**Status:** ✅ Klaar voor implementatie  
**Volgende Actie:** Start met Fase 1 (Validatie Service Uitbreiden)







