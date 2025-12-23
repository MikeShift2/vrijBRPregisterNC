<?php
/**
 * ZGW Tasks Controller
 * 
 * Implementeert de Tasks API voor workflow management
 * bovenop OpenRegister Tasks schema (ID 22)
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntityMapper;

class ZgwTaskController extends Controller {
    
    private const REGISTER_ID_TASKS = 4; // Tasks register (moet worden aangemaakt)
    private const SCHEMA_ID_TASKS = 22;   // Tasks schema
    
    public function __construct(
        $appName,
        IRequest $request,
        private ObjectService $objectService,
        private SchemaMapper $schemaMapper,
        private ObjectEntityMapper $objectMapper,
        private IDBConnection $db
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /apps/openregister/zgw/tasks
     * Lijst alle tasks (met filters)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Query parameters:
     * - bsn: Filter op BSN
     * - taskType: Filter op task type
     * - status: Filter op status (planned, in_progress, done)
     * - zaakId: Filter op zaak ID
     * - page: Paginanummer (default: 1)
     * - page_size: Aantal resultaten per pagina (default: 20)
     */
    public function getTasks(
        ?string $bsn = null,
        ?string $taskType = null,
        ?string $status = null,
        ?string $zaakId = null,
        int $page = 1,
        int $page_size = 20
    ): DataResponse {
        try {
            $limit = $page_size;
            $offset = ($page - 1) * $page_size;
            
            // Build filters
            $filters = [];
            if ($bsn) {
                $filters['bsn'] = $bsn;
            }
            if ($taskType) {
                $filters['task_type'] = $taskType;
            }
            if ($status) {
                $filters['status'] = $status;
            }
            if ($zaakId) {
                $filters['zaak_id'] = $zaakId;
            }
            
            // Haal tasks op uit database
            $objects = $this->getTasksFromDatabase($limit, $offset, $filters);
            
            // Transformeer naar response formaat
            $tasks = [];
            foreach ($objects['data'] as $object) {
                $tasks[] = $this->transformToTaskResponse($object);
            }
            
            return new DataResponse([
                'tasks' => $tasks,
                'count' => $objects['pagination']['total'] ?? count($tasks),
                'page' => $page,
                'page_size' => $page_size
            ]);
            
        } catch (\Exception $e) {
            error_log("Error in getTasks: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /apps/openregister/zgw/tasks/{taskId}
     * Specifieke task ophalen
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getTask(string $taskId): DataResponse {
        try {
            $task = $this->getTaskById($taskId);
            
            if (!$task) {
                return new DataResponse([
                    'error' => 'Task niet gevonden',
                    'detail' => "Task met ID {$taskId} bestaat niet"
                ], 404);
            }
            
            return new DataResponse($this->transformToTaskResponse($task));
            
        } catch (\Exception $e) {
            error_log("Error in getTask: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /apps/openregister/zgw/tasks
     * Nieuwe task aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createTask(): DataResponse {
        try {
            $data = $this->request->getParams();
            
            // Valideer required velden
            $required = ['task_type', 'status', 'description'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return new DataResponse([
                        'error' => 'Bad Request',
                        'detail' => "Required field '{$field}' is missing"
                    ], 400);
                }
            }
            
            // Set created_at als niet gegeven
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('c');
            }
            
            // Maak task aan via Open Register
            $task = $this->objectService->create(
                $data,
                self::REGISTER_ID_TASKS,
                self::SCHEMA_ID_TASKS
            );
            
            return new DataResponse($this->transformToTaskResponse($task), 201);
            
        } catch (\Exception $e) {
            error_log("Error in createTask: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /apps/openregister/zgw/tasks/{taskId}
     * Task bijwerken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateTask(string $taskId): DataResponse {
        try {
            $data = $this->request->getParams();
            
            // Check of task bestaat
            $existingTask = $this->getTaskById($taskId);
            if (!$existingTask) {
                return new DataResponse([
                    'error' => 'Task niet gevonden',
                    'detail' => "Task met ID {$taskId} bestaat niet"
                ], 404);
            }
            
            // Als status wordt bijgewerkt naar 'done', set completed_at
            if (isset($data['status']) && $data['status'] === 'done' && !isset($data['completed_at'])) {
                $data['completed_at'] = date('c');
            }
            
            // Update task via Open Register
            $task = $this->objectService->update(
                $taskId,
                $data,
                self::REGISTER_ID_TASKS,
                self::SCHEMA_ID_TASKS
            );
            
            return new DataResponse($this->transformToTaskResponse($task));
            
        } catch (\Exception $e) {
            error_log("Error in updateTask: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE /apps/openregister/zgw/tasks/{taskId}
     * Task verwijderen
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteTask(string $taskId): DataResponse {
        try {
            // Check of task bestaat
            $existingTask = $this->getTaskById($taskId);
            if (!$existingTask) {
                return new DataResponse([
                    'error' => 'Task niet gevonden',
                    'detail' => "Task met ID {$taskId} bestaat niet"
                ], 404);
            }
            
            // Verwijder task via Open Register
            $this->objectService->delete(
                $taskId,
                self::REGISTER_ID_TASKS,
                self::SCHEMA_ID_TASKS
            );
            
            return new DataResponse(null, 204);
            
        } catch (\Exception $e) {
            error_log("Error in deleteTask: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Haal tasks op uit database
     */
    private function getTasksFromDatabase(int $limit, int $offset, array $filters = []): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_TASKS)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_TASKS)))
           ->setMaxResults($limit)
           ->setFirstResult($offset);
        
        // Apply filters
        foreach ($filters as $field => $value) {
            $qb->andWhere(
                $qb->expr()->like(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.' . $field) . '))'),
                    $qb->createNamedParameter('%' . $value . '%')
                )
            );
        }
        
        $result = $qb->executeQuery();
        $objects = $result->fetchAll();
        
        // Count total
        $qbCount = $this->db->getQueryBuilder();
        $qbCount->select($qbCount->createFunction('COUNT(*)'))
                ->from('openregister_objects')
                ->where($qbCount->expr()->eq('register', $qbCount->createNamedParameter(self::REGISTER_ID_TASKS)))
                ->andWhere($qbCount->expr()->eq('schema', $qbCount->createNamedParameter(self::SCHEMA_ID_TASKS)));
        
        foreach ($filters as $field => $value) {
            $qbCount->andWhere(
                $qbCount->expr()->like(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.' . $field) . '))'),
                    $qbCount->createNamedParameter('%' . $value . '%')
                )
            );
        }
        
        $countResult = $qbCount->executeQuery();
        $total = $countResult->fetchOne();
        
        $data = [];
        foreach ($objects as $row) {
            $objectData = json_decode($row['object'], true);
            if ($objectData) {
                $data[] = [
                    'uuid' => $row['uuid'],
                    'object' => $objectData,
                    '@self' => [
                        'id' => $row['id'],
                        'uuid' => $row['uuid'],
                        'register' => self::REGISTER_ID_TASKS,
                        'schema' => self::SCHEMA_ID_TASKS,
                        'created' => $row['created'],
                        'updated' => $row['updated']
                    ]
                ];
            }
        }
        
        return [
            'data' => $data,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]
        ];
    }
    
    /**
     * Haal specifieke task op bij ID
     */
    private function getTaskById(string $taskId): ?array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_TASKS)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_TASKS)))
           ->andWhere($qb->expr()->eq('uuid', $qb->createNamedParameter($taskId)));
        
        $result = $qb->executeQuery();
        $row = $result->fetch();
        
        if (!$row) {
            return null;
        }
        
        $objectData = json_decode($row['object'], true);
        if (!$objectData) {
            return null;
        }
        
        return [
            'uuid' => $row['uuid'],
            'object' => $objectData,
            '@self' => [
                'id' => $row['id'],
                'uuid' => $row['uuid'],
                'register' => self::REGISTER_ID_TASKS,
                'schema' => self::SCHEMA_ID_TASKS,
                'created' => $row['created'],
                'updated' => $row['updated']
            ]
        ];
    }
    
    /**
     * Transformeer Open Register object naar Task response formaat
     */
    private function transformToTaskResponse(array $object): array {
        $data = $object['object'] ?? $object;
        $uuid = $object['uuid'] ?? $object['@self']['uuid'] ?? null;
        
        return [
            'taskId' => $uuid,
            'task_id' => $data['task_id'] ?? $uuid,
            'zaakId' => $data['zaak_id'] ?? null,
            'zaak_identificatie' => $data['zaak_identificatie'] ?? null,
            'taskType' => $data['task_type'] ?? '',
            'task_type' => $data['task_type'] ?? '',
            'status' => $data['status'] ?? 'planned',
            'bsn' => $data['bsn'] ?? null,
            'description' => $data['description'] ?? '',
            'createdAt' => $data['created_at'] ?? date('c'),
            'dueDate' => $data['due_date'] ?? null,
            'completedAt' => $data['completed_at'] ?? null
        ];
    }
}

