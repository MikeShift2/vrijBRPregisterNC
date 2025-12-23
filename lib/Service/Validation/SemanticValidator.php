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
}







