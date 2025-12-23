<?php
/**
 * Mutatie Database Service
 * 
 * Database operaties voor mutaties in MariaDB (Nextcloud database)
 */

namespace OCA\OpenRegister\Service\Database;

use OCP\IDBConnection;

class MutatieDatabaseService {
    private IDBConnection $db;
    
    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }
    
    /**
     * Maak verhuizing mutatie aan
     */
    public function createRelocation(array $data): string {
        $qb = $this->db->getQueryBuilder();
        
        $dossierId = $data['dossier_id'] ?? 'DOSSIER-' . date('Ymd') . '-' . uniqid();
        
        $qb->insert('oc_openregister_mutaties')
            ->setValue('dossier_id', $qb->createNamedParameter($dossierId))
            ->setValue('mutation_type', $qb->createNamedParameter('relocation'))
            ->setValue('status', $qb->createNamedParameter($data['status'] ?? 'ingediend'))
            ->setValue('reference_id', $qb->createNamedParameter($data['reference_id'] ?? null))
            ->setValue('declarant_bsn', $qb->createNamedParameter($data['declarant_bsn'] ?? null))
            ->setValue('relocation_date', $qb->createNamedParameter($data['relocation_date'] ?? null))
            ->setValue('new_postal_code', $qb->createNamedParameter($data['new_postal_code'] ?? null))
            ->setValue('new_house_number', $qb->createNamedParameter($data['new_house_number'] ?? null))
            ->setValue('new_house_number_addition', $qb->createNamedParameter($data['new_house_number_addition'] ?? null))
            ->setValue('new_street', $qb->createNamedParameter($data['new_street'] ?? null))
            ->setValue('new_city', $qb->createNamedParameter($data['new_city'] ?? null))
            ->setValue('new_country', $qb->createNamedParameter($data['new_country'] ?? 'NL'))
            ->setValue('old_postal_code', $qb->createNamedParameter($data['old_postal_code'] ?? null))
            ->setValue('old_house_number', $qb->createNamedParameter($data['old_house_number'] ?? null))
            ->setValue('old_street', $qb->createNamedParameter($data['old_street'] ?? null))
            ->setValue('old_city', $qb->createNamedParameter($data['old_city'] ?? null))
            ->setValue('relocator_bsn', $qb->createNamedParameter($data['relocator_bsn'] ?? null))
            ->setValue('relocator_relationship', $qb->createNamedParameter($data['relocator_relationship'] ?? null))
            ->setValue('relocator_bsns', $qb->createNamedParameter(
                isset($data['relocator_bsns']) ? json_encode($data['relocator_bsns']) : null
            ))
            ->setValue('mutation_data', $qb->createNamedParameter(json_encode($data)));
        
        $qb->executeStatement();
        
        return $dossierId;
    }
    
    /**
     * Maak geboorte mutatie aan
     */
    public function createBirth(array $data): string {
        $qb = $this->db->getQueryBuilder();
        
        $dossierId = $data['dossier_id'] ?? 'DOSSIER-' . date('Ymd') . '-' . uniqid();
        
        $qb->insert('oc_openregister_mutaties')
            ->setValue('dossier_id', $qb->createNamedParameter($dossierId))
            ->setValue('mutation_type', $qb->createNamedParameter('birth'))
            ->setValue('status', $qb->createNamedParameter($data['status'] ?? 'ingediend'))
            ->setValue('reference_id', $qb->createNamedParameter($data['reference_id'] ?? null))
            ->setValue('birth_date', $qb->createNamedParameter($data['birth_date'] ?? null))
            ->setValue('birth_place', $qb->createNamedParameter($data['birth_place'] ?? null))
            ->setValue('first_names', $qb->createNamedParameter($data['first_names'] ?? null))
            ->setValue('last_name', $qb->createNamedParameter($data['last_name'] ?? null))
            ->setValue('gender', $qb->createNamedParameter($data['gender'] ?? null))
            ->setValue('mother_bsn', $qb->createNamedParameter($data['mother_bsn'] ?? null))
            ->setValue('father_bsn', $qb->createNamedParameter($data['father_bsn'] ?? null))
            ->setValue('mutation_data', $qb->createNamedParameter(json_encode($data)));
        
        $qb->executeStatement();
        
        return $dossierId;
    }
    
    /**
     * Maak partnerschap mutatie aan
     */
    public function createPartnership(array $data): string {
        $qb = $this->db->getQueryBuilder();
        
        $dossierId = $data['dossier_id'] ?? 'DOSSIER-' . date('Ymd') . '-' . uniqid();
        
        $qb->insert('oc_openregister_mutaties')
            ->setValue('dossier_id', $qb->createNamedParameter($dossierId))
            ->setValue('mutation_type', $qb->createNamedParameter('partnership'))
            ->setValue('status', $qb->createNamedParameter($data['status'] ?? 'ingediend'))
            ->setValue('reference_id', $qb->createNamedParameter($data['reference_id'] ?? null))
            ->setValue('partnership_date', $qb->createNamedParameter($data['partnership_date'] ?? null))
            ->setValue('partnership_place', $qb->createNamedParameter($data['partnership_place'] ?? null))
            ->setValue('partnership_type', $qb->createNamedParameter($data['partnership_type'] ?? 'marriage'))
            ->setValue('partner1_bsn', $qb->createNamedParameter($data['partner1_bsn'] ?? null))
            ->setValue('partner2_bsn', $qb->createNamedParameter($data['partner2_bsn'] ?? null))
            ->setValue('mutation_data', $qb->createNamedParameter(json_encode($data)));
        
        $qb->executeStatement();
        
        return $dossierId;
    }
    
    /**
     * Maak overlijden mutatie aan
     */
    public function createDeath(array $data): string {
        $qb = $this->db->getQueryBuilder();
        
        $dossierId = $data['dossier_id'] ?? 'DOSSIER-' . date('Ymd') . '-' . uniqid();
        
        $qb->insert('oc_openregister_mutaties')
            ->setValue('dossier_id', $qb->createNamedParameter($dossierId))
            ->setValue('mutation_type', $qb->createNamedParameter('death'))
            ->setValue('status', $qb->createNamedParameter($data['status'] ?? 'ingediend'))
            ->setValue('reference_id', $qb->createNamedParameter($data['reference_id'] ?? $data['zaak_id'] ?? null))
            ->setValue('person_bsn', $qb->createNamedParameter($data['person_bsn'] ?? null))
            ->setValue('death_date', $qb->createNamedParameter($data['death_date'] ?? null))
            ->setValue('death_place', $qb->createNamedParameter($data['death_place'] ?? null))
            ->setValue('mutation_data', $qb->createNamedParameter(json_encode($data)));
        
        $qb->executeStatement();
        
        return $dossierId;
    }
    
    /**
     * Haal mutatie op bij dossier ID
     */
    public function getMutatieByDossierId(string $dossierId): ?array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('oc_openregister_mutaties')
           ->where($qb->expr()->eq('dossier_id', $qb->createNamedParameter($dossierId)))
           ->setMaxResults(1);
        
        $result = $qb->executeQuery();
        $row = $result->fetch();
        
        return $row ?: null;
    }
}




