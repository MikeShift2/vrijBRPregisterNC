<?php
/**
 * Search Trail Controller
 * 
 * API endpoints voor search trail management
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IUserSession;

class SearchTrailController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request,
        private IDBConnection $db,
        private IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /api/search-trails
     * Haal alle search trails op met filters
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): DataResponse {
        try {
            $limit = (int)($this->request->getParam('limit') ?? 50);
            $page = (int)($this->request->getParam('page') ?? 1);
            $offset = ($page - 1) * $limit;
            
            // Accepteer zowel register_id als register (voor compatibiliteit)
            $registerId = $this->request->getParam('register_id') ?? $this->request->getParam('register');
            $schemaId = $this->request->getParam('schema_id') ?? $this->request->getParam('schema');
            $searchTerm = $this->request->getParam('search_term');
            $userId = $this->request->getParam('user_id');
            $dateFrom = $this->request->getParam('date_from');
            $dateTo = $this->request->getParam('date_to');
            
            // Converteer naar integers als ze niet null zijn
            if ($registerId !== null) {
                $registerId = (int)$registerId;
            }
            if ($schemaId !== null) {
                $schemaId = (int)$schemaId;
            }
            
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
               ->from('oc_openregister_search_trails')
               ->orderBy('created_at', 'DESC')
               ->setMaxResults($limit)
               ->setFirstResult($offset);
            
            // Filters
            if ($registerId !== null) {
                $qb->andWhere($qb->expr()->eq('register_id', $qb->createNamedParameter($registerId)));
            }
            
            if ($schemaId !== null) {
                $qb->andWhere($qb->expr()->eq('schema_id', $qb->createNamedParameter($schemaId)));
            }
            
            if ($searchTerm !== null && $searchTerm !== '') {
                $qb->andWhere($qb->expr()->like('search_term', $qb->createNamedParameter('%' . $searchTerm . '%')));
            }
            
            if ($userId !== null && $userId !== '') {
                $qb->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            }
            
            if ($dateFrom !== null) {
                $qb->andWhere($qb->expr()->gte('created_at', $qb->createNamedParameter($dateFrom)));
            }
            
            if ($dateTo !== null) {
                $qb->andWhere($qb->expr()->lte('created_at', $qb->createNamedParameter($dateTo)));
            }
            
            $result = $qb->executeQuery();
            $trails = $result->fetchAll();
            
            // Haal totaal aantal op voor paginatie
            $qbCount = $this->db->getQueryBuilder();
            $qbCount->select($qbCount->func()->count('*'))
                    ->from('oc_openregister_search_trails');
            
            // Voeg dezelfde filters toe
            if ($registerId !== null) {
                $qbCount->andWhere($qbCount->expr()->eq('register_id', $qbCount->createNamedParameter($registerId)));
            }
            if ($schemaId !== null) {
                $qbCount->andWhere($qbCount->expr()->eq('schema_id', $qbCount->createNamedParameter($schemaId)));
            }
            if ($searchTerm !== null && $searchTerm !== '') {
                $qbCount->andWhere($qbCount->expr()->like('search_term', $qbCount->createNamedParameter('%' . $searchTerm . '%')));
            }
            if ($userId !== null && $userId !== '') {
                $qbCount->andWhere($qbCount->expr()->eq('user_id', $qbCount->createNamedParameter($userId)));
            }
            if ($dateFrom !== null) {
                $qbCount->andWhere($qbCount->expr()->gte('created_at', $qbCount->createNamedParameter($dateFrom)));
            }
            if ($dateTo !== null) {
                $qbCount->andWhere($qbCount->expr()->lte('created_at', $qbCount->createNamedParameter($dateTo)));
            }
            
            $totalResult = $qbCount->executeQuery();
            $total = (int)$totalResult->fetchOne();
            
            // Parse query_params JSON voor elke trail
            foreach ($trails as &$trail) {
                if (!empty($trail['query_params'])) {
                    $decoded = json_decode($trail['query_params'], true);
                    $trail['query_params'] = $decoded !== null ? $decoded : [];
                } else {
                    $trail['query_params'] = [];
                }
                // Zorg dat alle velden strings zijn voor JSON encoding
                $trail['id'] = (string)$trail['id'];
                $trail['register_id'] = $trail['register_id'] !== null ? (string)$trail['register_id'] : null;
                $trail['schema_id'] = $trail['schema_id'] !== null ? (string)$trail['schema_id'] : null;
                $trail['result_count'] = (int)$trail['result_count'];
                $trail['total_results'] = (int)$trail['total_results'];
                $trail['response_time'] = (float)$trail['response_time'];
            }
            
            // Return response in format expected by frontend
            return new DataResponse([
                'data' => $trails,
                '_embedded' => [
                    'search_trails' => $trails
                ],
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'page' => $page,
                    'has_more' => ($offset + $limit) < $total
                ],
                'total' => $total,
                'count' => count($trails)
            ]);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage(),
                '_embedded' => [
                    'search_trails' => []
                ],
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'limit' => $limit ?? 50,
                    'offset' => $offset ?? 0,
                    'has_more' => false
                ],
                'total' => 0
            ], 500);
        }
    }
    
    /**
     * GET /api/search-trails/statistics
     * Haal statistieken op over search trails
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function statistics(): DataResponse {
        try {
            $qb = $this->db->getQueryBuilder();
            
            // Totaal aantal searches
            $qbTotal = $this->db->getQueryBuilder();
            $qbTotal->select($qbTotal->func()->count('*'))
                    ->from('oc_openregister_search_trails');
            $totalSearches = (int)$qbTotal->executeQuery()->fetchOne();
            
            // Gemiddelde response time
            $qbAvg = $this->db->getQueryBuilder();
            $qbAvg->select($qbAvg->func()->avg('response_time'))
                  ->from('oc_openregister_search_trails');
            $avgResponseTime = $qbAvg->executeQuery()->fetchOne();
            $avgResponseTime = $avgResponseTime ? round((float)$avgResponseTime, 2) : 0;
            
            // Totaal aantal resultaten gevonden
            $qbResults = $this->db->getQueryBuilder();
            $qbResults->select($qbResults->func()->sum('result_count'))
                      ->from('oc_openregister_search_trails');
            $totalResults = (int)$qbResults->executeQuery()->fetchOne();
            
            // Unieke gebruikers
            $qbUsers = $this->db->getQueryBuilder();
            $qbUsers->select($qbUsers->func()->count($qbUsers->createFunction('DISTINCT user_id')))
                    ->from('oc_openregister_search_trails');
            $uniqueUsers = (int)$qbUsers->executeQuery()->fetchOne();
            
            return new DataResponse([
                'total_searches' => $totalSearches,
                'average_response_time' => $avgResponseTime,
                'total_results_found' => $totalResults,
                'unique_users' => $uniqueUsers
            ]);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/search-trails/popular-terms
     * Haal populaire zoektermen op
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function popularTerms(): DataResponse {
        try {
            $limit = (int)($this->request->getParam('limit') ?? 10);
            
            $qb = $this->db->getQueryBuilder();
            $qb->select('search_term', $qb->func()->count('*')->as('count'))
               ->from('oc_openregister_search_trails')
               ->where($qb->expr()->isNotNull('search_term'))
               ->andWhere($qb->expr()->neq('search_term', $qb->createNamedParameter('')))
               ->groupBy('search_term')
               ->orderBy('count', 'DESC')
               ->setMaxResults($limit);
            
            $result = $qb->executeQuery();
            $terms = $result->fetchAll();
            
            return new DataResponse($terms);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/search-trails/activity
     * Haal activiteit over tijd op
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function activity(): DataResponse {
        try {
            $days = (int)($this->request->getParam('days') ?? 7);
            
            $qb = $this->db->getQueryBuilder();
            $qb->select(
                $qb->createFunction('DATE(created_at)')->as('date'),
                $qb->func()->count('*')->as('count')
            )
               ->from('oc_openregister_search_trails')
               ->where($qb->expr()->gte('created_at', $qb->createFunction('DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)')))
               ->groupBy($qb->createFunction('DATE(created_at)'))
               ->orderBy('date', 'ASC');
            
            $result = $qb->executeQuery();
            $activity = $result->fetchAll();
            
            return new DataResponse($activity);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/search-trails/register-schema-stats
     * Statistieken per register en schema
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function registerSchemaStats(): DataResponse {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select(
                'register_id',
                'schema_id',
                $qb->func()->count('*')->as('count'),
                $qb->func()->avg('response_time')->as('avg_response_time')
            )
               ->from('oc_openregister_search_trails')
               ->where($qb->expr()->isNotNull('register_id'))
               ->groupBy('register_id', 'schema_id')
               ->orderBy('count', 'DESC');
            
            $result = $qb->executeQuery();
            $stats = $result->fetchAll();
            
            return new DataResponse($stats);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/search-trails/user-agent-stats
     * Statistieken per user agent
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function userAgentStats(): DataResponse {
        try {
            $limit = (int)($this->request->getParam('limit') ?? 10);
            
            $qb = $this->db->getQueryBuilder();
            $qb->select(
                'user_agent',
                $qb->func()->count('*')->as('count')
            )
               ->from('oc_openregister_search_trails')
               ->where($qb->expr()->isNotNull('user_agent'))
               ->groupBy('user_agent')
               ->orderBy('count', 'DESC')
               ->setMaxResults($limit);
            
            $result = $qb->executeQuery();
            $stats = $result->fetchAll();
            
            return new DataResponse($stats);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/search-trails/export
     * Export search trails als CSV
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function export(): DataResponse {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
               ->from('oc_openregister_search_trails')
               ->orderBy('created_at', 'DESC');
            
            $result = $qb->executeQuery();
            $trails = $result->fetchAll();
            
            // Genereer CSV
            $csv = "id,user_id,register_id,schema_id,search_term,result_count,total_results,response_time,type,created_at\n";
            foreach ($trails as $trail) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                    $trail['id'],
                    $trail['user_id'],
                    $trail['register_id'] ?? '',
                    $trail['schema_id'] ?? '',
                    $trail['search_term'] ?? '',
                    $trail['result_count'],
                    $trail['total_results'],
                    $trail['response_time'],
                    $trail['type'],
                    $trail['created_at']
                );
            }
            
            return new DataResponse($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="search-trails-' . date('Y-m-d') . '.csv"'
            ]);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/search-trails/cleanup
     * Verwijder oude search trails
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function cleanup(): DataResponse {
        try {
            $days = (int)($this->request->getParam('days') ?? 90);
            
            $qb = $this->db->getQueryBuilder();
            $qb->delete('oc_openregister_search_trails')
               ->where($qb->expr()->lt('created_at', $qb->createFunction('DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)')));
            
            $deleted = $qb->executeStatement();
            
            return new DataResponse([
                'deleted' => $deleted,
                'message' => "Deleted {$deleted} search trails older than {$days} days"
            ]);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE /api/search-trails
     * Verwijder meerdere search trails
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function destroyMultiple(): DataResponse {
        try {
            $ids = $this->request->getParam('ids');
            
            if (empty($ids) || !is_array($ids)) {
                return new DataResponse([
                    'error' => 'No IDs provided'
                ], 400);
            }
            
            $qb = $this->db->getQueryBuilder();
            $qb->delete('oc_openregister_search_trails')
               ->where($qb->expr()->in('id', $qb->createParameter('ids')));
            
            $qb->setParameter('ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
            $deleted = $qb->executeStatement();
            
            return new DataResponse([
                'deleted' => $deleted,
                'message' => "Deleted {$deleted} search trails"
            ]);
            
        } catch (\Exception $e) {
            return new DataResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

