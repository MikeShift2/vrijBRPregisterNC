<?php
/**
 * RVIG Validator
 * 
 * Complexe BRP-regels volgens RVIG (Rijksdienst voor Identiteitsgegevens)
 * Implementeert business rules die niet in syntactische/semantische validatie passen
 */

namespace OCA\OpenRegister\Service\Validation;

use OCA\OpenRegister\Service\Database\BrpDatabaseService;

class RvigValidator {
    private BrpDatabaseService $dbService;
    
    public function __construct(BrpDatabaseService $dbService) {
        $this->dbService = $dbService;
    }
    
    /**
     * Valideer geboorte mutatie volgens RVIG
     */
    public function validateBirth(array $request): array {
        $errors = [];
        
        // Geboortedatum mag niet in de toekomst liggen
        $birthDate = $request['child']['birthDate'] ?? $request['birthDate'] ?? null;
        if ($birthDate) {
            $birthDateTime = new \DateTime($birthDate);
            $now = new \DateTime();
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
            $mother = $this->dbService->findPersonByBsn($motherBsn);
            if (!$mother) {
                $errors[] = new ValidationError(
                    'mother.bsn',
                    'Mother BSN does not exist',
                    'MOTHER_NOT_FOUND'
                );
            } else {
                // Check geslacht (V = vrouw)
                $gender = $this->getGender($motherBsn);
                if ($gender !== 'V' && $gender !== 'v') {
                    $errors[] = new ValidationError(
                        'mother.bsn',
                        'Mother must be female',
                        'MOTHER_NOT_FEMALE'
                    );
                }
            }
        }
        
        // Vader moet bestaan en man zijn (indien opgegeven)
        $fatherBsn = $request['father']['bsn'] ?? null;
        if ($fatherBsn) {
            $father = $this->dbService->findPersonByBsn($fatherBsn);
            if (!$father) {
                $errors[] = new ValidationError(
                    'father.bsn',
                    'Father BSN does not exist',
                    'FATHER_NOT_FOUND'
                );
            } else {
                // Check geslacht (M = man)
                $gender = $this->getGender($fatherBsn);
                if ($gender !== 'M' && $gender !== 'm') {
                    $errors[] = new ValidationError(
                        'father.bsn',
                        'Father must be male',
                        'FATHER_NOT_MALE'
                    );
                }
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
                    ($currentAddress['postcode'] ?? '') === ($newAddress['postalCode'] ?? '') &&
                    ($currentAddress['huisnummer'] ?? '') === ($newAddress['houseNumber'] ?? '') &&
                    ($currentAddress['straat'] ?? '') === ($newAddress['street'] ?? '')
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
                $relocationDateTime = new \DateTime($relocationDate);
                $now = new \DateTime();
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
            $partner1 = $this->dbService->findPersonByBsn($partner1Bsn);
            $partner2 = $this->dbService->findPersonByBsn($partner2Bsn);
            
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
            if ($partner1 && !$this->isAdult($partner1Bsn)) {
                $errors[] = new ValidationError(
                    'partner1.bsn',
                    'Partner 1 must be 18 years or older',
                    'PARTNER1_NOT_ADULT'
                );
            }
            
            if ($partner2 && !$this->isAdult($partner2Bsn)) {
                $errors[] = new ValidationError(
                    'partner2.bsn',
                    'Partner 2 must be 18 years or older',
                    'PARTNER2_NOT_ADULT'
                );
            }
        }
        
        return $errors;
    }

    /**
     * Valideer overlijden mutatie volgens RVIG
     */
    public function validateDeath(array $request): array {
        $errors = [];

        $deathDate = $request['deathDate'] ?? null;
        $place = $request['place'] ?? null;
        $bsn = $request['person']['bsn'] ?? null;

        if ($deathDate) {
            // Mag niet voor geboorte liggen (voor zover beschikbaar)
            if ($bsn) {
                $birthDate = $this->dbService->getBirthDate($bsn);
                if ($birthDate instanceof \DateTime) {
                    $death = new \DateTime($deathDate);
                    if ($death < $birthDate) {
                        $errors[] = new ValidationError(
                            'deathDate',
                            'Death date cannot be before birth date',
                            'DEATH_BEFORE_BIRTH'
                        );
                    }
                }
            }
        }

        if ($place === null || $place === '') {
            $errors[] = new ValidationError(
                'place',
                'Death place is required',
                'DEATH_PLACE_REQUIRED'
            );
        }

        return $errors;
    }
    
    /**
     * Check of persoon volwassen is (18+)
     */
    private function isAdult(string $bsn): bool {
        $age = $this->dbService->getAge($bsn);
        return $age !== null && $age >= 18;
    }
    
    /**
     * Haal geslacht op voor BSN
     */
    private function getGender(string $bsn): ?string {
        $person = $this->dbService->findPersonByBsn($bsn);
        if (!$person) {
            return null;
        }
        
        // Probeer geslacht uit verschillende velden
        return $person['geslacht'] ?? $person['gender'] ?? $person['geslachtcode'] ?? null;
    }
}






