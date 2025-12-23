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
    private RvigValidator $rvigValidator;
    
    public function __construct(
        SyntacticValidator $syntacticValidator,
        SemanticValidator $semanticValidator,
        BrpDatabaseService $dbService
    ) {
        $this->syntacticValidator = $syntacticValidator;
        $this->semanticValidator = $semanticValidator;
        $this->dbService = $dbService;
        $this->rvigValidator = new RvigValidator($dbService);
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
        
        // RVIG validaties (alleen als syntactisch en semantisch OK)
        if (empty($errors)) {
            $rvigErrors = $this->rvigValidator->validateRelocation($request);
            $errors = array_merge($errors, $rvigErrors);
        }
        
        return new ValidationResult(
            empty($errors),
            $errors,
            null
        );
    }
    
    /**
     * Valideer geboorte
     */
    public function validateBirth(array $request): ValidationResult {
        $errors = [];
        
        // Syntactische validaties
        $jsonError = $this->syntacticValidator->validateJson($request);
        if ($jsonError !== null) {
            $errors[] = $jsonError;
        }
        
        // Verplichte velden
        $requiredFields = [
            'child.firstName',
            'child.lastName',
            'child.birthDate',
            'mother.bsn'
        ];
        $requiredErrors = $this->syntacticValidator->validateRequiredFields($request, $requiredFields);
        $errors = array_merge($errors, $requiredErrors);
        
        // BSN validatie
        if (isset($request['mother']['bsn'])) {
            $bsnError = $this->syntacticValidator->validateBsn($request['mother']['bsn']);
            if ($bsnError !== null) {
                $errors[] = new ValidationError('mother.bsn', $bsnError->getMessage());
            }
        }
        
        if (isset($request['father']['bsn'])) {
            $bsnError = $this->syntacticValidator->validateBsn($request['father']['bsn']);
            if ($bsnError !== null) {
                $errors[] = new ValidationError('father.bsn', $bsnError->getMessage());
            }
        }
        
        // Datum validatie
        if (isset($request['child']['birthDate'])) {
            $dateError = $this->syntacticValidator->validateDate(
                $request['child']['birthDate'],
                'child.birthDate'
            );
            if ($dateError !== null) {
                $errors[] = $dateError;
            }
        }
        
        // Als syntactische validatie faalt, stop hier
        if (!empty($errors)) {
            return new ValidationResult(false, $errors);
        }
        
        // Semantische validaties
        if (isset($request['mother']['bsn'])) {
            $existsError = $this->semanticValidator->validateBsnExists(
                $request['mother']['bsn'],
                'mother.bsn'
            );
            if ($existsError !== null) {
                $errors[] = $existsError;
            }
            
            $blockedError = $this->semanticValidator->validateBsnNotBlocked(
                $request['mother']['bsn'],
                'mother.bsn'
            );
            if ($blockedError !== null) {
                $errors[] = $blockedError;
            }
            
            // NIEUWE VALIDATIE: Leeftijdscheck voor moeder (bijv. minimum 16 jaar)
            $ageError = $this->semanticValidator->validateMinimumAge(
                $request['mother']['bsn'],
                16,
                'mother.bsn'
            );
            if ($ageError !== null) {
                $errors[] = $ageError;
            }
        }
        
        if (isset($request['father']['bsn'])) {
            $existsError = $this->semanticValidator->validateBsnExists(
                $request['father']['bsn'],
                'father.bsn'
            );
            if ($existsError !== null) {
                $errors[] = $existsError;
            }
            
            $blockedError = $this->semanticValidator->validateBsnNotBlocked(
                $request['father']['bsn'],
                'father.bsn'
            );
            if ($blockedError !== null) {
                $errors[] = $blockedError;
            }
            
            // NIEUWE VALIDATIE: Leeftijdscheck voor vader (bijv. minimum 16 jaar)
            $ageError = $this->semanticValidator->validateMinimumAge(
                $request['father']['bsn'],
                16,
                'father.bsn'
            );
            if ($ageError !== null) {
                $errors[] = $ageError;
            }
        }
        
        // RVIG validaties (alleen als syntactisch en semantisch OK)
        if (empty($errors)) {
            $rvigErrors = $this->rvigValidator->validateBirth($request);
            $errors = array_merge($errors, $rvigErrors);
        }
        
        return new ValidationResult(
            empty($errors),
            $errors,
            null
        );
    }
    
    /**
     * Valideer partnerschap
     */
    public function validateCommitment(array $request): ValidationResult {
        $errors = [];
        
        // Syntactische validaties
        $jsonError = $this->syntacticValidator->validateJson($request);
        if ($jsonError !== null) {
            $errors[] = $jsonError;
        }
        
        // Verplichte velden
        $requiredFields = [
            'partner1.bsn',
            'partner2.bsn',
            'commitmentDate'
        ];
        $requiredErrors = $this->syntacticValidator->validateRequiredFields($request, $requiredFields);
        $errors = array_merge($errors, $requiredErrors);
        
        // BSN validatie
        if (isset($request['partner1']['bsn'])) {
            $bsnError = $this->syntacticValidator->validateBsn($request['partner1']['bsn']);
            if ($bsnError !== null) {
                $errors[] = new ValidationError('partner1.bsn', $bsnError->getMessage());
            }
        }
        
        if (isset($request['partner2']['bsn'])) {
            $bsnError = $this->syntacticValidator->validateBsn($request['partner2']['bsn']);
            if ($bsnError !== null) {
                $errors[] = new ValidationError('partner2.bsn', $bsnError->getMessage());
            }
        }
        
        // Datum validatie
        if (isset($request['commitmentDate'])) {
            $dateError = $this->syntacticValidator->validateDate(
                $request['commitmentDate'],
                'commitmentDate'
            );
            if ($dateError !== null) {
                $errors[] = $dateError;
            }
        }
        
        // Als syntactische validatie faalt, stop hier
        if (!empty($errors)) {
            return new ValidationResult(false, $errors);
        }
        
        // Semantische validaties
        if (isset($request['partner1']['bsn'])) {
            $existsError = $this->semanticValidator->validateBsnExists(
                $request['partner1']['bsn'],
                'partner1.bsn'
            );
            if ($existsError !== null) {
                $errors[] = $existsError;
            }
            
            $blockedError = $this->semanticValidator->validateBsnNotBlocked(
                $request['partner1']['bsn'],
                'partner1.bsn'
            );
            if ($blockedError !== null) {
                $errors[] = $blockedError;
            }
            
            // NIEUWE VALIDATIES:
            
            // 1. Check of partner niet al getrouwd is
            $marriedError = $this->semanticValidator->validateNotMarried(
                $request['partner1']['bsn'],
                'partner1.bsn'
            );
            if ($marriedError !== null) {
                $errors[] = $marriedError;
            }
            
            // 2. Leeftijdscheck (bijv. minimum 18 jaar)
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
            $existsError = $this->semanticValidator->validateBsnExists(
                $request['partner2']['bsn'],
                'partner2.bsn'
            );
            if ($existsError !== null) {
                $errors[] = $existsError;
            }
            
            $blockedError = $this->semanticValidator->validateBsnNotBlocked(
                $request['partner2']['bsn'],
                'partner2.bsn'
            );
            if ($blockedError !== null) {
                $errors[] = $blockedError;
            }
            
            // NIEUWE VALIDATIES:
            
            // 1. Check of partner niet al getrouwd is
            $marriedError = $this->semanticValidator->validateNotMarried(
                $request['partner2']['bsn'],
                'partner2.bsn'
            );
            if ($marriedError !== null) {
                $errors[] = $marriedError;
            }
            
            // 2. Leeftijdscheck (bijv. minimum 18 jaar)
            $ageError = $this->semanticValidator->validateMinimumAge(
                $request['partner2']['bsn'],
                18,
                'partner2.bsn'
            );
            if ($ageError !== null) {
                $errors[] = $ageError;
            }
        }
        
        // RVIG validaties (alleen als syntactisch en semantisch OK)
        if (empty($errors)) {
            $rvigErrors = $this->rvigValidator->validatePartnership($request);
            $errors = array_merge($errors, $rvigErrors);
        }
        
        return new ValidationResult(
            empty($errors),
            $errors,
            null
        );
    }
    
    /**
     * Valideer overlijden
     */
    public function validateDeath(array $request): ValidationResult {
        $errors = [];

        // Syntactische validaties
        $jsonError = $this->syntacticValidator->validateJson($request);
        if ($jsonError !== null) {
            $errors[] = $jsonError;
        }

        $requiredErrors = $this->syntacticValidator->validateRequiredFields(
            $request,
            ['person.bsn', 'deathDate', 'place']
        );
        $errors = array_merge($errors, $requiredErrors);

        if (isset($request['person']['bsn'])) {
            $bsnError = $this->syntacticValidator->validateBsn($request['person']['bsn']);
            if ($bsnError !== null) {
                $errors[] = new ValidationError('person.bsn', $bsnError->getMessage());
            }
        }

        if (isset($request['deathDate'])) {
            $dateError = $this->syntacticValidator->validateDate($request['deathDate'], 'deathDate');
            if ($dateError !== null) {
                $errors[] = $dateError;
            } else {
                // Datum mag niet in de toekomst liggen
                $deathDate = new \DateTime($request['deathDate']);
                $today = new \DateTime('today');
                if ($deathDate > $today) {
                    $errors[] = new ValidationError('deathDate', 'Death date cannot be in the future');
                }
            }
        }

        if (!empty($errors)) {
            return new ValidationResult(false, $errors);
        }

        // Semantische validaties
        $personBsn = $request['person']['bsn'] ?? null;
        if ($personBsn) {
            $existsError = $this->semanticValidator->validateBsnExists($personBsn, 'person.bsn');
            if ($existsError !== null) {
                $errors[] = $existsError;
            }

            $blockedError = $this->semanticValidator->validateBsnNotBlocked($personBsn, 'person.bsn');
            if ($blockedError !== null) {
                $errors[] = $blockedError;
            }
        }

        // RVIG-validatie (plausibiliteit)
        if (empty($errors)) {
            $rvigErrors = $this->rvigValidator->validateDeath($request);
            $errors = array_merge($errors, $rvigErrors);
        }

        if (!empty($errors)) {
            return new ValidationResult(false, $errors);
        }

        // Getransformeerde data voor opslag
        $transformed = [
            'person_bsn' => $personBsn,
            'death_date' => $request['deathDate'] ?? null,
            'death_place' => $request['place'] ?? null,
            'reference_id' => $request['reference_id'] ?? null,
            'zaak_id' => $request['zaak_id'] ?? null,
            'documents' => $request['documents'] ?? [],
            'status' => 'ingediend',
            'mutation_type' => 'death',
            'mutation_data' => $request
        ];

        return new ValidationResult(true, [], $transformed);
    }
    
}
