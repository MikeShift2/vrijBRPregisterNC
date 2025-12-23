<?php
/**
 * ZGW Documenten Controller
 * 
 * Implementeert de Documenten API voor ZGW document management
 * Documenten worden opgeslagen in Nextcloud Files en gekoppeld aan zaken
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntityMapper;

class ZgwDocumentController extends Controller {
    
    private const REGISTER_ID_DOCUMENTEN = 6; // Documenten register
    private const SCHEMA_ID_DOCUMENTEN = 23;   // Documenten schema
    private const DOCUMENTEN_FOLDER = 'ZGW Documenten'; // Folder in Nextcloud Files
    
    public function __construct(
        $appName,
        IRequest $request,
        private ObjectService $objectService,
        private SchemaMapper $schemaMapper,
        private ObjectEntityMapper $objectMapper,
        private IDBConnection $db,
        private IRootFolder $rootFolder
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /apps/openregister/zgw/documenten
     * Lijst alle documenten (met filters)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Query parameters:
     * - zaakId: Filter op zaak ID
     * - documentType: Filter op document type
     * - page: Paginanummer (default: 1)
     * - page_size: Aantal resultaten per pagina (default: 20)
     */
    public function getDocumenten(
        ?string $zaakId = null,
        ?string $documentType = null,
        int $page = 1,
        int $page_size = 20
    ): DataResponse {
        try {
            $limit = $page_size;
            $offset = ($page - 1) * $page_size;
            
            // Build filters
            $filters = [];
            if ($zaakId) {
                $filters['zaak_id'] = $zaakId;
            }
            if ($documentType) {
                $filters['document_type'] = $documentType;
            }
            
            // Haal documenten op uit database
            $objects = $this->getDocumentenFromDatabase($limit, $offset, $filters);
            
            // Transformeer naar response formaat
            $documenten = [];
            foreach ($objects['data'] as $object) {
                $documenten[] = $this->transformToDocumentResponse($object);
            }
            
            return new DataResponse([
                'documenten' => $documenten,
                'count' => $objects['pagination']['total'] ?? count($documenten),
                'page' => $page,
                'page_size' => $page_size
            ]);
            
        } catch (\Exception $e) {
            error_log("Error in getDocumenten: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /apps/openregister/zgw/documenten/{documentId}
     * Specifiek document ophalen
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getDocument(string $documentId): DataResponse {
        try {
            $document = $this->getDocumentById($documentId);
            
            if (!$document) {
                return new DataResponse([
                    'error' => 'Document niet gevonden',
                    'detail' => "Document met ID {$documentId} bestaat niet"
                ], 404);
            }
            
            return new DataResponse($this->transformToDocumentResponse($document));
            
        } catch (\Exception $e) {
            error_log("Error in getDocument: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /apps/openregister/zgw/documenten/{documentId}/download
     * Download document bestand
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function downloadDocument(string $documentId): FileDisplayResponse {
        try {
            $document = $this->getDocumentById($documentId);
            
            if (!$document) {
                throw new \Exception("Document niet gevonden");
            }
            
            $data = $document['object'] ?? $document;
            $bestandspad = $data['bestandspad'] ?? null;
            
            if (!$bestandspad) {
                throw new \Exception("Bestandspad niet gevonden");
            }
            
            // Haal bestand op uit Nextcloud Files
            $userFolder = $this->rootFolder->getUserFolder('admin'); // TODO: gebruik huidige gebruiker
            $file = $userFolder->get($bestandspad);
            
            if (!$file instanceof File) {
                throw new NotFoundException("Bestand niet gevonden: {$bestandspad}");
            }
            
            return new FileDisplayResponse($file);
            
        } catch (\Exception $e) {
            error_log("Error in downloadDocument: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /apps/openregister/zgw/documenten
     * Nieuw document aanmaken en uploaden
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createDocument(): DataResponse {
        try {
            $data = $this->request->getParams();
            
            // Valideer required velden
            $required = ['titel', 'document_type'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    return new DataResponse([
                        'error' => 'Bad Request',
                        'detail' => "Required field '{$field}' is missing"
                    ], 400);
                }
            }
            
            // Handle file upload (support 'file' and 'bestand')
            $uploadedFile = $this->request->getUploadedFile('bestand');
            if (!$uploadedFile) {
                $uploadedFile = $this->request->getUploadedFile('file');
            }
            if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
                return new DataResponse([
                    'error' => 'Bad Request',
                    'detail' => 'Geen bestand geüpload of upload fout'
                ], 400);
            }
            
            // Sla bestand op in Nextcloud Files
            $bestandspad = $this->saveFileToNextcloud($uploadedFile, $data);
            
            // Voeg bestandsinformatie toe aan data
            $data['bestandsnaam'] = $uploadedFile['name'];
            $data['bestandspad'] = $bestandspad;
            $data['bestandsgrootte'] = $uploadedFile['size'];
            $data['mime_type'] = $uploadedFile['type'] ?? mime_content_type($uploadedFile['tmp_name']);
            $data['creatiedatum'] = date('c');
            $data['versie'] = $data['versie'] ?? '1.0';
            
            // Maak document aan via Open Register
            $document = $this->objectService->createFromArray(
                $data,
                [],
                self::REGISTER_ID_DOCUMENTEN,
                self::SCHEMA_ID_DOCUMENTEN,
                true,   // rbac
                false   // multi (skip tenant collections)
            );
            
            return new DataResponse($this->transformToDocumentResponse($document), 201);
            
        } catch (\Exception $e) {
            error_log("Error in createDocument: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE /apps/openregister/zgw/documenten/{documentId}
     * Document verwijderen (ook uit Nextcloud Files)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deleteDocument(string $documentId): DataResponse {
        try {
            // Check of document bestaat
            $existingDocument = $this->getDocumentById($documentId);
            if (!$existingDocument) {
                return new DataResponse([
                    'error' => 'Document niet gevonden',
                    'detail' => "Document met ID {$documentId} bestaat niet"
                ], 404);
            }
            
            // Verwijder bestand uit Nextcloud Files
            $data = $existingDocument['object'] ?? $existingDocument;
            $bestandspad = $data['bestandspad'] ?? null;
            
            if ($bestandspad) {
                try {
                    $userFolder = $this->rootFolder->getUserFolder('admin'); // TODO: gebruik huidige gebruiker
                    $file = $userFolder->get($bestandspad);
                    if ($file instanceof File) {
                        $file->delete();
                    }
                } catch (\Exception $e) {
                    error_log("Fout bij verwijderen bestand uit Nextcloud: " . $e->getMessage());
                    // Doorgaan met verwijderen uit database
                }
            }
            
            // Verwijder document via Open Register
            $this->objectService->delete(
                $documentId,
                self::REGISTER_ID_DOCUMENTEN,
                self::SCHEMA_ID_DOCUMENTEN
            );
            
            return new DataResponse(null, 204);
            
        } catch (\Exception $e) {
            error_log("Error in deleteDocument: " . $e->getMessage());
            return new DataResponse([
                'error' => 'Internal server error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sla bestand op in Nextcloud Files
     */
    private function saveFileToNextcloud(array $uploadedFile, array $documentData): string {
        $userFolder = $this->rootFolder->getUserFolder('admin'); // TODO: gebruik huidige gebruiker
        
        // Maak ZGW Documenten folder aan als deze niet bestaat
        if (!$userFolder->nodeExists(self::DOCUMENTEN_FOLDER)) {
            $userFolder->newFolder(self::DOCUMENTEN_FOLDER);
        }
        
        $zgwFolder = $userFolder->get(self::DOCUMENTEN_FOLDER);
        
        // Maak subfolder aan voor zaak als zaak_id is opgegeven
        $zaakId = $documentData['zaak_id'] ?? null;
        if ($zaakId) {
            $zaakFolderName = 'Zaak-' . substr($zaakId, 0, 8);
            if (!$zgwFolder->nodeExists($zaakFolderName)) {
                $zgwFolder->newFolder($zaakFolderName);
            }
            $targetFolder = $zgwFolder->get($zaakFolderName);
        } else {
            $targetFolder = $zgwFolder;
        }
        
        // Genereer unieke bestandsnaam
        $originalName = $uploadedFile['name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $timestamp = date('YmdHis');
        $uniqueName = $baseName . '_' . $timestamp . '.' . $extension;
        
        // Controleer of bestand al bestaat
        $counter = 1;
        while ($targetFolder->nodeExists($uniqueName)) {
            $uniqueName = $baseName . '_' . $timestamp . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        // Sla bestand op
        $file = $targetFolder->newFile($uniqueName, file_get_contents($uploadedFile['tmp_name']));
        
        // Bepaal relatief pad vanaf user folder
        $fullPath = $file->getPath();
        $userPath = $userFolder->getPath();
        $relativePath = str_replace($userPath . '/', '', $fullPath);
        
        return $relativePath;
    }
    
    /**
     * Haal documenten op uit database
     */
    private function getDocumentenFromDatabase(int $limit, int $offset, array $filters = []): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_DOCUMENTEN)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_DOCUMENTEN)))
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
                ->where($qbCount->expr()->eq('register', $qbCount->createNamedParameter(self::REGISTER_ID_DOCUMENTEN)))
                ->andWhere($qbCount->expr()->eq('schema', $qbCount->createNamedParameter(self::SCHEMA_ID_DOCUMENTEN)));
        
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
                        'register' => self::REGISTER_ID_DOCUMENTEN,
                        'schema' => self::SCHEMA_ID_DOCUMENTEN,
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
     * Haal specifiek document op bij ID
     */
    private function getDocumentById(string $documentId): ?array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_DOCUMENTEN)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_DOCUMENTEN)))
           ->andWhere($qb->expr()->eq('uuid', $qb->createNamedParameter($documentId)));
        
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
                'register' => self::REGISTER_ID_DOCUMENTEN,
                'schema' => self::SCHEMA_ID_DOCUMENTEN,
                'created' => $row['created'],
                'updated' => $row['updated']
            ]
        ];
    }
    
    /**
     * Transformeer Open Register object naar Document response formaat
     */
    private function transformToDocumentResponse(array | \OCA\OpenRegister\Db\ObjectEntity $object): array {
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
        $uuid = $object['uuid'] ?? $object['@self']['uuid'] ?? null;
        $baseUrl = $this->request->getServerProtocol() . '://' . $this->request->getServerHost();
        
        return [
            'url' => $baseUrl . '/apps/openregister/zgw/documenten/' . $uuid,
            'documentId' => $uuid,
            'document_id' => $data['document_id'] ?? $uuid,
            'zaakId' => $data['zaak_id'] ?? null,
            'zaak_identificatie' => $data['zaak_identificatie'] ?? null,
            'documentType' => $data['document_type'] ?? '',
            'document_type' => $data['document_type'] ?? '',
            'titel' => $data['titel'] ?? '',
            'beschrijving' => $data['beschrijving'] ?? null,
            'bestandsnaam' => $data['bestandsnaam'] ?? '',
            'bestandspad' => $data['bestandspad'] ?? null,
            'bestandsgrootte' => $data['bestandsgrootte'] ?? 0,
            'mimeType' => $data['mime_type'] ?? '',
            'mime_type' => $data['mime_type'] ?? '',
            'auteur' => $data['auteur'] ?? null,
            'creatiedatum' => $data['creatiedatum'] ?? date('c'),
            'versie' => $data['versie'] ?? '1.0',
            'downloadUrl' => $baseUrl . '/apps/openregister/zgw/documenten/' . $uuid . '/download'
        ];
    }
}




