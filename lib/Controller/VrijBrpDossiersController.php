<?php
/**
 * vrijBRP Dossiers Controller
 * 
 * Mutatie endpoints voor dossiers (verhuizingen, geboorten, etc.)
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCA\OpenRegister\Service\Validation\VrijBrpValidationService;
use OCA\OpenRegister\Service\Validation\SyntacticValidator;
use OCA\OpenRegister\Service\Validation\SemanticValidator;
use OCA\OpenRegister\Service\Database\BrpDatabaseService;
use OCA\OpenRegister\Service\Database\MutatieDatabaseService;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntityMapper;

class VrijBrpDossiersController extends Controller {
    
    private const REGISTER_ID_MUTATIES = 7; // Mutaties register
    private const SCHEMA_ID_MUTATIES = 24;   // Mutaties schema
    
    private VrijBrpValidationService $validationService;
    private MutatieDatabaseService $mutatieDb;
    private ?ObjectService $objectService;
    
    public function __construct(
        $appName,
        IRequest $request,
        IDBConnection $db,
        ?ObjectService $objectService = null,
        ?SchemaMapper $schemaMapper = null,
        ?ObjectEntityMapper $objectMapper = null
    ) {
        parent::__construct($appName, $request);
        
        // Manual instantiation (fallback als dependency injection niet werkt)
        $dbService = new BrpDatabaseService();
        $syntacticValidator = new SyntacticValidator();
        $semanticValidator = new SemanticValidator($dbService);
        $this->validationService = new VrijBrpValidationService(
            $syntacticValidator,
            $semanticValidator,
            $dbService
        );
        $this->mutatieDb = new MutatieDatabaseService($db);
        
        // ObjectService via dependency injection (optioneel)
        $this->objectService = $objectService;
    }
    
    /**
     * Valideer POST/GET requests
     */
    private function getRequestData(): array {
        // Nextcloud IRequest heeft geen getRawInput; gebruik php://input met fallback op getParams
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $data = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                return $data;
            }
        }

        // Fallback: formulier/params (bijv. multipart)
        $params = $this->request->getParams();
        return is_array($params) ? $params : [];
    }
    
    /**
     * POST /api/v1/relocations/intra
     * Nieuwe verhuizing aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createRelocation(): JSONResponse {
        try {
            // Haal request body op
            $request = $this->getRequestData();
            
            if (empty($request)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid JSON',
                    'errors' => [
                        [
                            'field' => 'request',
                            'message' => 'Request body must be valid JSON'
                        ]
                    ]
                ], 400);
            }
            
            // Valideer via vrijBRP Logica Service
            $result = $this->validationService->validateRelocation($request);
            
            if (!$result->isValid()) {
                return new JSONResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $result->toErrorArray()
                ], 422);
            }
            
            // Transformeer data (via validation service)
            $data = $this->mapRelocation($request, $result->getTransformedData());

            // Gebruik ObjectService als beschikbaar, anders fallback naar directe SQL
            if ($this->objectService) {
                $mutatieData = $this->prepareMutatieDataForObjectService($data, 'relocation');
                $mutatie = $this->objectService->createFromArray(
                    $mutatieData,
                    [],
                    self::REGISTER_ID_MUTATIES,
                    self::SCHEMA_ID_MUTATIES,
                    true,   // rbac
                    false   // multi (skip tenant collections)
                );
                $dossierId = $mutatie->getObject()['id'] ?? $mutatie->getObject()['dossier_id'] ?? null;
            } else {
                // Fallback: schrijf direct naar openregister_objects zodat het zichtbaar is in Open Register UI
                $mutatieData = $this->prepareMutatieDataForObjectService($data, 'relocation');
                $dossierId = $this->createMutatieInOpenRegister($mutatieData);
            }
            
            return new JSONResponse([
                'mutatie_id' => $dossierId,
                'dossierId' => $dossierId,
                'status' => $data['status'] ?? 'ingediend',
                'dossierType' => 'intra_mun_relocation',
                'createdAt' => date('c')
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createRelocation: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/v1/birth
     * Nieuwe geboorte aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createBirth(): JSONResponse {
        try {
            $request = $this->getRequestData();
            
            if (empty($request)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid JSON',
                    'errors' => [
                        [
                            'field' => 'request',
                            'message' => 'Request body must be valid JSON'
                        ]
                    ]
                ], 400);
            }
            
            // Valideer via vrijBRP Logica Service
            $result = $this->validationService->validateBirth($request);
            
            if (!$result->isValid()) {
                return new JSONResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $result->toErrorArray()
                ], 422);
            }
            
            // Transformeer data (via validation service)
            $data = $this->mapBirth($request, $result->getTransformedData());
            
            // Gebruik ObjectService als beschikbaar, anders fallback naar directe SQL
            if ($this->objectService) {
                $mutatieData = $this->prepareMutatieDataForObjectService($data, 'birth');
                $mutatie = $this->objectService->createFromArray(
                    $mutatieData,
                    [],
                    self::REGISTER_ID_MUTATIES,
                    self::SCHEMA_ID_MUTATIES,
                    true,   // rbac
                    false   // multi (skip tenant collections)
                );
                $dossierId = $mutatie->getObject()['id'] ?? $mutatie->getObject()['dossier_id'] ?? null;
            } else {
                // Fallback: schrijf direct naar openregister_objects zodat het zichtbaar is in Open Register UI
                $mutatieData = $this->prepareMutatieDataForObjectService($data, 'birth');
                $dossierId = $this->createMutatieInOpenRegister($mutatieData);
            }
            
            return new JSONResponse([
                'mutatie_id' => $dossierId,
                'dossierId' => $dossierId,
                'status' => $data['status'] ?? 'ingediend',
                'dossierType' => 'birth',
                'createdAt' => date('c')
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createBirth: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/v1/commitment
     * Nieuw partnerschap aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createCommitment(): JSONResponse {
        try {
            $request = $this->getRequestData();
            
            if (empty($request)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid JSON',
                    'errors' => [
                        [
                            'field' => 'request',
                            'message' => 'Request body must be valid JSON'
                        ]
                    ]
                ], 400);
            }
            
            // Valideer via vrijBRP Logica Service
            $result = $this->validationService->validateCommitment($request);
            
            if (!$result->isValid()) {
                return new JSONResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $result->toErrorArray()
                ], 422);
            }
            
            // Transformeer data (via validation service)
            $data = $this->mapCommitment($request, $result->getTransformedData());
            
            // Gebruik ObjectService als beschikbaar, anders fallback naar directe SQL
            if ($this->objectService) {
                $mutatieData = $this->prepareMutatieDataForObjectService($data, 'partnership');
                $mutatie = $this->objectService->createFromArray(
                    $mutatieData,
                    [],
                    self::REGISTER_ID_MUTATIES,
                    self::SCHEMA_ID_MUTATIES,
                    true,   // rbac
                    false   // multi (skip tenant collections)
                );
                $dossierId = $mutatie->getObject()['id'] ?? $mutatie->getObject()['dossier_id'] ?? null;
            } else {
                // Fallback: schrijf direct naar openregister_objects zodat het zichtbaar is in Open Register UI
                $mutatieData = $this->prepareMutatieDataForObjectService($data, 'partnership');
                $dossierId = $this->createMutatieInOpenRegister($mutatieData);
            }
            
            return new JSONResponse([
                'mutatie_id' => $dossierId,
                'dossierId' => $dossierId,
                'status' => $data['status'] ?? 'ingediend',
                'dossierType' => 'commitment',
                'createdAt' => date('c')
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createCommitment: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/v1/deaths/in-municipality
     * Nieuw overlijden aanmaken
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createDeath(): JSONResponse {
        try {
            $request = $this->getRequestData();
            
            if (empty($request)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid JSON',
                    'errors' => [
                        [
                            'field' => 'request',
                            'message' => 'Request body must be valid JSON'
                        ]
                    ]
                ], 400);
            }
            
            // Valideer via vrijBRP Logica Service
            $result = $this->validationService->validateDeath($request);
            
            if (!$result->isValid()) {
                return new JSONResponse([
                    'status' => 422,
                    'title' => 'Unprocessable Entity',
                    'detail' => 'Validation failed',
                    'errors' => $result->toErrorArray()
                ], 422);
            }
            
            // Transformeer data (via validation service)
            $transformedData = $result->getTransformedData() ?? $request;

            // Gebruik ObjectService als beschikbaar, anders fallback naar directe SQL
            if ($this->objectService) {
                $mutatieData = $this->prepareMutatieDataForObjectService($transformedData, 'death');
                $mutatie = $this->objectService->createFromArray(
                    $mutatieData,
                    [],
                    self::REGISTER_ID_MUTATIES,
                    self::SCHEMA_ID_MUTATIES,
                    true,   // rbac
                    false   // multi (skip tenant collections)
                );
                $mutatieObject = $mutatie->getObject();
                $dossierId = $mutatieObject['id'] ?? $mutatieObject['dossier_id'] ?? null;
            } else {
                // Fallback: schrijf direct naar openregister_objects zodat het zichtbaar is in Open Register UI
                $mutatieData = $this->prepareMutatieDataForObjectService($transformedData, 'death');
                try {
                    $dossierId = $this->createMutatieInOpenRegister($mutatieData);
                    if (empty($dossierId)) {
                        error_log("createMutatieInOpenRegister returned empty dossierId for death mutation");
                        throw new \Exception("Failed to create mutatie: empty dossier ID");
                    }
                } catch (\Exception $e) {
                    error_log("Error in createMutatieInOpenRegister: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    throw $e; // Re-throw zodat de error response wordt gegenereerd
                }
            }

            return new JSONResponse([
                'mutatie_id' => $dossierId,
                'dossierId' => $dossierId,
                'status' => $transformedData['status'] ?? 'ingediend',
                'dossierType' => 'death',
                'createdAt' => date('c'),
                'person_bsn' => $transformedData['person_bsn'] ?? null,
                'death_date' => $transformedData['death_date'] ?? null,
                'death_place' => $transformedData['death_place'] ?? null
            ], 201);
            
        } catch (\Exception $e) {
            error_log("Error in createDeath: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/mutaties/{id}
     * Haal mutatie status op
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMutatie(string $id): JSONResponse {
        try {
            // Probeer eerst via ObjectService als beschikbaar
            if ($this->objectService) {
                try {
                    // Zoek mutatie op dossier_id in Open Register
                    $mutatie = $this->objectService->getById(
                        $id,
                        self::REGISTER_ID_MUTATIES,
                        self::SCHEMA_ID_MUTATIES
                    );
                    
                    if ($mutatie) {
                        $mutatieObject = $mutatie->getObject();
                        $documents = $mutatieObject['documents'] ?? [];
                        if (is_string($documents)) {
                            $documents = json_decode($documents, true) ?? [];
                        }
                        
                        $persoonStatus = ($mutatieObject['mutation_type'] ?? '') === 'death' ? 'overleden' : 'onbekend';
                        
                        return new JSONResponse([
                            'mutatie_id' => $mutatieObject['id'] ?? $mutatieObject['dossier_id'] ?? $id,
                            'dossier_id' => $mutatieObject['dossier_id'] ?? $mutatieObject['id'] ?? $id,
                            'mutation_type' => $mutatieObject['mutation_type'] ?? null,
                            'status' => $mutatieObject['status'] ?? null,
                            'person_bsn' => $mutatieObject['person_bsn'] ?? null,
                            'death_date' => $mutatieObject['death_date'] ?? null,
                            'death_place' => $mutatieObject['death_place'] ?? null,
                            'zaak_id' => $mutatieObject['zaak_id'] ?? $mutatieObject['reference_id'] ?? null,
                            'documents' => $documents,
                            'persoon_status' => $persoonStatus,
                            'created_at' => $mutatieObject['created_at'] ?? null,
                            'updated_at' => $mutatieObject['updated_at'] ?? null
                        ], 200);
                    }
                } catch (\Exception $e) {
                    // Fallback naar directe SQL als ObjectService faalt
                    error_log("ObjectService getMutatie failed, falling back to SQL: " . $e->getMessage());
                }
            }
            
            // Fallback: probeer eerst uit openregister_objects
            try {
                $qb = $this->db->getQueryBuilder();
                $qb->select('*')
                   ->from('openregister_objects')
                   ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_MUTATIES)))
                   ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_MUTATIES)))
                   ->andWhere($qb->expr()->orX(
                       $qb->expr()->eq(
                           $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.id') . '))'),
                           $qb->createNamedParameter($id)
                       ),
                       $qb->expr()->eq(
                           $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.dossier_id') . '))'),
                           $qb->createNamedParameter($id)
                       )
                   ))
                   ->setMaxResults(1);
                
                $result = $qb->executeQuery();
                $row = $result->fetch();
                
                if ($row) {
                    $objectData = json_decode($row['object'], true);
                    if ($objectData) {
                        $documents = $objectData['documents'] ?? [];
                        if (is_string($documents)) {
                            $documents = json_decode($documents, true) ?? [];
                        }
                        
                        $persoonStatus = ($objectData['mutation_type'] ?? '') === 'death' ? 'overleden' : 'onbekend';
                        
                        return new JSONResponse([
                            'mutatie_id' => $objectData['id'] ?? $objectData['dossier_id'] ?? $id,
                            'dossier_id' => $objectData['dossier_id'] ?? $objectData['id'] ?? $id,
                            'mutation_type' => $objectData['mutation_type'] ?? null,
                            'status' => $objectData['status'] ?? null,
                            'person_bsn' => $objectData['person_bsn'] ?? null,
                            'death_date' => $objectData['death_date'] ?? null,
                            'death_place' => $objectData['death_place'] ?? null,
                            'zaak_id' => $objectData['zaak_id'] ?? $objectData['reference_id'] ?? null,
                            'documents' => $documents,
                            'persoon_status' => $persoonStatus,
                            'created_at' => $objectData['created_at'] ?? $row['created'] ?? null,
                            'updated_at' => $objectData['updated_at'] ?? $row['updated'] ?? null
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                error_log("Error reading from openregister_objects: " . $e->getMessage());
            }
            
            // Laatste fallback: probeer uit oc_openregister_mutaties
            $row = $this->mutatieDb->getMutatieByDossierId($id);
            if (!$row) {
                return new JSONResponse([
                    'status' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Mutatie niet gevonden'
                ], 404);
            }

            $documents = [];
            $documentsRaw = $row['documents'] ?? null;
            if (!empty($documentsRaw)) {
                $decoded = json_decode($documentsRaw, true);
                if (is_array($decoded)) {
                    $documents = $decoded;
                }
            }

            $persoonStatus = $row['mutation_type'] === 'death' ? 'overleden' : 'onbekend';

            return new JSONResponse([
                'mutatie_id' => $row['dossier_id'],
                'dossier_id' => $row['dossier_id'],
                'mutation_type' => $row['mutation_type'],
                'status' => $row['status'],
                'person_bsn' => $row['person_bsn'] ?? null,
                'death_date' => $row['death_date'] ?? null,
                'death_place' => $row['death_place'] ?? null,
                'zaak_id' => $row['reference_id'] ?? null,
                'documents' => $documents,
                'persoon_status' => $persoonStatus,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null
            ], 200);
        } catch (\Exception $e) {
            error_log("Error in getMutatie: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Maak mutatie aan in Open Register objects tabel (fallback wanneer ObjectService niet beschikbaar is)
     */
    private function createMutatieInOpenRegister(array $mutatieData): string {
        $qb = $this->db->getQueryBuilder();
        
        $dossierId = $mutatieData['id'] ?? $mutatieData['dossier_id'] ?? 'DOSSIER-' . date('Ymd') . '-' . uniqid();
        $mutatieData['id'] = $dossierId;
        $mutatieData['dossier_id'] = $dossierId;
        
        // Genereer UUID voor object
        $objectUuid = bin2hex(random_bytes(16));
        $objectUuid = substr($objectUuid, 0, 8) . '-' . substr($objectUuid, 8, 4) . '-' . substr($objectUuid, 12, 4) . '-' . substr($objectUuid, 16, 4) . '-' . substr($objectUuid, 20, 12);
        
        // Converteer naar JSON
        $objectJson = json_encode($mutatieData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($objectJson === false) {
            $error = "JSON encoding failed: " . json_last_error_msg();
            error_log($error);
            throw new \Exception($error);
        }
        
        error_log("Creating mutatie in openregister_objects: dossier_id=$dossierId, mutation_type=" . ($mutatieData['mutation_type'] ?? 'unknown'));
        
        // Genereer URI (vereist veld)
        $uri = '/api/objects/' . self::REGISTER_ID_MUTATIES . '/' . self::SCHEMA_ID_MUTATIES . '/' . $dossierId;
        
        // Insert in openregister_objects (Nextcloud query builder voegt automatisch oc_ prefix toe)
        $qb->insert('openregister_objects')
            ->setValue('uuid', $qb->createNamedParameter($objectUuid))
            ->setValue('register', $qb->createNamedParameter(self::REGISTER_ID_MUTATIES))
            ->setValue('schema', $qb->createNamedParameter(self::SCHEMA_ID_MUTATIES))
            ->setValue('object', $qb->createNamedParameter($objectJson))
            ->setValue('version', $qb->createNamedParameter('1'))
            ->setValue('uri', $qb->createNamedParameter($uri))
            ->setValue('created', $qb->createNamedParameter(new \DateTime(), \OCP\DB\Types::DATETIME))
            ->setValue('updated', $qb->createNamedParameter(new \DateTime(), \OCP\DB\Types::DATETIME));
        
        try {
            $qb->executeStatement();
            error_log("Successfully created mutatie in openregister_objects: dossier_id=$dossierId");
            
            // Schrijf ook naar oc_openregister_mutaties voor backward compatibility
            $mutationType = $mutatieData['mutation_type'] ?? 'relocation';
            try {
                switch ($mutationType) {
                    case 'death':
                        $this->mutatieDb->createDeath($mutatieData);
                        break;
                    case 'birth':
                        $this->mutatieDb->createBirth($mutatieData);
                        break;
                    case 'partnership':
                        $this->mutatieDb->createPartnership($mutatieData);
                        break;
                    case 'relocation':
                    default:
                        $this->mutatieDb->createRelocation($mutatieData);
                        break;
                }
            } catch (\Exception $e) {
                // Ignore - backward compatibility only
                error_log("Backward compatibility write failed: " . $e->getMessage());
            }
            
            return $dossierId;
        } catch (\Exception $e) {
            error_log("Error creating mutatie in openregister_objects: " . $e->getMessage());
            error_log("SQL: " . $qb->getSQL());
            error_log("Parameters: " . print_r($qb->getParameters(), true));
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Fallback naar oude tabel als laatste redmiddel
            $mutationType = $mutatieData['mutation_type'] ?? 'relocation';
            error_log("Falling back to oc_openregister_mutaties for mutation_type=$mutationType");
            switch ($mutationType) {
                case 'death':
                    return $this->mutatieDb->createDeath($mutatieData);
                case 'birth':
                    return $this->mutatieDb->createBirth($mutatieData);
                case 'partnership':
                    return $this->mutatieDb->createPartnership($mutatieData);
                case 'relocation':
                default:
                    return $this->mutatieDb->createRelocation($mutatieData);
            }
        }
    }
    
    /**
     * Bereid mutatie data voor voor ObjectService
     */
    private function prepareMutatieDataForObjectService(array $data, string $mutationType): array {
        $dossierId = $data['dossier_id'] ?? 'DOSSIER-' . date('Ymd') . '-' . uniqid();
        
        $mutatieData = [
            'id' => $dossierId,
            'dossier_id' => $dossierId,
            'mutation_type' => $mutationType,
            'status' => $data['status'] ?? 'ingediend',
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];
        
        // Voeg type-specifieke velden toe
        if ($mutationType === 'death') {
            $mutatieData['person_bsn'] = $data['person_bsn'] ?? null;
            $mutatieData['death_date'] = $data['death_date'] ?? null;
            $mutatieData['death_place'] = $data['death_place'] ?? null;
            $mutatieData['persoon_status'] = 'overleden';
        } elseif ($mutationType === 'birth') {
            $mutatieData['birth_date'] = $data['birth_date'] ?? null;
            $mutatieData['birth_place'] = $data['birth_place'] ?? null;
        } elseif ($mutationType === 'relocation') {
            $mutatieData['relocation_date'] = $data['relocation_date'] ?? null;
        } elseif ($mutationType === 'partnership') {
            $mutatieData['partnership_date'] = $data['partnership_date'] ?? null;
        }
        
        // Voeg referenties toe
        if (isset($data['reference_id'])) {
            $mutatieData['reference_id'] = $data['reference_id'];
            $mutatieData['zaak_id'] = $data['reference_id'];
        } elseif (isset($data['zaak_id'])) {
            $mutatieData['zaak_id'] = $data['zaak_id'];
            $mutatieData['reference_id'] = $data['zaak_id'];
        }
        
        // Voeg documenten toe
        if (isset($data['documents'])) {
            $mutatieData['documents'] = is_array($data['documents']) ? $data['documents'] : [];
        }
        
        // Voeg ruwe payload toe
        if (isset($data['mutation_data'])) {
            $mutatieData['mutation_data'] = $data['mutation_data'];
            $mutatieData['payload_raw'] = is_string($data['mutation_data']) 
                ? $data['mutation_data'] 
                : json_encode($data['mutation_data']);
        }
        
        return $mutatieData;
    }

    /**
     * Mapping helpers
     */
    private function mapRelocation(array $request, ?array $transformedData = null): array {
        // Voorkeur voor getransformeerde data; anders map vanuit request
        if (is_array($transformedData) && !empty($transformedData)) {
            return $transformedData;
        }

        return [
            'status' => 'ingediend',
            'declarant_bsn' => $request['declarant']['bsn'] ?? $request['bsn'] ?? null,
            'relocation_date' => $request['relocationDate'] ?? null,
            'new_postal_code' => $request['newAddress']['postalCode'] ?? null,
            'new_house_number' => $request['newAddress']['houseNumber'] ?? null,
            'new_house_number_addition' => $request['newAddress']['houseNumberAddition'] ?? null,
            'new_street' => $request['newAddress']['street'] ?? null,
            'new_city' => $request['newAddress']['city'] ?? null,
            'new_country' => $request['newAddress']['country'] ?? 'NL',
            'relocator_bsn' => $request['relocator']['bsn'] ?? null,
            'relocator_relationship' => $request['relocator']['relationship'] ?? null,
            'relocator_bsns' => $request['relocators'] ?? null,
            'mutation_data' => $request
        ];
    }

    private function mapBirth(array $request, ?array $transformedData = null): array {
        if (is_array($transformedData) && !empty($transformedData)) {
            return $transformedData;
        }

        return [
            'status' => 'ingediend',
            'birth_date' => $request['child']['birthDate'] ?? $request['birthDate'] ?? null,
            'birth_place' => $request['child']['birthPlace'] ?? $request['birthPlace'] ?? null,
            'first_names' => isset($request['child']['firstName']) ? $request['child']['firstName'] : null,
            'last_name' => $request['child']['lastName'] ?? null,
            'gender' => $request['child']['gender'] ?? null,
            'mother_bsn' => $request['mother']['bsn'] ?? null,
            'father_bsn' => $request['father']['bsn'] ?? null,
            'mutation_data' => $request
        ];
    }

    private function mapCommitment(array $request, ?array $transformedData = null): array {
        if (is_array($transformedData) && !empty($transformedData)) {
            return $transformedData;
        }

        return [
            'status' => 'ingediend',
            'partnership_date' => $request['commitmentDate'] ?? $request['datum'] ?? null,
            'partnership_place' => $request['place'] ?? null,
            'partnership_type' => $request['type'] ?? 'marriage',
            'partner1_bsn' => $request['partner1']['bsn'] ?? $request['bsn'] ?? null,
            'partner2_bsn' => $request['partner2']['bsn'] ?? $request['partnerBsn'] ?? null,
            'mutation_data' => $request
        ];
    }
}
