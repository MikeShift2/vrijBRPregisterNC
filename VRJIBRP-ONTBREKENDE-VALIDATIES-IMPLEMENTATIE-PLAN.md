# Implementatie Plan: Ontbrekende Validaties (Leeftijd, Huwelijk, Curatele)

## Overzicht

Dit plan beschrijft de concrete stappen om de drie ontbrekende validaties te implementeren in de vrijBRP Logica Service:

1. **Leeftijdscheck** - Is iemand ouder dan X jaar?
2. **Huwelijksstatus check** - Is iemand al getrouwd?
3. **Curatele check** - Is iemand onder curatele?

**Doel:** Deze validaties toevoegen aan de bestaande validatieservice structuur.

**Architectuur:** Validaties worden toegevoegd aan `SemanticValidator` en `BrpDatabaseService`.

---

## Fase 1: Database Service Uitbreidingen

### Doel
Database queries implementeren voor leeftijd, huwelijk en curatele checks.

### Stappen

#### Stap 1.1: Leeftijd Query Implementeren

**Bestand:** `lib/Service/Database/BrpDatabaseService.php`

**Nieuwe methode:**
```php
/**
 * Haal leeftijd op voor een BSN
 * 
 * @param string $bsn
 * @return int|null Leeftijd in jaren, of null als niet gevonden
 */
public function getAge(string $bsn): ?int {
    try {
        $stmt = $this->getConnection()->prepare("
            SELECT 
                EXTRACT(YEAR FROM AGE(
                    TO_DATE(inw.d_geboorte::text, 'YYYYMMDD')
                )) as leeftijd
            FROM probev.inw_ax inw
            WHERE inw.bsn = :bsn
            AND inw.ax = 'A'
            AND inw.hist = 'A'
            LIMIT 1
        ");
        $stmt->execute(['bsn' => $bsn]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['leeftijd'] : null;
    } catch (PDOException $e) {
        error_log("Error getting age: " . $e->getMessage());
        return null;
    }
}
```

**Tijd:** 30 minuten

**Test:** Test met bekende BSN's om leeftijd te verifiëren

---

#### Stap 1.2: Huwelijksstatus Query Implementeren

**Bestand:** `lib/Service/Database/BrpDatabaseService.php`

**Nieuwe methodes:**
```php
/**
 * Check of persoon actueel getrouwd is
 * 
 * @param string $bsn
 * @return bool True als persoon getrouwd is of geregistreerd partnerschap heeft
 */
public function isPersonMarried(string $bsn): bool {
    try {
        // Methode 1: Via huw_ax tabel (actuele huwelijken/partnerschappen)
        $stmt = $this->getConnection()->prepare("
            SELECT COUNT(*) as count
            FROM probev.huw_ax huw
            WHERE huw.pl_id = (
                SELECT pl_id FROM probev.inw_ax 
                WHERE bsn = :bsn 
                AND ax = 'A' 
                AND hist = 'A'
            )
            AND huw.ax = 'A'
            AND huw.hist = 'A'
            AND (huw.datum_ontbinding IS NULL OR huw.datum_ontbinding > CURRENT_DATE)
        ");
        $stmt->execute(['bsn' => $bsn]);
        $result = $stmt->fetch();
        
        if ($result && (int)$result['count'] > 0) {
            return true;
        }
        
        // Methode 2: Via PL tabel (burgerlijke staat)
        $stmt = $this->getConnection()->prepare("
            SELECT burgerlijke_staat
            FROM probev.pl
            WHERE bsn = :bsn
            AND overlijdensdatum IS NULL
        ");
        $stmt->execute(['bsn' => $bsn]);
        $result = $stmt->fetch();
        
        if ($result) {
            $maritalStatus = strtolower($result['burgerlijke_staat'] ?? '');
            return in_array($maritalStatus, ['gehuwd', 'geregistreerd_partnerschap', 'gps']);
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error checking marriage status: " . $e->getMessage());
        return false;
    }
}

/**
 * Haal huwelijksinfo op
 * 
 * @param string $bsn
 * @return array|null Array met huwelijksinfo, of null als niet gevonden
 */
public function getMarriageInfo(string $bsn): ?array {
    try {
        $stmt = $this->getConnection()->prepare("
            SELECT 
                huw.datum_huwelijk,
                huw.datum_ontbinding,
                huw.soort,
                huw.pl_id_partner
            FROM probev.huw_ax huw
            WHERE huw.pl_id = (
                SELECT pl_id FROM probev.inw_ax 
                WHERE bsn = :bsn 
                AND ax = 'A' 
                AND hist = 'A'
            )
            AND huw.ax = 'A'
            AND huw.hist = 'A'
            AND (huw.datum_ontbinding IS NULL OR huw.datum_ontbinding > CURRENT_DATE)
            ORDER BY huw.datum_huwelijk DESC
            LIMIT 1
        ");
        $stmt->execute(['bsn' => $bsn]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Error getting marriage info: " . $e->getMessage());
        return null;
    }
}
```

**Tijd:** 1 uur

**Test:** Test met getrouwde en ongehuwde personen

---

#### Stap 1.3: Curatele Query Implementeren

**Bestand:** `lib/Service/Database/BrpDatabaseService.php`

**Nieuwe methodes:**
```php
/**
 * Check of persoon onder curatele staat
 * 
 * @param string $bsn
 * @return bool True als persoon onder curatele, bewind of mentorschap staat
 */
public function isPersonUnderCuratorship(string $bsn): bool {
    try {
        // Methode 1: Via gezag_ax tabel
        $stmt = $this->getConnection()->prepare("
            SELECT COUNT(*) as count
            FROM probev.gezag_ax gezag
            WHERE gezag.pl_id = (
                SELECT pl_id FROM probev.inw_ax 
                WHERE bsn = :bsn 
                AND ax = 'A' 
                AND hist = 'A'
            )
            AND gezag.ax = 'A'
            AND gezag.hist = 'A'
            AND (gezag.datum_einde IS NULL OR gezag.datum_einde > CURRENT_DATE)
            AND gezag.soort_gezag IN ('curatele', 'bewind', 'mentorschap')
        ");
        $stmt->execute(['bsn' => $bsn]);
        $result = $stmt->fetch();
        
        if ($result && (int)$result['count'] > 0) {
            return true;
        }
        
        // Methode 2: Via PL tabel (als curatele indicator bestaat)
        $stmt = $this->getConnection()->prepare("
            SELECT 
                CASE 
                    WHEN curatele_indicator = true THEN true
                    WHEN datum_curatele_begin IS NOT NULL 
                        AND (datum_curatele_einde IS NULL OR datum_curatele_einde > CURRENT_DATE) 
                    THEN true
                    ELSE false
                END as is_under_curatorship
            FROM probev.pl
            WHERE bsn = :bsn
            AND overlijdensdatum IS NULL
        ");
        $stmt->execute(['bsn' => $bsn]);
        $result = $stmt->fetch();
        
        return $result && ($result['is_under_curatorship'] === true || $result['is_under_curatorship'] === 't');
    } catch (PDOException $e) {
        error_log("Error checking curatorship: " . $e->getMessage());
        return false;
    }
}

/**
 * Haal curatele info op
 * 
 * @param string $bsn
 * @return array|null Array met curatele info, of null als niet gevonden
 */
public function getCuratorshipInfo(string $bsn): ?array {
    try {
        $stmt = $this->getConnection()->prepare("
            SELECT 
                gezag.soort_gezag,
                gezag.datum_begin,
                gezag.datum_einde,
                gezag.pl_id_curator
            FROM probev.gezag_ax gezag
            WHERE gezag.pl_id = (
                SELECT pl_id FROM probev.inw_ax 
                WHERE bsn = :bsn 
                AND ax = 'A' 
                AND hist = 'A'
            )
            AND gezag.ax = 'A'
            AND gezag.hist = 'A'
            AND (gezag.datum_einde IS NULL OR gezag.datum_einde > CURRENT_DATE)
            AND gezag.soort_gezag IN ('curatele', 'bewind', 'mentorschap')
            ORDER BY gezag.datum_begin DESC
            LIMIT 1
        ");
        $stmt->execute(['bsn' => $bsn]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log("Error getting curatorship info: " . $e->getMessage());
        return null;
    }
}
```

**Tijd:** 1 uur

**Test:** Test met personen onder curatele (indien beschikbaar in testdata)

---

### Fase 1 Deliverables

- ✅ `getAge()` methode geïmplementeerd
- ✅ `isPersonMarried()` methode geïmplementeerd
- ✅ `getMarriageInfo()` methode geïmplementeerd
- ✅ `isPersonUnderCuratorship()` methode geïmplementeerd
- ✅ `getCuratorshipInfo()` methode geïmplementeerd
- ✅ Database queries getest

**Totaal tijd:** ~2.5 uur

---

## Fase 2: Semantic Validator Uitbreidingen

### Doel
Validatie methodes toevoegen aan SemanticValidator die gebruik maken van de database service.

### Stappen

#### Stap 2.1: Leeftijdsvalidaties Implementeren

**Bestand:** `lib/Service/Validation/SemanticValidator.php`

**Nieuwe methodes:**
```php
/**
 * Valideer of persoon minimum leeftijd heeft
 * 
 * @param string $bsn
 * @param int $minimumAge Minimum leeftijd in jaren
 * @param string $fieldName Veldnaam voor error (default: 'bsn')
 * @return ValidationError|null Error als leeftijd niet gehaald wordt
 */
public function validateMinimumAge(
    string $bsn,
    int $minimumAge,
    string $fieldName = 'bsn'
): ?ValidationError {
    $age = $this->dbService->getAge($bsn);
    
    if ($age === null) {
        return new ValidationError(
            $fieldName,
            'Birth date not available or person not found',
            'BIRTH_DATE_MISSING'
        );
    }
    
    if ($age < $minimumAge) {
        return new ValidationError(
            $fieldName,
            "Person must be at least $minimumAge years old (current age: $age)",
            'MINIMUM_AGE_NOT_MET',
            ['minimumAge' => $minimumAge, 'currentAge' => $age]
        );
    }
    
    return null;
}

/**
 * Valideer of persoon maximum leeftijd heeft
 */
public function validateMaximumAge(
    string $bsn,
    int $maximumAge,
    string $fieldName = 'bsn'
): ?ValidationError {
    $age = $this->dbService->getAge($bsn);
    
    if ($age === null) {
        return new ValidationError(
            $fieldName,
            'Birth date not available or person not found',
            'BIRTH_DATE_MISSING'
        );
    }
    
    if ($age > $maximumAge) {
        return new ValidationError(
            $fieldName,
            "Person must be at most $maximumAge years old (current age: $age)",
            'MAXIMUM_AGE_EXCEEDED',
            ['maximumAge' => $maximumAge, 'currentAge' => $age]
        );
    }
    
    return null;
}

/**
 * Valideer leeftijdsbereik
 */
public function validateAgeRange(
    string $bsn,
    int $minimumAge,
    int $maximumAge,
    string $fieldName = 'bsn'
): ?ValidationError {
    // Check minimum
    $minError = $this->validateMinimumAge($bsn, $minimumAge, $fieldName);
    if ($minError !== null) {
        return $minError;
    }
    
    // Check maximum
    $maxError = $this->validateMaximumAge($bsn, $maximumAge, $fieldName);
    if ($maxError !== null) {
        return $maxError;
    }
    
    return null;
}
```

**Tijd:** 1 uur

**Test:** Unit tests voor verschillende leeftijden

---

#### Stap 2.2: Huwelijksstatus Validaties Implementeren

**Bestand:** `lib/Service/Validation/SemanticValidator.php`

**Nieuwe methodes:**
```php
/**
 * Valideer of persoon NIET getrouwd is
 * 
 * @param string $bsn
 * @param string $fieldName Veldnaam voor error (default: 'bsn')
 * @return ValidationError|null Error als persoon al getrouwd is
 */
public function validateNotMarried(
    string $bsn,
    string $fieldName = 'bsn'
): ?ValidationError {
    $isMarried = $this->dbService->isPersonMarried($bsn);
    
    if ($isMarried) {
        return new ValidationError(
            $fieldName,
            'Person is already married or in a registered partnership',
            'PERSON_ALREADY_MARRIED',
            ['maritalStatus' => 'married']
        );
    }
    
    return null;
}

/**
 * Valideer of persoon WEL getrouwd is
 * 
 * @param string $bsn
 * @param string $fieldName Veldnaam voor error (default: 'bsn')
 * @return ValidationError|null Error als persoon niet getrouwd is
 */
public function validateIsMarried(
    string $bsn,
    string $fieldName = 'bsn'
): ?ValidationError {
    $isMarried = $this->dbService->isPersonMarried($bsn);
    
    if (!$isMarried) {
        return new ValidationError(
            $fieldName,
            'Person is not married or in a registered partnership',
            'PERSON_NOT_MARRIED',
            ['maritalStatus' => 'not_married']
        );
    }
    
    return null;
}
```

**Tijd:** 30 minuten

**Test:** Unit tests voor getrouwde en ongehuwde personen

---

#### Stap 2.3: Curatele Validaties Implementeren

**Bestand:** `lib/Service/Validation/SemanticValidator.php`

**Nieuwe methodes:**
```php
/**
 * Valideer of persoon onder curatele staat
 * 
 * @param string $bsn
 * @param string $fieldName Veldnaam voor error (default: 'bsn')
 * @return ValidationError|null Error als persoon onder curatele staat
 */
public function validateUnderCuratorship(
    string $bsn,
    string $fieldName = 'bsn'
): ?ValidationError {
    $isUnderCuratorship = $this->dbService->isPersonUnderCuratorship($bsn);
    
    if ($isUnderCuratorship) {
        return new ValidationError(
            $fieldName,
            'Person is under curatorship (curatele)',
            'PERSON_UNDER_CURATORSHIP',
            ['curatorshipType' => 'curatele']
        );
    }
    
    return null;
}

/**
 * Valideer of persoon NIET onder curatele staat
 * 
 * @param string $bsn
 * @param string $fieldName Veldnaam voor error (default: 'bsn')
 * @return ValidationError|null Error als persoon onder curatele staat
 */
public function validateNotUnderCuratorship(
    string $bsn,
    string $fieldName = 'bsn'
): ?ValidationError {
    $isUnderCuratorship = $this->dbService->isPersonUnderCuratorship($bsn);
    
    if ($isUnderCuratorship) {
        return new ValidationError(
            $fieldName,
            'Person must not be under curatorship (curatele)',
            'PERSON_UNDER_CURATORSHIP',
            ['curatorshipType' => 'curatele']
        );
    }
    
    return null;
}
```

**Tijd:** 30 minuten

**Test:** Unit tests voor personen onder curatele

---

### Fase 2 Deliverables

- ✅ `validateMinimumAge()` geïmplementeerd
- ✅ `validateMaximumAge()` geïmplementeerd
- ✅ `validateAgeRange()` geïmplementeerd
- ✅ `validateNotMarried()` geïmplementeerd
- ✅ `validateIsMarried()` geïmplementeerd
- ✅ `validateUnderCuratorship()` geïmplementeerd
- ✅ `validateNotUnderCuratorship()` geïmplementeerd

**Totaal tijd:** ~2 uur

---

## Fase 3: Integratie in Validatie Service

### Doel
Nieuwe validaties integreren in bestaande mutatie validaties.

### Stappen

#### Stap 3.1: Verhuizing Validatie Uitbreiden

**Bestand:** `lib/Service/Validation/VrijBrpValidationService.php`

**Aanpassing aan `validateRelocation()`:**
```php
public function validateRelocation(array $request): ValidationResult {
    $errors = [];
    
    // ... bestaande syntactische validaties ...
    
    // Semantische validaties
    $declarantBsn = $request['declarant']['bsn'] ?? null;
    
    if ($declarantBsn) {
        // Bestaande validaties
        $existsError = $this->semanticValidator->validateBsnExists($declarantBsn, 'declarant.bsn');
        if ($existsError !== null) {
            $errors[] = $existsError;
        }
        
        $blockedError = $this->semanticValidator->validateBsnNotBlocked($declarantBsn, 'declarant.bsn');
        if ($blockedError !== null) {
            $errors[] = $blockedError;
        }
        
        // NIEUWE VALIDATIES:
        
        // 1. Leeftijdscheck (bijv. minimum 18 jaar)
        $ageError = $this->semanticValidator->validateMinimumAge(
            $declarantBsn,
            18,
            'declarant.bsn'
        );
        if ($ageError !== null) {
            $errors[] = $ageError;
        }
        
        // 2. Check of declarant niet onder curatele staat
        $curatorshipError = $this->semanticValidator->validateNotUnderCuratorship(
            $declarantBsn,
            'declarant.bsn'
        );
        if ($curatorshipError !== null) {
            $errors[] = $curatorshipError;
        }
    }
    
    // ... rest van validaties ...
    
    return new ValidationResult(empty($errors), $errors);
}
```

**Tijd:** 30 minuten

---

#### Stap 3.2: Partnerschap Validatie Uitbreiden

**Bestand:** `lib/Service/Validation/VrijBrpValidationService.php`

**Aanpassing aan `validateCommitment()`:**
```php
public function validateCommitment(array $request): ValidationResult {
    $errors = [];
    
    // ... bestaande validaties ...
    
    // NIEUWE VALIDATIES:
    
    // Check of partners niet al getrouwd zijn
    if (isset($request['partner1']['bsn'])) {
        $marriedError = $this->semanticValidator->validateNotMarried(
            $request['partner1']['bsn'],
            'partner1.bsn'
        );
        if ($marriedError !== null) {
            $errors[] = $marriedError;
        }
    }
    
    if (isset($request['partner2']['bsn'])) {
        $marriedError = $this->semanticValidator->validateNotMarried(
            $request['partner2']['bsn'],
            'partner2.bsn'
        );
        if ($marriedError !== null) {
            $errors[] = $marriedError;
        }
    }
    
    // Optioneel: Leeftijdscheck voor partners (bijv. minimum 18 jaar)
    if (isset($request['partner1']['bsn'])) {
        $ageError = $this->semanticValidator->validateMinimumAge(
            $request['partner1']['bsn'],
            18,
            'partner1.bsn'
        );
        if ($ageError !== null) {
            $errors[] = $ageError;
        }
    }
    
    if (isset($request['partner2']['bsn'])) {
        $ageError = $this->semanticValidator->validateMinimumAge(
            $request['partner2']['bsn'],
            18,
            'partner2.bsn'
        );
        if ($ageError !== null) {
            $errors[] = $ageError;
        }
    }
    
    // ... rest van validaties ...
    
    return new ValidationResult(empty($errors), $errors);
}
```

**Tijd:** 30 minuten

---

#### Stap 3.3: Geboorte Validatie Uitbreiden

**Bestand:** `lib/Service/Validation/VrijBrpValidationService.php`

**Aanpassing aan `validateBirth()`:**
```php
public function validateBirth(array $request): ValidationResult {
    $errors = [];
    
    // ... bestaande validaties ...
    
    // NIEUWE VALIDATIES:
    
    // Leeftijdscheck voor ouders (bijv. minimum 16 jaar)
    if (isset($request['mother']['bsn'])) {
        $ageError = $this->semanticValidator->validateMinimumAge(
            $request['mother']['bsn'],
            16,  // Minimum leeftijd voor moeder
            'mother.bsn'
        );
        if ($ageError !== null) {
            $errors[] = $ageError;
        }
    }
    
    if (isset($request['father']['bsn'])) {
        $ageError = $this->semanticValidator->validateMinimumAge(
            $request['father']['bsn'],
            16,  // Minimum leeftijd voor vader
            'father.bsn'
        );
        if ($ageError !== null) {
            $errors[] = $ageError;
        }
    }
    
    // ... rest van validaties ...
    
    return new ValidationResult(empty($errors), $errors);
}
```

**Tijd:** 30 minuten

---

### Fase 3 Deliverables

- ✅ Verhuizing validatie uitgebreid met leeftijd en curatele check
- ✅ Partnerschap validatie uitgebreid met huwelijksstatus en leeftijd check
- ✅ Geboorte validatie uitgebreid met leeftijd check voor ouders

**Totaal tijd:** ~1.5 uur

---

## Fase 4: Testing

### Doel
Unit tests en integratietests schrijven voor nieuwe validaties.

### Stappen

#### Stap 4.1: Unit Tests voor Database Service

**Bestand:** `tests/Unit/Service/Database/BrpDatabaseServiceTest.php`

**Test cases:**
```php
class BrpDatabaseServiceTest extends TestCase {
    public function testGetAge() {
        $service = new BrpDatabaseService();
        
        // Test met bekende BSN
        $age = $service->getAge('168149291');
        $this->assertIsInt($age);
        $this->assertGreaterThan(0, $age);
    }
    
    public function testIsPersonMarried() {
        $service = new BrpDatabaseService();
        
        // Test met getrouwde persoon
        $isMarried = $service->isPersonMarried('168149291');
        $this->assertIsBool($isMarried);
    }
    
    public function testIsPersonUnderCuratorship() {
        $service = new BrpDatabaseService();
        
        // Test met persoon onder curatele (indien beschikbaar)
        $isUnderCuratorship = $service->isPersonUnderCuratorship('168149291');
        $this->assertIsBool($isUnderCuratorship);
    }
}
```

**Tijd:** 1 uur

---

#### Stap 4.2: Unit Tests voor Semantic Validator

**Bestand:** `tests/Unit/Service/Validation/SemanticValidatorTest.php`

**Test cases:**
```php
class SemanticValidatorTest extends TestCase {
    public function testValidateMinimumAge() {
        $dbService = $this->createMock(BrpDatabaseService::class);
        $dbService->method('getAge')->willReturn(20);
        
        $validator = new SemanticValidator($dbService);
        
        // Test: leeftijd 20, minimum 18 → OK
        $error = $validator->validateMinimumAge('123456789', 18);
        $this->assertNull($error);
        
        // Test: leeftijd 20, minimum 21 → Error
        $error = $validator->validateMinimumAge('123456789', 21);
        $this->assertNotNull($error);
        $this->assertEquals('MINIMUM_AGE_NOT_MET', $error->getCode());
    }
    
    public function testValidateNotMarried() {
        $dbService = $this->createMock(BrpDatabaseService::class);
        $dbService->method('isPersonMarried')->willReturn(false);
        
        $validator = new SemanticValidator($dbService);
        
        // Test: niet getrouwd → OK
        $error = $validator->validateNotMarried('123456789');
        $this->assertNull($error);
        
        // Test: wel getrouwd → Error
        $dbService->method('isPersonMarried')->willReturn(true);
        $error = $validator->validateNotMarried('123456789');
        $this->assertNotNull($error);
        $this->assertEquals('PERSON_ALREADY_MARRIED', $error->getCode());
    }
    
    public function testValidateNotUnderCuratorship() {
        $dbService = $this->createMock(BrpDatabaseService::class);
        $dbService->method('isPersonUnderCuratorship')->willReturn(false);
        
        $validator = new SemanticValidator($dbService);
        
        // Test: niet onder curatele → OK
        $error = $validator->validateNotUnderCuratorship('123456789');
        $this->assertNull($error);
        
        // Test: wel onder curatele → Error
        $dbService->method('isPersonUnderCuratorship')->willReturn(true);
        $error = $validator->validateNotUnderCuratorship('123456789');
        $this->assertNotNull($error);
        $this->assertEquals('PERSON_UNDER_CURATORSHIP', $error->getCode());
    }
}
```

**Tijd:** 1.5 uur

---

#### Stap 4.3: Integratie Tests

**Bestand:** `tests/Integration/VrijBrpValidationServiceTest.php`

**Test cases:**
```php
class VrijBrpValidationServiceTest extends TestCase {
    public function testValidateRelocationWithAgeCheck() {
        $service = $this->getValidationService();
        
        // Test: verhuizing met persoon onder 18 jaar
        $request = [
            'declarant' => ['bsn' => '123456789'], // Persoon onder 18
            'newAddress' => [...]
        ];
        
        $result = $service->validateRelocation($request);
        
        // Moet error geven voor leeftijd
        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        
        $ageError = array_filter($errors, function($error) {
            return $error->getCode() === 'MINIMUM_AGE_NOT_MET';
        });
        $this->assertNotEmpty($ageError);
    }
    
    public function testValidateCommitmentWithMarriageCheck() {
        $service = $this->getValidationService();
        
        // Test: partnerschap met al getrouwde persoon
        $request = [
            'partner1' => ['bsn' => '123456789'], // Al getrouwd
            'partner2' => ['bsn' => '987654321'],
            'commitmentDate' => '2024-01-01'
        ];
        
        $result = $service->validateCommitment($request);
        
        // Moet error geven voor huwelijksstatus
        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $marriageError = array_filter($errors, function($error) {
            return $error->getCode() === 'PERSON_ALREADY_MARRIED';
        });
        $this->assertNotEmpty($marriageError);
    }
}
```

**Tijd:** 1.5 uur

---

### Fase 4 Deliverables

- ✅ Unit tests voor database service
- ✅ Unit tests voor semantic validator
- ✅ Integratie tests voor validatie service
- ✅ Alle tests slagen

**Totaal tijd:** ~4 uur

---

## Implementatie Tijdlijn

### Week 1: Database & Validator Implementatie

**Dag 1:**
- Fase 1: Database service uitbreidingen (2.5 uur)
- Fase 2: Semantic validator uitbreidingen (2 uur)

**Deliverables:**
- Database queries werkend
- Validatie methodes geïmplementeerd

---

### Week 2: Integratie & Testing

**Dag 2:**
- Fase 3: Integratie in validatie service (1.5 uur)
- Fase 4: Testing (4 uur)

**Deliverables:**
- Validaties geïntegreerd in mutatie flows
- Tests geschreven en werkend

---

## Success Criteria

### Functioneel

- ✅ Leeftijdscheck werkt voor minimum leeftijd
- ✅ Leeftijdscheck werkt voor maximum leeftijd
- ✅ Leeftijdscheck werkt voor leeftijdsbereik
- ✅ Huwelijksstatus check werkt (getrouwd/niet getrouwd)
- ✅ Curatele check werkt (onder curatele/niet onder curatele)
- ✅ Validaties geïntegreerd in verhuizing, partnerschap, geboorte

### Technisch

- ✅ Database queries geoptimaliseerd
- ✅ Error responses conform vrijBRP Dossiers API
- ✅ Code volgt bestaande patterns
- ✅ Unit tests geschreven
- ✅ Integratie tests geschreven

### Performance

- ✅ Database queries < 100ms (p95)
- ✅ Validatie overhead < 50ms per check

---

## Risico's & Mitigaties

### Risico 1: Database Veldnamen Onbekend

**Risico:** Exacte veldnamen in `probev` schema kunnen afwijken

**Mitigatie:**
- Test queries eerst met bekende BSN's
- Check database schema documentatie
- Gebruik fallback queries indien nodig

---

### Risico 2: Curatele Data Niet Beschikbaar

**Risico:** Testdata bevat mogelijk geen personen onder curatele

**Mitigatie:**
- Test met mock data
- Documenteer dat curatele check alleen werkt als data beschikbaar is
- Overweeg testdata aan te maken

---

### Risico 3: Leeftijd Berekenen Complex

**Risico:** Datum conversie (JJJJMMDD) kan fouten bevatten

**Mitigatie:**
- Test uitgebreid met verschillende datum formaten
- Gebruik PostgreSQL AGE() functie voor betrouwbaarheid
- Valideer dat geboortedatum niet in toekomst ligt

---

## Volgende Stappen

### Directe Acties

1. **Start Fase 1** - Implementeer database service methodes
2. **Test database queries** - Verifieer met echte data
3. **Implementeer validators** - Voeg validatie methodes toe
4. **Integreer in service** - Voeg toe aan mutatie validaties
5. **Schrijf tests** - Test alle nieuwe functionaliteit

### Afhankelijkheden

- ✅ PostgreSQL database moet draaien
- ✅ `probev` schema moet beschikbaar zijn
- ✅ Testdata moet beschikbaar zijn (voor testing)
- ✅ Bestaande validatie service structuur moet bestaan

---

## Conclusie

Dit plan beschrijft een gefaseerde implementatie van drie ontbrekende validaties:

1. **Leeftijdscheck** - Via `getAge()` en leeftijdsvalidatie methodes
2. **Huwelijksstatus** - Via `isPersonMarried()` en huwelijksvalidatie methodes
3. **Curatele check** - Via `isPersonUnderCuratorship()` en curatele validatie methodes

**Totale geschatte tijd:** ~10 uur

**Prioriteit:** Hoog - Deze validaties zijn essentieel voor correcte BRP-processen.

---

## Referenties

- [VRJIBRP-ONTBREKENDE-VALIDATIES.md](./VRJIBRP-ONTBREKENDE-VALIDATIES.md) - Technische details
- [VRJIBRP-LOGICA-SERVICE-IMPLEMENTATIE-PLAN.md](./VRJIBRP-LOGICA-SERVICE-IMPLEMENTATIE-PLAN.md) - Hoofd implementatie plan
- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md) - Alle validaties overzicht







