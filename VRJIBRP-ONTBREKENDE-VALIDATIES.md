# Ontbrekende Validaties: Leeftijd, Huwelijk, Curatele

## Overzicht

Dit document beschrijft de ontbrekende validaties die moeten worden toegevoegd aan de vrijBRP Logica Service:

1. **Leeftijdscheck** - Is iemand ouder dan X jaar?
2. **Huwelijksstatus check** - Is iemand al getrouwd?
3. **Curatele check** - Is iemand onder curatele?

---

## 1. Leeftijdscheck Validatie

### Doel
Controleren of een persoon een bepaalde minimum- of maximumleeftijd heeft.

### Use Cases
- **Minimumleeftijd:** Bijvoorbeeld 18 jaar voor bepaalde handelingen
- **Maximumleeftijd:** Bijvoorbeeld 65 jaar voor bepaalde processen
- **Leeftijdsbereik:** Bijvoorbeeld tussen 18 en 65 jaar

### Database Query

**Bereken leeftijd uit geboortedatum:**
```sql
-- Haal geboortedatum op
SELECT 
    pl_id,
    bsn,
    d_geboorte,  -- Geboortedatum in formaat JJJJMMDD (integer)
    -- Bereken leeftijd
    EXTRACT(YEAR FROM AGE(
        TO_DATE(d_geboorte::text, 'YYYYMMDD')
    )) as leeftijd
FROM probev.inw_ax
WHERE bsn = :bsn
AND ax = 'A'
AND hist = 'A';
```

**Of via PL tabel:**
```sql
SELECT 
    pl.pl_id,
    pl.bsn,
    pl.overlijdensdatum,
    -- Geboortedatum uit inw_ax
    inw.d_geboorte,
    -- Bereken leeftijd
    EXTRACT(YEAR FROM AGE(
        TO_DATE(inw.d_geboorte::text, 'YYYYMMDD')
    )) as leeftijd
FROM probev.pl
JOIN probev.inw_ax inw ON inw.pl_id = pl.pl_id
WHERE pl.bsn = :bsn
AND inw.ax = 'A'
AND inw.hist = 'A';
```

### Implementatie

**Nieuwe methode in SemanticValidator:**
```php
/**
 * Valideer of persoon minimum leeftijd heeft
 */
public function validateMinimumAge(
    string $bsn,
    int $minimumAge,
    string $fieldName = 'bsn'
): ?ValidationError {
    $person = $this->dbService->findPersonByBsn($bsn);
    
    if ($person === null) {
        return new ValidationError(
            $fieldName,
            'Person not found',
            'PERSON_NOT_FOUND'
        );
    }
    
    $birthDate = $person['d_geboorte'] ?? null;
    if ($birthDate === null) {
        return new ValidationError(
            $fieldName,
            'Birth date not available',
            'BIRTH_DATE_MISSING'
        );
    }
    
    // Converteer JJJJMMDD naar DateTime
    $birthDateStr = (string)$birthDate;
    $year = substr($birthDateStr, 0, 4);
    $month = substr($birthDateStr, 4, 2);
    $day = substr($birthDateStr, 6, 2);
    $birthDateTime = new DateTime("$year-$month-$day");
    
    // Bereken leeftijd
    $today = new DateTime();
    $age = $today->diff($birthDateTime)->y;
    
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
    $person = $this->dbService->findPersonByBsn($bsn);
    
    if ($person === null) {
        return new ValidationError(
            $fieldName,
            'Person not found',
            'PERSON_NOT_FOUND'
        );
    }
    
    $birthDate = $person['d_geboorte'] ?? null;
    if ($birthDate === null) {
        return new ValidationError(
            $fieldName,
            'Birth date not available',
            'BIRTH_DATE_MISSING'
        );
    }
    
    // Bereken leeftijd
    $birthDateStr = (string)$birthDate;
    $year = substr($birthDateStr, 0, 4);
    $month = substr($birthDateStr, 4, 2);
    $day = substr($birthDateStr, 6, 2);
    $birthDateTime = new DateTime("$year-$month-$day");
    
    $today = new DateTime();
    $age = $today->diff($birthDateTime)->y;
    
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

### Database Service Uitbreiding

**Nieuwe methode in BrpDatabaseService:**
```php
/**
 * Haal leeftijd op voor een BSN
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

### Error Response Voorbeeld

```json
{
    "field": "declarant.bsn",
    "message": "Person must be at least 18 years old (current age: 16)",
    "code": "MINIMUM_AGE_NOT_MET",
    "obstructions": {
        "minimumAge": 18,
        "currentAge": 16
    }
}
```

---

## 2. Huwelijksstatus Check

### Doel
Controleren of een persoon al getrouwd is of een geregistreerd partnerschap heeft.

### Status in VRJIBRP-ALLE-VALIDATIES.md

**✅ Al genoemd:** Regel 501 - "Partners mogen niet al getrouwd zijn"

**⚠️ Maar:** Geen expliciete implementatie beschreven

### Database Query

**Check actueel huwelijk/partnerschap:**
```sql
-- Check of persoon actueel getrouwd is
SELECT 
    huw.pl_id,
    huw.datum_huwelijk,
    huw.datum_ontbinding,
    huw.soort,  -- 'H' = Huwelijk, 'G' = Geregistreerd Partnerschap
    CASE 
        WHEN huw.datum_ontbinding IS NULL THEN true
        WHEN huw.datum_ontbinding > CURRENT_DATE THEN true
        ELSE false
    END as is_actueel
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
LIMIT 1;
```

**Of via PL tabel (burgerlijke staat):**
```sql
SELECT 
    pl.bsn,
    pl.burgerlijke_staat,  -- Bijv. 'gehuwd', 'geregistreerd_partnerschap', 'ongehuwd', etc.
    pl.datum_huwelijk,
    pl.datum_ontbinding
FROM probev.pl
WHERE pl.bsn = :bsn
AND pl.overlijdensdatum IS NULL;
```

### Implementatie

**Nieuwe methode in SemanticValidator:**
```php
/**
 * Valideer of persoon al getrouwd is
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
 * Valideer of persoon getrouwd is (voor processen die vereisen dat iemand getrouwd is)
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

### Database Service Uitbreiding

**Nieuwe methode in BrpDatabaseService:**
```php
/**
 * Check of persoon actueel getrouwd is
 */
public function isPersonMarried(string $bsn): bool {
    try {
        // Methode 1: Via huw_ax tabel
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

### Error Response Voorbeeld

```json
{
    "field": "partner1.bsn",
    "message": "Person is already married or in a registered partnership",
    "code": "PERSON_ALREADY_MARRIED",
    "obstructions": {
        "maritalStatus": "married"
    }
}
```

---

## 3. Curatele Check

### Doel
Controleren of een persoon onder curatele staat.

### Database Query

**Check curatele via gezag_ax tabel:**
```sql
-- Check of persoon onder curatele staat
SELECT 
    gezag.pl_id,
    gezag.soort_gezag,  -- Bijv. 'curatele', 'bewind', 'mentorschap'
    gezag.datum_begin,
    gezag.datum_einde,
    CASE 
        WHEN gezag.datum_einde IS NULL THEN true
        WHEN gezag.datum_einde > CURRENT_DATE THEN true
        ELSE false
    END as is_actueel
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
LIMIT 1;
```

**Of check via PL tabel:**
```sql
SELECT 
    pl.bsn,
    pl.curatele_indicator,  -- Als dit veld bestaat
    pl.datum_curatele_begin,
    pl.datum_curatele_einde
FROM probev.pl
WHERE pl.bsn = :bsn
AND pl.overlijdensdatum IS NULL
AND (
    pl.curatele_indicator = true
    OR (pl.datum_curatele_begin IS NOT NULL 
        AND (pl.datum_curatele_einde IS NULL OR pl.datum_curatele_einde > CURRENT_DATE))
);
```

### Implementatie

**Nieuwe methode in SemanticValidator:**
```php
/**
 * Valideer of persoon onder curatele staat
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
 * Valideer of persoon NIET onder curatele staat (voor processen die vereisen dat iemand handelingsbekwaam is)
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

### Database Service Uitbreiding

**Nieuwe methode in BrpDatabaseService:**
```php
/**
 * Check of persoon onder curatele staat
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

### Error Response Voorbeeld

```json
{
    "field": "declarant.bsn",
    "message": "Person is under curatorship (curatele)",
    "code": "PERSON_UNDER_CURATORSHIP",
    "obstructions": {
        "curatorshipType": "curatele"
    }
}
```

---

## 4. Integratie in Validatie Service

### Toevoegen aan VrijBrpValidationService

**Voorbeeld voor verhuizing validatie:**
```php
public function validateRelocation(array $request): ValidationResult {
    $errors = [];
    
    // ... bestaande validaties ...
    
    // Nieuwe validaties toevoegen:
    
    // 1. Leeftijdscheck (bijv. minimum 18 jaar)
    if (isset($request['declarant']['bsn'])) {
        $ageError = $this->semanticValidator->validateMinimumAge(
            $request['declarant']['bsn'],
            18,
            'declarant.bsn'
        );
        if ($ageError !== null) {
            $errors[] = $ageError;
        }
    }
    
    // 2. Check of declarant niet onder curatele staat
    if (isset($request['declarant']['bsn'])) {
        $curatorshipError = $this->semanticValidator->validateNotUnderCuratorship(
            $request['declarant']['bsn'],
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

**Voorbeeld voor partnerschap validatie:**
```php
public function validateCommitment(array $request): ValidationResult {
    $errors = [];
    
    // ... bestaande validaties ...
    
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
    
    // ... rest van validaties ...
    
    return new ValidationResult(empty($errors), $errors);
}
```

---

## 5. Implementatie Checklist

### Database Service

- [ ] `getAge(string $bsn): ?int` - Haal leeftijd op
- [ ] `isPersonMarried(string $bsn): bool` - Check huwelijksstatus
- [ ] `getMarriageInfo(string $bsn): ?array` - Haal huwelijksinfo op
- [ ] `isPersonUnderCuratorship(string $bsn): bool` - Check curatele
- [ ] `getCuratorshipInfo(string $bsn): ?array` - Haal curatele info op

### Semantic Validator

- [ ] `validateMinimumAge(string $bsn, int $minimumAge, string $fieldName): ?ValidationError`
- [ ] `validateMaximumAge(string $bsn, int $maximumAge, string $fieldName): ?ValidationError`
- [ ] `validateAgeRange(string $bsn, int $min, int $max, string $fieldName): ?ValidationError`
- [ ] `validateNotMarried(string $bsn, string $fieldName): ?ValidationError`
- [ ] `validateIsMarried(string $bsn, string $fieldName): ?ValidationError`
- [ ] `validateUnderCuratorship(string $bsn, string $fieldName): ?ValidationError`
- [ ] `validateNotUnderCuratorship(string $bsn, string $fieldName): ?ValidationError`

### Validatie Service Integratie

- [ ] Leeftijdscheck toevoegen aan `validateRelocation()`
- [ ] Leeftijdscheck toevoegen aan `validateBirth()` (bijv. ouders moeten minimaal X jaar zijn)
- [ ] Curatele check toevoegen aan `validateRelocation()`
- [ ] Huwelijksstatus check toevoegen aan `validateCommitment()`
- [ ] Huwelijksstatus check toevoegen aan andere mutaties waar nodig

### Testing

- [ ] Unit tests voor leeftijdscheck
- [ ] Unit tests voor huwelijksstatus check
- [ ] Unit tests voor curatele check
- [ ] Integratie tests met echte database

---

## 6. Database Veld Mapping

### probev Schema Velden

**Geboortedatum:**
- `inw_ax.d_geboorte` - Formaat: `JJJJMMDD` (integer)

**Huwelijk:**
- `huw_ax.datum_huwelijk` - Huwelijksdatum
- `huw_ax.datum_ontbinding` - Ontbindingsdatum
- `huw_ax.soort` - 'H' = Huwelijk, 'G' = Geregistreerd Partnerschap
- `pl.burgerlijke_staat` - Burgerlijke staat (tekst)

**Curatele:**
- `gezag_ax.soort_gezag` - 'curatele', 'bewind', 'mentorschap'
- `gezag_ax.datum_begin` - Begin datum
- `gezag_ax.datum_einde` - Eind datum
- `pl.curatele_indicator` - Boolean (als dit veld bestaat)

---

## 7. Referenties

- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md) - Regel 501: "Partners mogen niet al getrouwd zijn"
- [OPENREGISTER-BEVAX-CONFIG.md](./OPENREGISTER-BEVAX-CONFIG.md) - Database structuur
- [VRJIBRP-LOGICA-SERVICE-IMPLEMENTATIE-PLAN.md](./VRJIBRP-LOGICA-SERVICE-IMPLEMENTATIE-PLAN.md) - Implementatie plan







