<?php
/**
 * Data Transformation Service
 * 
 * Transformeert API formaat naar database formaat voor mutaties
 */

namespace OCA\OpenRegister\Service\Transformation;

use OCA\OpenRegister\Service\Database\BrpDatabaseService;

class DataTransformationService {
    private BrpDatabaseService $dbService;
    
    public function __construct(BrpDatabaseService $dbService) {
        $this->dbService = $dbService;
    }
    
    /**
     * Transformeer verhuizing API request naar database formaat
     */
    public function transformRelocation(array $apiRequest): array {
        $dbData = [];
        
        // Basis gegevens
        $dbData['declarant_bsn'] = $apiRequest['declarant']['bsn'] ?? null;
        $dbData['relocation_date'] = $apiRequest['relocationDate'] ?? null;
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Nieuw adres
        $newAddress = $apiRequest['newAddress'] ?? [];
        $dbData['new_postal_code'] = $newAddress['postalCode'] ?? null;
        $dbData['new_house_number'] = $newAddress['houseNumber'] ?? null;
        $dbData['new_house_number_addition'] = $newAddress['houseNumberAddition'] ?? null;
        $dbData['new_street'] = $newAddress['street'] ?? null;
        $dbData['new_city'] = $newAddress['city'] ?? null;
        $dbData['new_country'] = $newAddress['country'] ?? 'NL';
        
        // Oud adres (indien opgegeven)
        $oldAddress = $apiRequest['oldAddress'] ?? null;
        if ($oldAddress) {
            $dbData['old_postal_code'] = $oldAddress['postalCode'] ?? null;
            $dbData['old_house_number'] = $oldAddress['houseNumber'] ?? null;
            $dbData['old_street'] = $oldAddress['street'] ?? null;
            $dbData['old_city'] = $oldAddress['city'] ?? null;
        }
        
        // Relocators (indien aanwezig)
        if (isset($apiRequest['relocators']) && is_array($apiRequest['relocators'])) {
            $relocatorBsns = [];
            foreach ($apiRequest['relocators'] as $relocator) {
                if (isset($relocator['bsn'])) {
                    $relocatorBsns[] = $relocator['bsn'];
                }
            }
            $dbData['relocator_bsns'] = $relocatorBsns;
        }
        
        // Relocator (indien als enkel object)
        if (isset($apiRequest['relocator'])) {
            $dbData['relocator_bsn'] = $apiRequest['relocator']['bsn'] ?? null;
            $dbData['relocator_relationship'] = $apiRequest['relocator']['relationship'] ?? null;
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
        $child = $apiRequest['child'] ?? $apiRequest['person'] ?? [];
        $dbData['birth_date'] = $child['birthDate'] ?? $apiRequest['birthDate'] ?? null;
        $dbData['birth_place'] = $child['birthPlace'] ?? $apiRequest['birthPlace'] ?? null;
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Persoon gegevens
        $dbData['first_names'] = is_array($child['firstNames'] ?? null) 
            ? implode(' ', $child['firstNames']) 
            : ($child['firstName'] ?? $child['firstNames'] ?? null);
        $dbData['last_name'] = $child['lastName'] ?? null;
        $dbData['gender'] = $child['gender'] ?? null;
        
        // Ouders
        if (isset($apiRequest['mother'])) {
            $dbData['mother_bsn'] = $apiRequest['mother']['bsn'] ?? null;
        }
        if (isset($apiRequest['father'])) {
            $dbData['father_bsn'] = $apiRequest['father']['bsn'] ?? null;
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
        $dbData['partnership_date'] = $apiRequest['commitmentDate'] ?? $apiRequest['partnershipDate'] ?? null;
        $dbData['partnership_place'] = $apiRequest['commitmentPlace'] ?? $apiRequest['partnershipPlace'] ?? null;
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Partners
        $dbData['partner1_bsn'] = $apiRequest['partner1']['bsn'] ?? null;
        $dbData['partner2_bsn'] = $apiRequest['partner2']['bsn'] ?? null;
        
        // Partnership type
        $dbData['partnership_type'] = $apiRequest['partnershipType'] ?? 'marriage';
        
        // Metadata
        $dbData['mutation_type'] = 'partnership';
        $dbData['mutation_date'] = date('Y-m-d H:i:s');
        $dbData['status'] = 'pending';
        
        return $dbData;
    }
    
    /**
     * Transformeer overlijden API request naar database formaat
     */
    public function transformDeath(array $apiRequest): array {
        $dbData = [];
        
        // Basis gegevens
        $person = $apiRequest['person'] ?? [];
        $dbData['person_bsn'] = $person['bsn'] ?? $apiRequest['bsn'] ?? null;
        $dbData['death_date'] = $apiRequest['deathDate'] ?? null;
        $dbData['death_place'] = $apiRequest['deathPlace'] ?? null;
        $dbData['reference_id'] = $apiRequest['referenceId'] ?? null;
        
        // Metadata
        $dbData['mutation_type'] = 'death';
        $dbData['mutation_date'] = date('Y-m-d H:i:s');
        $dbData['status'] = 'pending';
        
        return $dbData;
    }
}







