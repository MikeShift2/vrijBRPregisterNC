<?php
/**
 * ZGW Zaken Controller
 * 
 * Implementeert de ZGW (Zaakgericht Werken) Zaken API specificatie
 * bovenop OpenRegister Zaken schema (ID 20)
 * 
 * @see https://zaken-api.vng.cloud/
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntityMapper;

class ZgwZaakController extends Controller {
    
    private const REGISTER_ID_ZAKEN = 5; // Zaken register
    private const SCHEMA_ID_ZAKEN = 20;   // Zaken schema
    
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
     * GET /apps/openregister/zgw/zaken
     * Lijst alle zaken (ZGW-compliant)
     * 
     * Query parameters:
     * - identificatie: Filter op identificatie
     * - bronorganisatie: Filter op bronorganisatie
     * - zaaktype: Filter op zaaktype
     * - status: Filter op status
     * - page: Paginanummer (default: 1)
     * - page_size: Aantal resultaten per pagina (default: 20)
     */
    public function getZaken(
        ?string $identificatie = null,
        ?string $bronorganisatie = null,
        ?string $zaaktype = null,
        ?string $status = null,
        int $page = 1,
        int $page_size = 20
    ): DataResponse {
        try {
            // Haal zaken op via Open Register ObjectService
            $limit = $page_size;
            $offset = ($page - 1) * $page_size;
            
            // Build filters
            $filters = [];
            if ($identificatie) {
                $filters['identificatie'] = $identificatie;
            }
            if ($bronorganisatie) {
                $filters['bronorganisatie'] = $bronorganisatie;
            }
            if ($zaaktype) {
                $filters['zaaktype'] = $zaaktype;
            }
            if ($status) {
                $filters['status'] = $status;
            }
            
            // Haal objecten op uit Open Register
            $objects = $this->getZakenFromDatabase($limit, $offset, $filters);
            
            // Transformeer naar ZGW formaat
            $zaken = [];
            foreach ($objects['data'] as $object) {
                $zaken[] = $this->transformToZgwZaak($object);
            }
            
            // Bereken paginatie
            $total = $objects['pagination']['total'] ?? count($zaken);
            $next = ($page * $page_size < $total) ? 
                $this->request->getServerProtocol() . '://' . $this->request->getServerHost() . 
                '/apps/openregister/zgw/zaken?page=' . ($page + 1) : null;
            $previous = ($page > 1) ? 
                $this->request->getServerProtocol() . '://' . $this->request->getServerHost() . 
                '/apps/openregister/zgw/zaken?page=' . ($page - 1) : null;
            
            return new DataResponse([
                'count' => $total,
                'next' => $next,
                'previous' => $previous,
                'results' => $zaken
            ]);
            
        } catch (\Exception $e) {
            error_log("Error in getZaken: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /apps/openregister/zgw/zaken/{zaakId}
     * Specifieke zaak ophalen (ZGW-compliant)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getZaak(string $zaakId): DataResponse {
        try {
            // Haal zaak op via Open Register
            $zaak = $this->getZaakById($zaakId);
            
            if (!$zaak) {
                return new DataResponse([
                    'error' => 'Zaak niet gevonden',
                    'detail' => "Zaak met ID {$zaakId} bestaat niet"
                ], 404);
            }
            
            // Transformeer naar ZGW formaat
            $zgwZaak = $this->transformToZgwZaak($zaak);
            
            return new DataResponse($zgwZaak);
            
        } catch (\Exception $e) {
            error_log("Error in getZaak: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /apps/openregister/zgw/zaken
     * Nieuwe zaak aanmaken (ZGW-compliant)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createZaak(): DataResponse {
        try {
            // Haal JSON body op
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            
            // Fallback naar getParams als JSON leeg is
            if (empty($data)) {
                $data = $this->request->getParams();
            }
            
            // Valideer required velden
            $required = ['identificatie', 'bronorganisatie', 'zaaktype', 'registratiedatum', 'startdatum'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return new DataResponse([
                        'error' => 'Bad Request',
                        'detail' => "Required field '{$field}' is missing"
                    ], 400);
                }
            }
            
            // Transformeer ZGW formaat naar Open Register formaat
            $openRegisterData = $this->transformFromZgwZaak($data);
            
            // Maak zaak aan via Open Register
            // multi=false om tenant-collectie check te omzeilen voor deze testpagina
            $zaak = $this->objectService->createFromArray(
                $openRegisterData,
                [],
                self::REGISTER_ID_ZAKEN,
                self::SCHEMA_ID_ZAKEN,
                true,   // rbac
                false   // multi (skip tenant collections)
            );
            
            // Transformeer terug naar ZGW formaat
            $zgwZaak = $this->transformToZgwZaak($zaak);
            
            return new DataResponse($zgwZaak, 201);
            
        } catch (\Exception $e) {
            error_log("Error in createZaak: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * PUT /apps/openregister/zgw/zaken/{zaakId}
     * Zaak bijwerken (ZGW-compliant)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateZaak(string $zaakId): DataResponse {
        try {
            $data = $this->request->getParams();
            
            // Check of zaak bestaat
            $existingZaak = $this->getZaakById($zaakId);
            if (!$existingZaak) {
                return new DataResponse([
                    'error' => 'Zaak niet gevonden',
                    'detail' => "Zaak met ID {$zaakId} bestaat niet"
                ], 404);
            }
            
            // Transformeer ZGW formaat naar Open Register formaat
            $openRegisterData = $this->transformFromZgwZaak($data);
            
            // Update zaak via Open Register
            $zaak = $this->objectService->updateFromArray(
                $zaakId,
                $openRegisterData,
                true, // updateVersion
                false, // patch
                [],
                self::REGISTER_ID_ZAKEN,
                self::SCHEMA_ID_ZAKEN
            );
            
            // Transformeer terug naar ZGW formaat
            $zgwZaak = $this->transformToZgwZaak($zaak);
            
            return new DataResponse($zgwZaak);
            
        } catch (\Exception $e) {
            error_log("Error in updateZaak: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE /apps/openregister/zgw/zaken/{zaakId}
     * Zaak verwijderen (ZGW-compliant)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteZaak(string $zaakId): DataResponse {
        try {
            // Check of zaak bestaat
            $existingZaak = $this->getZaakById($zaakId);
            if (!$existingZaak) {
                return new DataResponse([
                    'error' => 'Zaak niet gevonden',
                    'detail' => "Zaak met ID {$zaakId} bestaat niet"
                ], 404);
            }
            
            // Verwijder zaak via Open Register (delete verwacht een object array)
            $this->objectService->delete($existingZaak);
            
            return new DataResponse(null, 204);
            
        } catch (\Exception $e) {
            error_log("Error in deleteZaak: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Haal zaken op uit database
     */
    private function getZakenFromDatabase(int $limit, int $offset, array $filters = []): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_ZAKEN)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_ZAKEN)))
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
                ->where($qbCount->expr()->eq('register', $qbCount->createNamedParameter(self::REGISTER_ID_ZAKEN)))
                ->andWhere($qbCount->expr()->eq('schema', $qbCount->createNamedParameter(self::SCHEMA_ID_ZAKEN)));
        
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
                        'register' => self::REGISTER_ID_ZAKEN,
                        'schema' => self::SCHEMA_ID_ZAKEN,
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
     * Haal specifieke zaak op bij ID
     */
    private function getZaakById(string $zaakId): ?array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_ZAKEN)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_ZAKEN)))
           ->andWhere($qb->expr()->eq('uuid', $qb->createNamedParameter($zaakId)));
        
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
                'register' => self::REGISTER_ID_ZAKEN,
                'schema' => self::SCHEMA_ID_ZAKEN,
                'created' => $row['created'],
                'updated' => $row['updated']
            ]
        ];
    }
    
    /**
     * Transformeer Open Register object naar ZGW Zaak formaat
     */
    private function transformToZgwZaak(array | \OCA\OpenRegister\Db\ObjectEntity $object): array {
        // Als het een ObjectEntity is, converteer naar array
        if ($object instanceof \OCA\OpenRegister\Db\ObjectEntity) {
            $rawObject = $object->getObject();
            $decoded = is_string($rawObject) ? json_decode($rawObject, true) : $rawObject;

            $object = [
                'uuid' => $object->getUuid(),
                'object' => $decoded,
                '@self' => [
                    'id' => $object->getId(),
                    'uuid' => $object->getUuid(),
                    'register' => $object->getRegister(),
                    'schema' => $object->getSchema(),
                    'created' => $object->getCreated(),
                    'updated' => $object->getUpdated()
                ]
            ];
        }
        
        $data = $object['object'] ?? $object;
        $baseUrl = $this->request->getServerProtocol() . '://' . $this->request->getServerHost();
        $uuid = $object['uuid'] ?? $object['@self']['uuid'] ?? null;
        
        // Parse betrokkeneIdentificaties als JSON string
        $betrokkeneIdentificaties = [];
        if (isset($data['betrokkeneIdentificaties'])) {
            if (is_string($data['betrokkeneIdentificaties'])) {
                $betrokkeneIdentificaties = json_decode($data['betrokkeneIdentificaties'], true) ?? [];
            } else {
                $betrokkeneIdentificaties = $data['betrokkeneIdentificaties'];
            }
        }
        
        return [
            'url' => $baseUrl . '/apps/openregister/zgw/zaken/' . $uuid,
            'identificatie' => $data['identificatie'] ?? '',
            'bronorganisatie' => $data['bronorganisatie'] ?? '',
            'zaaktype' => $data['zaaktype'] ?? '',
            'registratiedatum' => $data['registratiedatum'] ?? date('c'),
            'startdatum' => $data['startdatum'] ?? '',
            'einddatum' => $data['einddatum'] ?? null,
            'status' => $data['status'] ?? '',
            'omschrijving' => $data['omschrijving'] ?? '',
            'toelichting' => $data['toelichting'] ?? null,
            'verantwoordelijkeOrganisatie' => $data['verantwoordelijkeOrganisatie'] ?? '',
            'betrokkeneIdentificaties' => $betrokkeneIdentificaties
        ];
    }
    
    /**
     * Transformeer ZGW Zaak formaat naar Open Register object formaat
     */
    private function transformFromZgwZaak(array $zgwZaak): array {
        // Transformeer betrokkeneIdentificaties naar JSON string
        $betrokkeneIdentificaties = $zgwZaak['betrokkeneIdentificaties'] ?? [];
        if (is_array($betrokkeneIdentificaties)) {
            $betrokkeneIdentificaties = json_encode($betrokkeneIdentificaties);
        }
        
        return [
            'identificatie' => $zgwZaak['identificatie'] ?? '',
            'bronorganisatie' => $zgwZaak['bronorganisatie'] ?? '',
            'zaaktype' => $zgwZaak['zaaktype'] ?? '',
            'registratiedatum' => $zgwZaak['registratiedatum'] ?? date('c'),
            'startdatum' => $zgwZaak['startdatum'] ?? '',
            'einddatum' => $zgwZaak['einddatum'] ?? null,
            'status' => $zgwZaak['status'] ?? '',
            'omschrijving' => $zgwZaak['omschrijving'] ?? '',
            'toelichting' => $zgwZaak['toelichting'] ?? null,
            'verantwoordelijkeOrganisatie' => $zgwZaak['verantwoordelijkeOrganisatie'] ?? '',
            'betrokkeneIdentificaties' => $betrokkeneIdentificaties
        ];
    }
}
