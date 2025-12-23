<?php
/**
 * Search Trail Service
 * 
 * Service voor het loggen van zoekopdrachten voor analytics en dashboard
 */

namespace OCA\OpenRegister\Service;

use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\IRequest;

class SearchTrailService {
    
    public function __construct(
        private IDBConnection $db,
        private IUserSession $userSession,
        private IRequest $request
    ) {
    }
    
    /**
     * Maak een search trail entry aan
     * 
     * @param array $queryParams Query parameters van de zoekopdracht
     * @param int $resultCount Aantal resultaten gevonden
     * @param int $totalResults Totaal aantal resultaten
     * @param float $responseTime Response tijd in milliseconden
     * @param string $type Type zoekopdracht (sync, async, etc.)
     * @return bool True als succesvol, false bij fout
     */
    public function createSearchTrail(
        array $queryParams,
        int $resultCount,
        int $totalResults,
        float $responseTime,
        string $type = 'sync'
    ): bool {
        try {
            $user = $this->userSession->getUser();
            $userId = $user ? $user->getUID() : 'anonymous';
            
            // Haal register en schema uit query params
            $registerId = $queryParams['@self']['register'] ?? null;
            $schemaId = $queryParams['@self']['schema'] ?? null;
            
            // Converteer naar integers als ze niet null zijn
            if ($registerId !== null) {
                $registerId = (int)$registerId;
            }
            if ($schemaId !== null) {
                $schemaId = (int)$schemaId;
            }
            
            // Haal search term op
            $searchTerm = $queryParams['search'] ?? 
                         $queryParams['bsn'] ?? 
                         $queryParams['achternaam'] ?? 
                         null;
            
            // Haal user agent op
            $userAgent = $this->request->getHeader('User-Agent') ?? 'unknown';
            
            // Maak query builder
            $qb = $this->db->getQueryBuilder();
            $qb->insert('oc_openregister_search_trails')
               ->setValue('user_id', $qb->createNamedParameter($userId))
               ->setValue('register_id', $qb->createNamedParameter($registerId))
               ->setValue('schema_id', $qb->createNamedParameter($schemaId))
               ->setValue('query_params', $qb->createNamedParameter(json_encode($queryParams)))
               ->setValue('search_term', $qb->createNamedParameter($searchTerm))
               ->setValue('result_count', $qb->createNamedParameter($resultCount))
               ->setValue('total_results', $qb->createNamedParameter($totalResults))
               ->setValue('response_time', $qb->createNamedParameter($responseTime))
               ->setValue('type', $qb->createNamedParameter($type))
               ->setValue('user_agent', $qb->createNamedParameter($userAgent))
               ->setValue('ip_address', $qb->createNamedParameter($this->request->getRemoteAddress()))
               ->setValue('created_at', $qb->createFunction('NOW()'));
            
            $qb->executeStatement();
            return true;
            
        } catch (\Exception $e) {
            // Log error maar gooi niet - search trail logging mag niet falen
            error_log("SearchTrailService error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}

