<?php
/**
 * Haal Centraal BRP Bevragen Controller
 * 
 * Implementeert de Haal Centraal BRP Bevragen API specificatie
 * bovenop OpenRegister Personen schema
 * 
 * @see https://github.com/BRP-API/Haal-Centraal-BRP-bevragen
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IUserSession;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Service\SearchTrailService;
use OCA\OpenRegister\Service\HaalCentraal\FieldSelectionService;
use OCA\OpenRegister\Service\HaalCentraal\ExpandService;
use OCA\OpenRegister\Service\HaalCentraal\ErrorResponseService;
use OCA\OpenRegister\Service\HaalCentraal\CacheService;
use OCA\OpenRegister\Service\InformatieproductenService;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntityMapper;
use OCP\ICacheFactory;

class HaalCentraalBrpController extends Controller {
    
    private const REGISTER_ID = 2; // vrijBRPpersonen
    private const REGISTER_ID_ADRESSEN = 3; // Adressen register
    private const SCHEMA_ID_VRIJBRP = 6;   // Personen (niet-GGM)
    private const SCHEMA_ID_GGM = 21;      // GGM IngeschrevenPersoon
    private const SCHEMA_ID_ADRESSEN = 7; // Adressen schema
    
    private FieldSelectionService $fieldSelectionService;
    private ExpandService $expandService;
    private ErrorResponseService $errorService;
    private ?CacheService $cacheService = null;
    private InformatieproductenService $informatieproductenService;
    
    public function __construct(
        $appName,
        IRequest $request,
        private ObjectService $objectService,
        private SchemaMapper $schemaMapper,
        private ObjectEntityMapper $objectMapper,
        private IDBConnection $db,
        private SearchTrailService $searchTrailService,
        private IUserSession $userSession,
        ?ICacheFactory $cacheFactory = null
    ) {
        parent::__construct($appName, $request);
        $this->fieldSelectionService = new FieldSelectionService();
        $this->expandService = new ExpandService($this);
        $this->errorService = new ErrorResponseService();
        
        // Maak cache instance voor informatieproducten
        $informatieproductenCache = null;
        if ($cacheFactory) {
            $this->cacheService = new CacheService($cacheFactory);
            $informatieproductenCache = $cacheFactory->createDistributed('informatieproducten');
        }
        
        // Initialiseer informatieproducten service met database en cache
        $this->informatieproductenService = new InformatieproductenService(
            $this->db,
            $informatieproductenCache
        );
    }
    
    /**
     * Bepaal welk schema ID te gebruiken op basis van query parameter
     * ggm=true → GGM schema (ID 21)
     * ggm=false of geen parameter → vrijBRP schema (ID 6, probev data)
     */
    private function getSchemaId(): int {
        $useGgm = $this->request->getParam('ggm');
        if ($useGgm === 'true' || $useGgm === '1') {
            return self::SCHEMA_ID_GGM;
        }
        // Standaard: gebruik vrijBRP (probev data)
        return self::SCHEMA_ID_VRIJBRP;
    }
    
    /**
     * GET /ingeschrevenpersonen
     * Haal Centraal BRP Bevragen: Lijst ingeschreven personen
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getIngeschrevenPersonen(): JSONResponse {
        $startTime = microtime(true);
        try {
            $limit = (int)($this->request->getParam('_limit') ?? 20);
            $page = (int)($this->request->getParam('_page') ?? 1);
            $search = $this->request->getParam('search');
            $bsn = $this->request->getParam('bsn');
            $anummer = $this->request->getParam('anummer');
            $achternaam = $this->request->getParam('achternaam');
            $geboortedatum = $this->request->getParam('geboortedatum');
            $geboortedatumVan = $this->request->getParam('geboortedatumVan');
            $geboortedatumTot = $this->request->getParam('geboortedatumTot');
            $sort = $this->request->getParam('sort');
            $fields = $this->request->getParam('fields');
            $expand = $this->request->getParam('expand');
            $inclusiefRni = $this->request->getParam('inclusiefRni') === 'true';
            $schemaId = $this->getSchemaId();
            
            // Genereer cache key
            $cacheParams = [
                'limit' => $limit,
                'page' => $page,
                'search' => $search,
                'bsn' => $bsn,
                'anummer' => $anummer,
                'achternaam' => $achternaam,
                'geboortedatum' => $geboortedatum,
                'geboortedatumVan' => $geboortedatumVan,
                'geboortedatumTot' => $geboortedatumTot,
                'sort' => $sort,
                'schemaId' => $schemaId,
                'fields' => $fields,
                'expand' => $expand
            ];
            
            // Gebruik caching als beschikbaar
            if ($this->cacheService) {
                $cacheKey = $this->cacheService->generateKey('ingeschrevenpersonen', $cacheParams);
                
                $ingeschrevenPersonen = $this->cacheService->get($cacheKey, function() use ($limit, $page, $search, $schemaId, $bsn, $anummer, $achternaam, $geboortedatum, $geboortedatumVan, $geboortedatumTot, $sort, $fields, $expand) {
                    return $this->fetchAndTransformPersonen($limit, $page, $search, $schemaId, $bsn, $anummer, $achternaam, $geboortedatum, $geboortedatumVan, $geboortedatumTot, $sort, $fields, $expand);
                }, 1800); // Cache 30 minuten
            } else {
                $ingeschrevenPersonen = $this->fetchAndTransformPersonen($limit, $page, $search, $schemaId, $bsn, $anummer, $achternaam, $geboortedatum, $geboortedatumVan, $geboortedatumTot, $sort, $fields, $expand);
            }
            
            // Haal totaal aantal op (niet gecached voor paginatie)
            $objects = $this->getObjectsFromDatabase($limit, $page, $search, $schemaId, $bsn, $anummer, $achternaam, $geboortedatum, $geboortedatumVan, $geboortedatumTot, $sort, $inclusiefRni);
            $totalResults = $objects['pagination']['total'] ?? count($ingeschrevenPersonen);
            
            $resultCount = count($ingeschrevenPersonen);
            $responseTime = (microtime(true) - $startTime) * 1000; // in milliseconds
            
            // Log search trail voor dashboard
            try {
                $queryParams = [
                    '@self' => [
                        'register' => self::REGISTER_ID,
                        'schema' => $schemaId
                    ],
                    '_limit' => $limit,
                    '_page' => $page
                ];
                if ($search) {
                    $queryParams['search'] = $search;
                }
                $this->searchTrailService->createSearchTrail(
                    $queryParams,
                    $resultCount,
                    $totalResults,
                    $responseTime,
                    'sync'
                );
            } catch (\Exception $logError) {
                // Log error maar blokkeer niet de response
                error_log("Failed to log search trail: " . $logError->getMessage());
            }
            
            return new JSONResponse([
                '_embedded' => [
                    'ingeschrevenpersonen' => $ingeschrevenPersonen
                ],
                '_links' => [
                    'self' => [
                        'href' => '/ingeschrevenpersonen'
                    ]
                ],
                'page' => [
                    'number' => $page,
                    'size' => $limit,
                    'totalElements' => $totalResults,
                    'totalPages' => ceil($totalResults / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Haal objecten direct uit database zonder SOLR
     * Ondersteunt specifieke zoekvelden: bsn, anummer, achternaam, geboortedatum
     * Gebruikt JSON_EXTRACT voor efficiënte database filtering
     */
    private function getObjectsFromDatabase(int $limit, int $page, ?string $search = null, int $schemaId = null, ?string $bsn = null, ?string $anummer = null, ?string $achternaam = null, ?string $geboortedatum = null, ?string $geboortedatumVan = null, ?string $geboortedatumTot = null, ?string $sort = null, bool $inclusiefRni = false): array {
        if ($schemaId === null) {
            $schemaId = $this->getSchemaId();
        }
        
        $offset = ($page - 1) * $limit;
        
        // Gebruik database query builder om objecten op te halen
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter($schemaId)));
        
        // Voeg specifieke zoekfilters toe aan WHERE clause
        if ($bsn !== null && $bsn !== '') {
            // Normaliseer BSN: verwijder leading zeros voor vergelijking
            $normalizedBsn = ltrim($bsn, '0');
            
            // Zoek zowel op originele BSN als genormaliseerde BSN (voor leading zero problemen)
            // Ondersteun BEIDE oude ('$.bsn') en nieuwe ('$.burgerservicenummer') veldnamen
            $qb->andWhere($qb->expr()->orX(
                // Nieuwe veldnaam: burgerservicenummer
                $qb->expr()->eq(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . '))'),
                    $qb->createNamedParameter($bsn)
                ),
                $qb->expr()->eq(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . '))'),
                    $qb->createNamedParameter($normalizedBsn)
                ),
                $qb->expr()->like(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . '))'),
                    $qb->createNamedParameter('%' . $normalizedBsn)
                ),
                // FALLBACK: oude veldnaam 'bsn' (voor backward compatibility)
                $qb->expr()->eq(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.bsn') . '))'),
                    $qb->createNamedParameter($bsn)
                ),
                $qb->expr()->eq(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.bsn') . '))'),
                    $qb->createNamedParameter($normalizedBsn)
                ),
                $qb->expr()->like(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.bsn') . '))'),
                    $qb->createNamedParameter('%' . $normalizedBsn)
                )
            ));
        }
        
        if ($anummer !== null && $anummer !== '') {
            // Probeer zowel 'anummer' als 'anr' veld (afhankelijk van schema)
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like(
                    $qb->createFunction('LOWER(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.anummer') . ')))'),
                    $qb->createNamedParameter('%' . strtolower($anummer) . '%')
                ),
                $qb->expr()->like(
                    $qb->createFunction('LOWER(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.anr') . ')))'),
                    $qb->createNamedParameter('%' . strtolower($anummer) . '%')
                )
            ));
        }
        
        if ($achternaam !== null && $achternaam !== '') {
            $qb->andWhere($qb->expr()->like(
                $qb->createFunction('LOWER(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.naam.geslachtsnaam') . ')))'),
                $qb->createNamedParameter('%' . strtolower($achternaam) . '%')
            ));
        }
        
        if ($geboortedatum !== null && $geboortedatum !== '') {
            // Voor GGM schema is het 'geboortedatum', voor vrijBRP is het 'geboorte.datum.datum'
            if ($schemaId === self::SCHEMA_ID_GGM) {
                $qb->andWhere($qb->expr()->like(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboortedatum') . '))'),
                    $qb->createNamedParameter('%' . $geboortedatum . '%')
                ));
            } else {
                $qb->andWhere($qb->expr()->like(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboorte.datum.datum') . '))'),
                    $qb->createNamedParameter('%' . $geboortedatum . '%')
                ));
            }
        }
        
        // Geboortedatum range filters
        if ($geboortedatumVan !== null && $geboortedatumVan !== '') {
            if ($schemaId === self::SCHEMA_ID_GGM) {
                $qb->andWhere($qb->expr()->gte(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboortedatum') . '))'),
                    $qb->createNamedParameter($geboortedatumVan)
                ));
            } else {
                $qb->andWhere($qb->expr()->gte(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboorte.datum.datum') . '))'),
                    $qb->createNamedParameter($geboortedatumVan)
                ));
            }
        }
        
        if ($geboortedatumTot !== null && $geboortedatumTot !== '') {
            if ($schemaId === self::SCHEMA_ID_GGM) {
                $qb->andWhere($qb->expr()->lte(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboortedatum') . '))'),
                    $qb->createNamedParameter($geboortedatumTot)
                ));
            } else {
                $qb->andWhere($qb->expr()->lte(
                    $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.geboorte.datum.datum') . '))'),
                    $qb->createNamedParameter($geboortedatumTot)
                ));
            }
        }
        
        // Sortering
        $this->applySorting($qb, $sort, $schemaId);
        
        $qb->setMaxResults($limit)
           ->setFirstResult($offset);
        
        $result = $qb->executeQuery();
        $dbObjects = $result->fetchAll();
        
        // Haal totaal aantal op (voor paginatie) met dezelfde filters
        $qbCount = $this->db->getQueryBuilder();
        $qbCount->select($qbCount->func()->count('*'))
                ->from('openregister_objects')
                ->where($qbCount->expr()->eq('register', $qbCount->createNamedParameter(self::REGISTER_ID)))
                ->andWhere($qbCount->expr()->eq('schema', $qbCount->createNamedParameter($schemaId)));
        
        // Voeg dezelfde filters toe aan count query
        if ($bsn !== null && $bsn !== '') {
            $qbCount->andWhere($qbCount->expr()->eq(
                $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.burgerservicenummer') . '))'),
                $qbCount->createNamedParameter($bsn)
            ));
        }
        
        if ($anummer !== null && $anummer !== '') {
            $qbCount->andWhere($qbCount->expr()->orX(
                $qbCount->expr()->like(
                    $qbCount->createFunction('LOWER(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.anummer') . ')))'),
                    $qbCount->createNamedParameter('%' . strtolower($anummer) . '%')
                ),
                $qbCount->expr()->like(
                    $qbCount->createFunction('LOWER(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.anr') . ')))'),
                    $qbCount->createNamedParameter('%' . strtolower($anummer) . '%')
                )
            ));
        }
        
        if ($achternaam !== null && $achternaam !== '') {
            $qbCount->andWhere($qbCount->expr()->like(
                $qbCount->createFunction('LOWER(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.naam.geslachtsnaam') . ')))'),
                $qbCount->createNamedParameter('%' . strtolower($achternaam) . '%')
            ));
        }
        
        if ($geboortedatum !== null && $geboortedatum !== '') {
            if ($schemaId === self::SCHEMA_ID_GGM) {
                $qbCount->andWhere($qbCount->expr()->like(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.geboortedatum') . '))'),
                    $qbCount->createNamedParameter('%' . $geboortedatum . '%')
                ));
            } else {
                $qbCount->andWhere($qbCount->expr()->like(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.geboorte.datum.datum') . '))'),
                    $qbCount->createNamedParameter('%' . $geboortedatum . '%')
                ));
            }
        }
        
        // Geboortedatum range filters voor count query
        if ($geboortedatumVan !== null && $geboortedatumVan !== '') {
            if ($schemaId === self::SCHEMA_ID_GGM) {
                $qbCount->andWhere($qbCount->expr()->gte(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.geboortedatum') . '))'),
                    $qbCount->createNamedParameter($geboortedatumVan)
                ));
            } else {
                $qbCount->andWhere($qbCount->expr()->gte(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.geboorte.datum.datum') . '))'),
                    $qbCount->createNamedParameter($geboortedatumVan)
                ));
            }
        }
        
        if ($geboortedatumTot !== null && $geboortedatumTot !== '') {
            if ($schemaId === self::SCHEMA_ID_GGM) {
                $qbCount->andWhere($qbCount->expr()->lte(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.geboortedatum') . '))'),
                    $qbCount->createNamedParameter($geboortedatumTot)
                ));
            } else {
                $qbCount->andWhere($qbCount->expr()->lte(
                    $qbCount->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qbCount->createNamedParameter('$.geboorte.datum.datum') . '))'),
                    $qbCount->createNamedParameter($geboortedatumTot)
                ));
            }
        }
        
        $resultCount = $qbCount->executeQuery();
        $total = (int)$resultCount->fetchOne();
        
        // Transformeer naar verwacht formaat
        $data = [];
        foreach ($dbObjects as $row) {
            $objectData = json_decode($row['object'], true);
            if ($objectData) {
                $data[] = [
                    'object' => $objectData,
                    'uuid' => $row['uuid'],
                    'version' => $row['version']
                ];
            }
        }
        
        // Generieke search filter (als geen specifieke filters zijn opgegeven)
        if ($search && !$bsn && !$anummer && !$achternaam && !$geboortedatum) {
            $searchLower = strtolower($search);
            $filteredData = [];
            foreach ($data as $item) {
                $objectData = $item['object'];
                $matches = false;
                foreach ($objectData as $key => $value) {
                    if (is_string($value) && strpos(strtolower($value), $searchLower) !== false) {
                        $matches = true;
                        break;
                    }
                }
                if ($matches) {
                    $filteredData[] = $item;
                }
            }
            $data = $filteredData;
            $total = count($filteredData);
        }
        
        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ];
    }
    
    /**
     * GET /ingeschrevenpersonen/{burgerservicenummer}
     * Haal Centraal BRP Bevragen: Specifieke persoon op BSN
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getIngeschrevenPersoon(string $burgerservicenummer): JSONResponse {
        $startTime = microtime(true);
        try {
            // Valideer BSN formaat (9 cijfers)
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                $instance = $this->request->getRequestUri();
                $error = $this->errorService->badRequest(
                    'Invalid BSN format. BSN must be 9 digits.',
                    $instance
                );
                return new JSONResponse($error, 400);
            }
            
            $schemaId = $this->getSchemaId();
            
            // Haal query parameters op
            $fields = $this->request->getParam('fields');
            $expand = $this->request->getParam('expand');
            
            // Genereer cache key
            $cacheParams = [
                'bsn' => $burgerservicenummer,
                'schemaId' => $schemaId,
                'fields' => $fields,
                'expand' => $expand
            ];
            
            // Gebruik caching als beschikbaar
            if ($this->cacheService) {
                $cacheKey = $this->cacheService->generateKey("ingeschrevenpersoon:{$burgerservicenummer}", $cacheParams);
                
                $persoon = $this->cacheService->get($cacheKey, function() use ($burgerservicenummer, $schemaId, $expand, $fields) {
                    // Zoek persoon op BSN - gebruik direct database
                    $objects = $this->getPersonByBsnFromDatabase($burgerservicenummer, $schemaId);
                    
                    if (empty($objects['data'])) {
                        return null; // Return null voor 404
                    }
                    
                    $persoon = $this->transformToHaalCentraal($objects['data'][0], $schemaId);
                    
                    // Pas expand toe (relaties automatisch ophalen)
                    if ($expand) {
                        $persoonBsn = $persoon['burgerservicenummer'] ?? null;
                        $persoon = $this->expandService->applyExpand($persoon, $expand, $persoonBsn);
                    }
                    
                    // Pas field selection toe (alleen opgegeven velden teruggeven)
                    if ($fields) {
                        $persoon = $this->fieldSelectionService->applyFieldSelection($persoon, $fields);
                    }
                    
                    return $persoon;
                }, 1800); // Cache 30 minuten
                
                if ($persoon === null) {
                    $instance = $this->request->getRequestUri();
                    $error = $this->errorService->notFound(
                        'Person not found',
                        $instance
                    );
                    return new JSONResponse($error, 404);
                }
            } else {
                // Zoek persoon op BSN - gebruik direct database
                $objects = $this->getPersonByBsnFromDatabase($burgerservicenummer, $schemaId);
                
                if (empty($objects['data'])) {
                    $instance = $this->request->getRequestUri();
                    $error = $this->errorService->notFound(
                        'Person not found',
                        $instance
                    );
                    return new JSONResponse($error, 404);
                }
                
                $persoon = $this->transformToHaalCentraal($objects['data'][0], $schemaId);
                
                // Pas expand toe (relaties automatisch ophalen)
                if ($expand) {
                    $persoonBsn = $persoon['burgerservicenummer'] ?? null;
                    $persoon = $this->expandService->applyExpand($persoon, $expand, $persoonBsn);
                }
                
                // Pas field selection toe (alleen opgegeven velden teruggeven)
                if ($fields) {
                    $persoon = $this->fieldSelectionService->applyFieldSelection($persoon, $fields);
                }
            }
            
            $resultCount = 1;
            $totalResults = 1;
            $responseTime = (microtime(true) - $startTime) * 1000; // in milliseconds
            
            // Log search trail voor dashboard
            try {
                $queryParams = [
                    '@self' => [
                        'register' => self::REGISTER_ID,
                        'schema' => $schemaId
                    ],
                    'bsn' => $burgerservicenummer
                ];
                $this->searchTrailService->createSearchTrail(
                    $queryParams,
                    $resultCount,
                    $totalResults,
                    $responseTime,
                    'sync'
                );
            } catch (\Exception $logError) {
                // Log error maar blokkeer niet de response
                error_log("Failed to log search trail: " . $logError->getMessage());
            }
            
            // Voeg ruwe data toe voor volledige weergave
            $rawData = $objects['data'][0]['object'] ?? [];
            $persoon['_raw'] = $rawData; // Voeg ruwe data toe
            
            return new JSONResponse($persoon);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Transformeer OpenRegister object naar Haal Centraal BRP Bevragen formaat
     */
    private function transformToHaalCentraal(array $object, int $schemaId = null): array {
        if ($schemaId === null) {
            $schemaId = $this->getSchemaId();
        }
        
        $data = $object['object'] ?? $object;
        // Nieuw schema gebruikt 'burgerservicenummer', oude schema gebruikt 'bsn'
        $bsn = $data['burgerservicenummer'] ?? $data['bsn'] ?? null;
        
        // Check of dit een GGM schema is
        $isGgm = ($schemaId === self::SCHEMA_ID_GGM) || isset($data['ggm_objecttype']);
        
        // Check of dit het nieuwe Haal Centraal schema is (schema ID 6 met geneste structuur)
        $isNewHaalCentraalSchema = ($schemaId === self::SCHEMA_ID_VRIJBRP) && isset($data['naam']) && is_array($data['naam']);
        
        if ($isGgm) {
            // GGM formaat transformatie
            $result = [
                'burgerservicenummer' => $bsn,
                'naam' => [
                    'voornamen' => $this->splitVoornamen($data['voornamen'] ?? ''),
                    'geslachtsnaam' => $data['geslachtsnaam'] ?? null,
                    'voorvoegsel' => $data['voorvoegsel'] ?? null,
                ],
                'geboorte' => [
                    'datum' => [
                        'datum' => $data['geboortedatum'] ?? null, // GGM heeft al ISO formaat
                    ],
                ],
                'geslachtsaanduiding' => $data['geslachtsaanduiding'] ?? null,
                'aNummer' => $data['anummer'] ?? null,
                '_links' => [
                    'self' => [
                        'href' => '/ingeschrevenpersonen/' . ($bsn ?? '')
                    ]
                ],
                '_ggm' => [
                    'objecttype' => $data['ggm_objecttype'] ?? null,
                    'model' => $data['ggm_model'] ?? null,
                    'domein' => $data['ggm_domein'] ?? null
                ]
            ];
            
            // Haal adresgegevens op uit Adressen schema (schema ID 7)
            // Tijdelijk uitgeschakeld om timeout te voorkomen
            // $adresData = $this->getAdresFromAdressenSchema($bsn);
            $adresData = null;
            
            // Fallback: als geen adres in Adressen schema, probeer uit GGM object velden
            if (empty($adresData)) {
                if (!empty($data['verblijfplaats_straatnaam']) || !empty($data['verblijfplaats_huisnummer'])) {
                    $adresData = [];
                    
                    if (!empty($data['verblijfplaats_straatnaam'])) {
                        $adresData['straatnaam'] = $data['verblijfplaats_straatnaam'];
                    }
                    
                    if (!empty($data['verblijfplaats_huisnummer'])) {
                        $adresData['huisnummer'] = is_numeric($data['verblijfplaats_huisnummer']) ? (int)$data['verblijfplaats_huisnummer'] : $data['verblijfplaats_huisnummer'];
                    }
                    
                    if (!empty($data['verblijfplaats_huisnummertoevoeging'])) {
                        $adresData['huisnummertoevoeging'] = $data['verblijfplaats_huisnummertoevoeging'];
                    }
                    
                    if (!empty($data['verblijfplaats_postcode'])) {
                        $adresData['postcode'] = $data['verblijfplaats_postcode'];
                    }
                    
                    if (!empty($data['verblijfplaats_woonplaats'])) {
                        $adresData['woonplaats'] = $data['verblijfplaats_woonplaats'];
                    }
                }
            }
            
            // Transformeer adresData naar Haal Centraal formaat
            if (!empty($adresData) && is_array($adresData)) {
                $adres = [];
                
                if (!empty($adresData['straatnaam'])) {
                    $adres['straatnaam'] = $adresData['straatnaam'];
                }
                
                if (!empty($adresData['huisnummer'])) {
                    $adres['huisnummer'] = is_numeric($adresData['huisnummer']) ? (int)$adresData['huisnummer'] : $adresData['huisnummer'];
                }
                
                if (!empty($adresData['huisnummertoevoeging'])) {
                    $adres['huisnummertoevoeging'] = $adresData['huisnummertoevoeging'];
                }
                
                if (!empty($adresData['postcode'])) {
                    $adres['postcode'] = $adresData['postcode'];
                }
                
                if (!empty($adresData['woonplaats'])) {
                    $adres['woonplaatsnaam'] = $adresData['woonplaats'];
                }
                
                if (!empty($adres)) {
                    $result['verblijfplaats'] = $adres;
                }
            }
        } elseif ($isNewHaalCentraalSchema) {
            // Nieuw Haal Centraal schema (schema ID 6) met geneste structuur
            $voornamen = $data['naam']['voornamen'] ?? [];
            // Als voornamen een string is, splits deze; anders gebruik de array
            if (is_string($voornamen)) {
                $voornamen = $this->splitVoornamen($voornamen);
            } elseif (!is_array($voornamen)) {
                $voornamen = [];
            }
            
            $result = [
                'burgerservicenummer' => $bsn,
                'naam' => [
                    'voornamen' => $voornamen,
                    'geslachtsnaam' => $data['naam']['geslachtsnaam'] ?? null,
                    'voorvoegsel' => $data['naam']['voorvoegsel'] ?? null,
                ],
                'geboorte' => [
                    'datum' => [
                        'datum' => $data['geboorte']['datum']['datum'] ?? null,
                    ],
                ],
                'geslachtsaanduiding' => $this->mapGeslacht($data['geslacht']['code'] ?? null),
                'aNummer' => $data['aNummer'] ?? null,
                '_links' => [
                    'self' => [
                        'href' => '/ingeschrevenpersonen/' . ($bsn ?? '')
                    ]
                ]
            ];
            
            // Voeg _embedded relaties toe als die bestaan
            if (isset($data['_embedded']) && is_array($data['_embedded'])) {
                $result['_embedded'] = $data['_embedded'];
            }
            
            // Haal adresgegevens op (fallback naar probev database)
            $adresData = null;
            if (empty($adresData) && $bsn) {
                try {
                    $adresData = $this->getAdresFromProbevDatabase($bsn);
                } catch (\Exception $e) {
                    error_log("Could not fetch address from probev for BSN $bsn: " . $e->getMessage());
                }
            }
            
            // Transformeer adresData naar Haal Centraal formaat
            if ($adresData && is_array($adresData) && !empty($adresData)) {
                $adres = [];
                if (!empty($adresData['straatnaam'])) {
                    $adres['straatnaam'] = $adresData['straatnaam'];
                }
                if (!empty($adresData['huisnummer'])) {
                    $adres['huisnummer'] = is_numeric($adresData['huisnummer']) ? (int)$adresData['huisnummer'] : $adresData['huisnummer'];
                }
                if (!empty($adresData['huisnummertoevoeging'])) {
                    $adres['huisnummertoevoeging'] = $adresData['huisnummertoevoeging'];
                }
                if (!empty($adresData['postcode'])) {
                    $adres['postcode'] = $adresData['postcode'];
                }
                if (!empty($adresData['woonplaats'])) {
                    $adres['woonplaatsnaam'] = $adresData['woonplaats'];
                }
                if (!empty($adres)) {
                    $result['verblijfplaats'] = $adres;
                }
            }
        } else {
            // Oud VrijBRP formaat transformatie (backward compatibility)
            $result = [
                'burgerservicenummer' => $bsn,
                'naam' => [
                    'voornamen' => $this->splitVoornamen($data['voornamen'] ?? ''),
                    'geslachtsnaam' => $data['geslachtsnaam'] ?? null,
                    'voorvoegsel' => $data['voorvoegsel'] ?? null,
                ],
                'geboorte' => [
                    'datum' => [
                        'datum' => $this->formatDatum($data['geboortedatum'] ?? null),
                    ],
                ],
                'geslachtsaanduiding' => $this->mapGeslacht($data['geslacht'] ?? null),
                'aNummer' => $data['anr'] ?? null,
                '_links' => [
                    'self' => [
                        'href' => '/ingeschrevenpersonen/' . ($bsn ?? '')
                    ]
                ]
            ];
            
            // Haal adresgegevens op uit Adressen schema (schema ID 7)
            // Tijdelijk uitgeschakeld om timeout te voorkomen
            // $adresData = $this->getAdresFromAdressenSchema($bsn);
            $adresData = null;
            
            // Fallback 1: als geen adres in Adressen schema, probeer uit personen object
            if (empty($adresData)) {
                $adresData = $data['adres'] ?? null;
            }
            
            // Fallback 2: haal direct uit probev database (voor vrijBRP personen)
            if (empty($adresData) && $bsn) {
                try {
                    $adresData = $this->getAdresFromProbevDatabase($bsn);
                } catch (\Exception $e) {
                    // Silent fail - adresData blijft null
                    error_log("Could not fetch address from probev for BSN $bsn: " . $e->getMessage());
                }
            }
            
            // Check of adresData bestaat en niet leeg is
            if ($adresData && is_array($adresData) && !empty($adresData)) {
                $adres = [];
                
                if (!empty($adresData['straatnaam'])) {
                    $adres['straatnaam'] = $adresData['straatnaam'];
                }
                
                if (!empty($adresData['huisnummer'])) {
                    $adres['huisnummer'] = is_numeric($adresData['huisnummer']) ? (int)$adresData['huisnummer'] : $adresData['huisnummer'];
                }
                
                if (!empty($adresData['huisnummertoevoeging'])) {
                    $adres['huisnummertoevoeging'] = $adresData['huisnummertoevoeging'];
                }
                
                if (!empty($adresData['postcode'])) {
                    $adres['postcode'] = $adresData['postcode'];
                }
                
                if (!empty($adresData['woonplaats'])) {
                    $adres['woonplaatsnaam'] = $adresData['woonplaats'];
                }
                
                // Voeg verblijfplaats toe als er minimaal één veld is
                if (count($adres) > 0) {
                    $result['verblijfplaats'] = $adres;
                }
            }
        }
        
        // Voeg informatieproducten toe (voorletters, leeftijd, adressering)
        $result = $this->informatieproductenService->enrichPersoon($result);
        
        return $result;
    }
    
    /**
     * Splits voornamen string in array
     */
    private function splitVoornamen(?string $voornamen): array {
        if (empty($voornamen)) {
            return [];
        }
        return array_filter(array_map('trim', explode(' ', $voornamen)));
    }
    
    /**
     * Format datum van JJJJMMDD naar ISO 8601 (JJJJ-MM-DD)
     */
    private function formatDatum(?string $datum): ?string {
        if (empty($datum) || strlen($datum) !== 8) {
            return null;
        }
        return substr($datum, 0, 4) . '-' . substr($datum, 4, 2) . '-' . substr($datum, 6, 2);
    }
    
    /**
     * Map geslacht code naar Haal Centraal waarde
     */
    private function mapGeslacht(?string $geslacht): ?string {
        return match($geslacht) {
            'V' => 'vrouw',
            'M' => 'man',
            'O' => 'onbekend',
            default => null,
        };
    }
    
    /**
     * Haal adresgegevens op uit Adressen schema op basis van BSN
     */
    private function getAdresFromAdressenSchema(?string $bsn): ?array {
        if (empty($bsn)) {
            return null;
        }
        
        // Gebruik exact dezelfde aanpak als getObjectsFromDatabase() - directe query
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID_ADRESSEN)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(self::SCHEMA_ID_ADRESSEN)))
           ->andWhere($qb->expr()->eq(
               $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . '))'),
               $qb->createNamedParameter($bsn)
           ))
           ->setMaxResults(1);
        
        try {
            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            
            // Gebruik exact dezelfde aanpak als getObjectsFromDatabase() regel 224-281
            if (!empty($rows)) {
                $row = $rows[0];
                // Direct gebruik van $row['object'] zoals in getObjectsFromDatabase() regel 281
                if (isset($row['object'])) {
                    $adresData = json_decode($row['object'], true);
                    if ($adresData && is_array($adresData) && !empty($adresData)) {
                        // Verwijder BSN uit adresData (wordt niet gebruikt in verblijfplaats)
                        unset($adresData['burgerservicenummer']);
                        // Return adresData alleen als er nog velden zijn na unset
                        if (!empty($adresData)) {
                            return $adresData;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log exception silently
        }
        
        return null;
    }
    
    /**
     * Haal adresgegevens direct uit probev database via BSN
     * Gebruikt dezelfde query als de Python scripts
     */
    private function getAdresFromProbevDatabase(?string $bsn): ?array {
        if (empty($bsn)) {
            return null;
        }
        
        try {
            // Directe PostgreSQL connectie via PDO (zoals andere functies in deze controller)
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres',
                [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]
            );
            
            // Query adres via BSN (zoals in import-adressen-naar-openregister.py)
            $stmt = $pdo->prepare("
                SELECT 
                    COALESCE(vb.pc::text, '') as postcode,
                    COALESCE(vb.hnr::text, '') as huisnummer,
                    COALESCE(vb.hnr_t::text, '') as huisnummertoevoeging,
                    COALESCE(s.straat::text, '') as straatnaam,
                    COALESCE(w.wpl::text, '') as woonplaats
                FROM pl p
                JOIN vb vb ON p.a1 = vb.a1 AND p.a2 = vb.a2 AND p.a3 = vb.a3
                LEFT JOIN straat s ON vb.c_straat = s.c_straat
                LEFT JOIN wpl w ON vb.c_wpl = w.c_wpl
                WHERE p.bsn = :bsn 
                AND (vb.d_geld = -1 OR vb.d_geld > 20200000)
                ORDER BY vb.d_geld DESC
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($row) {
                $adresData = [];
                
                if (!empty($row['postcode'])) {
                    $adresData['postcode'] = trim($row['postcode']);
                }
                if (!empty($row['huisnummer'])) {
                    $adresData['huisnummer'] = trim($row['huisnummer']);
                }
                if (!empty($row['huisnummertoevoeging'])) {
                    $adresData['huisnummertoevoeging'] = trim($row['huisnummertoevoeging']);
                }
                if (!empty($row['straatnaam'])) {
                    $adresData['straatnaam'] = trim($row['straatnaam']);
                }
                if (!empty($row['woonplaats'])) {
                    $adresData['woonplaats'] = trim($row['woonplaats']);
                }
                
                if (!empty($adresData)) {
                    return $adresData;
                }
            }
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getAdresFromProbevDatabase for BSN $bsn: " . $e->getMessage());
            // Return null zodat andere fallbacks kunnen worden geprobeerd
        }
        
        return null;
    }
    
    /**
     * Zoek persoon op BSN direct uit database zonder SOLR
     * Gebruikt JSON_EXTRACT voor efficiënte query
     */
    private function getPersonByBsnFromDatabase(string $bsn, int $schemaId = null): array {
        if ($schemaId === null) {
            $schemaId = $this->getSchemaId();
        }
        
        // Gebruik JSON_UNQUOTE(JSON_EXTRACT()) om direct op BSN te zoeken in de database
        // Dit is veel efficiënter dan alle objecten ophalen en filteren
        // JSON_EXTRACT retourneert waarden met quotes, JSON_UNQUOTE verwijdert die
        // Normaliseer BSN: verwijder leading zeros en pad naar 9 cijfers
        $normalizedBsn = ltrim($bsn, '0');
        if (strlen($normalizedBsn) < 9) {
            $normalizedBsn = str_pad($normalizedBsn, 9, '0', STR_PAD_LEFT);
        }
        
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('openregister_objects')
           ->where($qb->expr()->eq('register', $qb->createNamedParameter(self::REGISTER_ID)))
           ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter($schemaId)))
           ->andWhere($qb->expr()->orX(
               // Exact match op originele BSN
               $qb->expr()->eq(
                   $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . '))'),
                   $qb->createNamedParameter($bsn)
               ),
               // Match op genormaliseerde BSN (met leading zeros)
               $qb->expr()->eq(
                   $qb->createFunction('LPAD(JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . ')), 9, ' . $qb->createNamedParameter('0') . ')'),
                   $qb->createNamedParameter($normalizedBsn)
               ),
               // Match op BSN zonder leading zeros (MariaDB/MySQL compatible)
               $qb->expr()->eq(
                   $qb->createFunction('TRIM(LEADING ' . $qb->createNamedParameter('0') . ' FROM JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . ')))'),
                   $qb->createNamedParameter(ltrim($bsn, '0'))
               )
           ))
           ->setMaxResults(1);
        
        $result = $qb->executeQuery();
        $row = $result->fetch();
        
        if ($row) {
            $objectData = json_decode($row['object'], true);
            if ($objectData) {
                return [
                    'data' => [[
                        'object' => $objectData,
                        'uuid' => $row['uuid'],
                        'version' => $row['version']
                    ]]
                ];
            }
        }
        
        return ['data' => []];
    }
    
    /**
     * GET /ingeschrevenpersonen/{burgerservicenummer}/partners
     * Haal Centraal BRP Bevragen: Partners van persoon
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPartners(string $burgerservicenummer): JSONResponse {
        try {
            // Valideer BSN formaat
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid BSN format. BSN must be 9 digits.'
                ], 400);
            }
            
            // Haal persoon op om pl_id te krijgen
            $persoon = $this->getPersonByBsnFromDatabase($burgerservicenummer, $this->getSchemaId());
            if (empty($persoon['data'])) {
                return new JSONResponse([
                    'status' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Person not found'
                ], 404);
            }
            
            // Haal pl_id uit persoon data (via view of direct)
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $plId = $persoonData['pl_id'] ?? null;
            
            // Als pl_id niet beschikbaar is, haal het op via BSN uit PostgreSQL
            if (!$plId) {
                $plId = $this->getPlIdFromBsn($burgerservicenummer);
            }
            
            if (!$plId) {
                return new JSONResponse([
                    '_embedded' => [
                        'partners' => []
                    ]
                ]);
            }
            
            // Probeer eerst partners uit Open Register te halen (_embedded)
            $partners = [];
            $embeddedPartners = $persoonData['_embedded']['partners'] ?? null;
            
            if (!empty($embeddedPartners) && is_array($embeddedPartners)) {
                // Partners zijn al beschikbaar in Open Register
                $partners = $embeddedPartners;
            } else {
                // Fallback: haal partners op uit PostgreSQL
                $partners = $this->getPartnersFromPostgres($plId, $burgerservicenummer);
            }
            
            return new JSONResponse([
                '_embedded' => [
                    'partners' => $partners
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /ingeschrevenpersonen/{burgerservicenummer}/kinderen
     * Haal Centraal BRP Bevragen: Kinderen van persoon
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getKinderen(string $burgerservicenummer): JSONResponse {
        try {
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid BSN format. BSN must be 9 digits.'
                ], 400);
            }
            
            $persoon = $this->getPersonByBsnFromDatabase($burgerservicenummer, $this->getSchemaId());
            if (empty($persoon['data'])) {
                return new JSONResponse([
                    'status' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Person not found'
                ], 404);
            }
            
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $plId = $persoonData['pl_id'] ?? null;
            
            // Als pl_id niet beschikbaar is, haal het op via BSN uit PostgreSQL
            if (!$plId) {
                $plId = $this->getPlIdFromBsn($burgerservicenummer);
            }
            
            if (!$plId) {
                return new JSONResponse([
                    '_embedded' => [
                        'kinderen' => []
                    ]
                ]);
            }
            
            // Probeer eerst kinderen uit Open Register te halen (_embedded)
            $kinderen = [];
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $embeddedKinderen = $persoonData['_embedded']['kinderen'] ?? null;
            
            if (!empty($embeddedKinderen) && is_array($embeddedKinderen)) {
                // Kinderen zijn al beschikbaar in Open Register
                $kinderen = $embeddedKinderen;
            } else {
                // Fallback: haal kinderen op uit PostgreSQL
                $kinderen = $this->getKinderenFromPostgres($plId);
            }
            
            return new JSONResponse([
                '_embedded' => [
                    'kinderen' => $kinderen
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /ingeschrevenpersonen/{burgerservicenummer}/ouders
     * Haal Centraal BRP Bevragen: Ouders van persoon
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getOuders(string $burgerservicenummer): JSONResponse {
        try {
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid BSN format. BSN must be 9 digits.'
                ], 400);
            }
            
            $persoon = $this->getPersonByBsnFromDatabase($burgerservicenummer, $this->getSchemaId());
            if (empty($persoon['data'])) {
                return new JSONResponse([
                    'status' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Person not found'
                ], 404);
            }
            
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $plId = $persoonData['pl_id'] ?? null;
            
            // Als pl_id niet beschikbaar is, haal het op via BSN uit PostgreSQL
            if (!$plId) {
                $plId = $this->getPlIdFromBsn($burgerservicenummer);
            }
            
            if (!$plId) {
                return new JSONResponse([
                    '_embedded' => [
                        'ouders' => []
                    ]
                ]);
            }
            
            // Probeer eerst ouders uit Open Register te halen (_embedded)
            $ouders = [];
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $embeddedOuders = $persoonData['_embedded']['ouders'] ?? null;
            
            if (!empty($embeddedOuders) && is_array($embeddedOuders)) {
                // Ouders zijn al beschikbaar in Open Register
                $ouders = $embeddedOuders;
            } else {
                // Fallback: haal ouders op uit PostgreSQL
                $ouders = $this->getOudersFromPostgres($plId);
            }
            
            return new JSONResponse([
                '_embedded' => [
                    'ouders' => $ouders
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /ingeschrevenpersonen/{burgerservicenummer}/verblijfplaats
     * Haal Centraal BRP Bevragen: Verblijfplaats van persoon
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getVerblijfplaats(string $burgerservicenummer): JSONResponse {
        try {
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid BSN format. BSN must be 9 digits.'
                ], 400);
            }
            
            // Probeer eerst persoon op te halen (optioneel, voor fallback)
            $persoon = $this->getPersonByBsnFromDatabase($burgerservicenummer, $this->getSchemaId());
            $persoonData = !empty($persoon['data']) ? $persoon['data'][0]['object'] ?? [] : [];
            
            // Haal adres op (gebruik bestaande methode)
            $adresData = $this->getAdresFromAdressenSchema($burgerservicenummer);
            
            // Fallback 1: probeer uit persoon data (voor GGM schema)
            if (empty($adresData) && !empty($persoonData)) {
                if (!empty($persoonData['verblijfplaats_straatnaam']) || !empty($persoonData['verblijfplaats_huisnummer'])) {
                    $adresData = [];
                    if (!empty($persoonData['verblijfplaats_straatnaam'])) {
                        $adresData['straatnaam'] = $persoonData['verblijfplaats_straatnaam'];
                    }
                    if (!empty($persoonData['verblijfplaats_huisnummer'])) {
                        $adresData['huisnummer'] = is_numeric($persoonData['verblijfplaats_huisnummer']) ? (int)$persoonData['verblijfplaats_huisnummer'] : $persoonData['verblijfplaats_huisnummer'];
                    }
                    if (!empty($persoonData['verblijfplaats_huisnummertoevoeging'])) {
                        $adresData['huisnummertoevoeging'] = $persoonData['verblijfplaats_huisnummertoevoeging'];
                    }
                    if (!empty($persoonData['verblijfplaats_postcode'])) {
                        $adresData['postcode'] = $persoonData['verblijfplaats_postcode'];
                    }
                    if (!empty($persoonData['verblijfplaats_woonplaats'])) {
                        $adresData['woonplaatsnaam'] = $persoonData['verblijfplaats_woonplaats'];
                    }
                }
            }
            
            // Fallback 2: haal direct uit probev database (werkt altijd, ook als persoon niet in OpenRegister staat)
            if (empty($adresData)) {
                try {
                    $adresData = $this->getAdresFromProbevDatabase($burgerservicenummer);
                } catch (\Exception $e) {
                    error_log("Error calling getAdresFromProbevDatabase for BSN $burgerservicenummer: " . $e->getMessage());
                    $adresData = null;
                }
            }
            
            if (empty($adresData)) {
                $instance = $this->request->getRequestUri();
                $error = $this->errorService->notFound(
                    'Verblijfplaats not found for BSN: ' . $burgerservicenummer,
                    $instance
                );
                return new JSONResponse($error, 404);
            }
            
            // Transformeer naar Haal Centraal formaat
            $verblijfplaats = [];
            if (!empty($adresData['straatnaam'])) {
                $verblijfplaats['straatnaam'] = $adresData['straatnaam'];
            }
            if (!empty($adresData['huisnummer'])) {
                $verblijfplaats['huisnummer'] = is_numeric($adresData['huisnummer']) ? (int)$adresData['huisnummer'] : $adresData['huisnummer'];
            }
            if (!empty($adresData['huisnummertoevoeging'])) {
                $verblijfplaats['huisnummertoevoeging'] = $adresData['huisnummertoevoeging'];
            }
            if (!empty($adresData['postcode'])) {
                $verblijfplaats['postcode'] = $adresData['postcode'];
            }
            if (!empty($adresData['woonplaats']) || !empty($adresData['woonplaatsnaam'])) {
                $verblijfplaats['woonplaatsnaam'] = $adresData['woonplaats'] ?? $adresData['woonplaatsnaam'] ?? null;
            }
            
            // Als er geen velden zijn, return 404
            if (empty($verblijfplaats)) {
                $instance = $this->request->getRequestUri();
                $error = $this->errorService->notFound(
                    'Verblijfplaats not found (empty result)',
                    $instance
                );
                return new JSONResponse($error, 404);
            }
            
            return new JSONResponse($verblijfplaats);
            
        } catch (\Exception $e) {
            error_log("Exception in getVerblijfplaats for BSN $burgerservicenummer: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage() ?: 'Unknown error occurred'
            ], 500);
        }
    }
    
    /**
     * GET /ingeschrevenpersonen/{burgerservicenummer}/nationaliteiten
     * Haal Centraal BRP Bevragen: Nationaliteiten van persoon
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getNationaliteiten(string $burgerservicenummer): JSONResponse {
        try {
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid BSN format. BSN must be 9 digits.'
                ], 400);
            }
            
            $persoon = $this->getPersonByBsnFromDatabase($burgerservicenummer, $this->getSchemaId());
            if (empty($persoon['data'])) {
                return new JSONResponse([
                    'status' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Person not found'
                ], 404);
            }
            
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $plId = $persoonData['pl_id'] ?? null;
            
            // Als pl_id niet beschikbaar is, haal het op via BSN uit PostgreSQL
            if (!$plId) {
                $plId = $this->getPlIdFromBsn($burgerservicenummer);
            }
            
            if (!$plId) {
                return new JSONResponse([
                    '_embedded' => [
                        'nationaliteiten' => []
                    ]
                ]);
            }
            
            // Probeer eerst nationaliteiten uit Open Register te halen (_embedded)
            $nationaliteiten = [];
            $persoonData = $persoon['data'][0]['object'] ?? [];
            $embeddedNationaliteiten = $persoonData['_embedded']['nationaliteiten'] ?? null;
            
            if (!empty($embeddedNationaliteiten) && is_array($embeddedNationaliteiten)) {
                // Nationaliteiten zijn al beschikbaar in Open Register
                $nationaliteiten = $embeddedNationaliteiten;
            } else {
                // Fallback: haal nationaliteiten op uit PostgreSQL
                $nationaliteiten = $this->getNationaliteitenFromPostgres($plId);
            }
            
            return new JSONResponse([
                '_embedded' => [
                    'nationaliteiten' => $nationaliteiten
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Haal pl_id op uit PostgreSQL op basis van BSN
     */
    private function getPlIdFromBsn(string $bsn): ?int {
        if (empty($bsn)) {
            return null;
        }
        
        try {
            // Directe PostgreSQL connectie via PDO
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result && isset($result['pl_id']) && is_numeric($result['pl_id']) && $result['pl_id'] != -1) {
                return (int)$result['pl_id'];
            }
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getPlIdFromBsn: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Haal partners op uit PostgreSQL via huw_ax
     */
    private function getPartnersFromPostgres(?int $plId, string $bsn): array {
        if (!$plId) {
            return [];
        }
        
        try {
            // Directe PostgreSQL connectie via PDO
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Query partners via huw_ax
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.bsn 
                FROM huw_ax h 
                JOIN pl p ON p.a1 = h.a1_ref AND p.a2 = h.a2_ref AND p.a3 = h.a3_ref 
                WHERE h.pl_id = :pl_id 
                AND h.ax = 'A' 
                AND h.hist = 'A' 
                AND p.bsn::text != :bsn
            ");
            $stmt->execute(['pl_id' => $plId, 'bsn' => $bsn]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $partners = [];
            foreach ($results as $row) {
                $partnerBsn = $row['bsn'] ?? null;
                if ($partnerBsn && $partnerBsn !== '-1') {
                    $partner = $this->getPersonByBsnFromDatabase($partnerBsn, $this->getSchemaId());
                    if (!empty($partner['data'])) {
                        $partners[] = $this->transformToHaalCentraal($partner['data'][0], $this->getSchemaId());
                    } else {
                        error_log("Partner BSN $partnerBsn niet gevonden in Open Register");
                    }
                }
            }
            
            return $partners;
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getPartnersFromPostgres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal kinderen op uit PostgreSQL via afst_ax
     */
    private function getKinderenFromPostgres(?int $plId): array {
        if (!$plId) {
            return [];
        }
        
        try {
            // Directe PostgreSQL connectie via PDO
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Query kinderen via afst_ax (waar pl_id verwijst naar ouder)
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.bsn 
                FROM afst_ax a 
                JOIN pl p ON p.a1 = a.a1_ref AND p.a2 = a.a2_ref AND p.a3 = a.a3_ref 
                WHERE a.pl_id = :pl_id 
                AND a.ax = 'A' 
                AND a.hist = 'A' 
                AND p.bsn::text != '-1'
            ");
            $stmt->execute(['pl_id' => $plId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $kinderen = [];
            foreach ($results as $row) {
                $kindBsn = $row['bsn'] ?? null;
                if ($kindBsn && $kindBsn !== '-1') {
                    $kind = $this->getPersonByBsnFromDatabase($kindBsn, $this->getSchemaId());
                    if (!empty($kind['data'])) {
                        $kinderen[] = $this->transformToHaalCentraal($kind['data'][0], $this->getSchemaId());
                    }
                }
            }
            
            return $kinderen;
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getKinderenFromPostgres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal ouders op uit PostgreSQL via mdr_ax en vdr_ax
     */
    private function getOudersFromPostgres(?int $plId): array {
        if (!$plId) {
            return [];
        }
        
        try {
            // Directe PostgreSQL connectie via PDO
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $ouders = [];
            
            // Ouder 1 via mdr_ax
            $stmt1 = $pdo->prepare("
                SELECT DISTINCT p.bsn 
                FROM mdr_ax m 
                JOIN pl p ON p.a1 = m.a1_ref AND p.a2 = m.a2_ref AND p.a3 = m.a3_ref 
                WHERE m.pl_id = :pl_id 
                AND m.ax = 'A' 
                AND m.hist = 'A' 
                AND p.bsn::text != '-1' 
                LIMIT 1
            ");
            $stmt1->execute(['pl_id' => $plId]);
            $ouder1Result = $stmt1->fetch(\PDO::FETCH_ASSOC);
            
            if ($ouder1Result && isset($ouder1Result['bsn']) && $ouder1Result['bsn'] !== '-1') {
                $ouder1 = $this->getPersonByBsnFromDatabase($ouder1Result['bsn'], $this->getSchemaId());
                if (!empty($ouder1['data'])) {
                    $ouders[] = $this->transformToHaalCentraal($ouder1['data'][0], $this->getSchemaId());
                }
            }
            
            // Ouder 2 via vdr_ax
            $stmt2 = $pdo->prepare("
                SELECT DISTINCT p.bsn 
                FROM vdr_ax v 
                JOIN pl p ON p.a1 = v.a1_ref AND p.a2 = v.a2_ref AND p.a3 = v.a3_ref 
                WHERE v.pl_id = :pl_id 
                AND v.ax = 'A' 
                AND v.hist = 'A' 
                AND p.bsn::text != '-1' 
                LIMIT 1
            ");
            $stmt2->execute(['pl_id' => $plId]);
            $ouder2Result = $stmt2->fetch(\PDO::FETCH_ASSOC);
            
            if ($ouder2Result && isset($ouder2Result['bsn']) && $ouder2Result['bsn'] !== '-1') {
                $ouder2 = $this->getPersonByBsnFromDatabase($ouder2Result['bsn'], $this->getSchemaId());
                if (!empty($ouder2['data'])) {
                    $ouders[] = $this->transformToHaalCentraal($ouder2['data'][0], $this->getSchemaId());
                }
            }
            
            return $ouders;
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getOudersFromPostgres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal personen op en transformeer naar Haal Centraal formaat
     * Helper methode voor caching
     */
    private function fetchAndTransformPersonen(
        int $limit,
        int $page,
        ?string $search,
        int $schemaId,
        ?string $bsn,
        ?string $anummer,
        ?string $achternaam,
        ?string $geboortedatum,
        ?string $geboortedatumVan,
        ?string $geboortedatumTot,
        ?string $sort,
        ?string $fields,
        ?string $expand
    ): array {
        // Gebruik direct database omdat SOLR mogelijk niet geconfigureerd is
        $inclusiefRni = false; // Default: RNI niet meenemen in dit pad
        $objects = $this->getObjectsFromDatabase($limit, $page, $search, $schemaId, $bsn, $anummer, $achternaam, $geboortedatum, $geboortedatumVan, $geboortedatumTot, $sort, $inclusiefRni);
        
        // Transformeer naar Haal Centraal formaat
        $ingeschrevenPersonen = [];
        foreach ($objects['data'] as $object) {
            $persoon = $this->transformToHaalCentraal($object, $schemaId);
            
            // Pas expand toe (relaties automatisch ophalen)
            if ($expand) {
                $persoonBsn = $persoon['burgerservicenummer'] ?? null;
                $persoon = $this->expandService->applyExpand($persoon, $expand, $persoonBsn);
            }
            
            // Pas field selection toe (alleen opgegeven velden teruggeven)
            if ($fields) {
                $persoon = $this->fieldSelectionService->applyFieldSelection($persoon, $fields);
            }
            
            $ingeschrevenPersonen[] = $persoon;
        }
        
        return $ingeschrevenPersonen;
    }
    
    /**
     * Haal nationaliteiten op uit PostgreSQL via nat_ax
     */
    private function getNationaliteitenFromPostgres(?int $plId): array {
        if (!$plId) {
            return [];
        }
        
        try {
            // Directe PostgreSQL connectie via PDO
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Query nationaliteiten via nat_ax
            $stmt = $pdo->prepare("
                SELECT n.c_natio, nat.natio 
                FROM nat_ax n 
                LEFT JOIN natio nat ON nat.c_natio = n.c_natio 
                WHERE n.pl_id = :pl_id 
                AND n.ax = 'A' 
                AND n.hist = 'A'
            ");
            $stmt->execute(['pl_id' => $plId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $nationaliteiten = [];
            foreach ($results as $row) {
                $code = $row['c_natio'] ?? null;
                $omschrijving = $row['natio'] ?? null;
                
                if ($code) {
                    $nationaliteiten[] = [
                        'nationaliteit' => [
                            'code' => trim($code),
                            'omschrijving' => $omschrijving ? trim($omschrijving) : null
                        ]
                    ];
                }
            }
            
            return $nationaliteiten;
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getNationaliteitenFromPostgres: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Pas sortering toe op query builder
     */
    private function applySorting($qb, ?string $sortParam, int $schemaId): void {
        if (empty($sortParam)) {
            $qb->orderBy('created', 'DESC');
            return;
        }
        
        $sorts = array_map('trim', explode(',', $sortParam));
        $firstSort = true;
        
        foreach ($sorts as $sort) {
            $direction = 'ASC';
            if (strpos($sort, '-') === 0) {
                $direction = 'DESC';
                $sort = substr($sort, 1);
            } elseif (strpos($sort, '+') === 0) {
                $sort = substr($sort, 1);
            }
            
            // Map Haal Centraal veldnamen naar database velden
            $dbField = $this->mapHaalCentraalFieldToDb($sort, $schemaId);
            if ($dbField) {
                if ($firstSort) {
                    $qb->orderBy($dbField, $direction);
                    $firstSort = false;
                } else {
                    $qb->addOrderBy($dbField, $direction);
                }
            }
        }
        
        // Als geen geldige sortering is toegepast, gebruik default
        if ($firstSort) {
            $qb->orderBy('created', 'DESC');
        }
    }
    
    /**
     * Map Haal Centraal veldnaam naar database veld
     */
    private function mapHaalCentraalFieldToDb(string $field, int $schemaId): ?string {
        $isGgm = ($schemaId === self::SCHEMA_ID_GGM);
        
        $mapping = [
            'naam.geslachtsnaam' => "JSON_UNQUOTE(JSON_EXTRACT(object, '$.naam.geslachtsnaam'))",
            'geboorte.datum.datum' => $isGgm
                ? "JSON_UNQUOTE(JSON_EXTRACT(object, '$.geboortedatum'))"
                : "JSON_UNQUOTE(JSON_EXTRACT(object, '$.geboorte.datum.datum'))",
            'burgerservicenummer' => "JSON_UNQUOTE(JSON_EXTRACT(object, '$.burgerservicenummer'))",
            'naam.voornamen' => "JSON_UNQUOTE(JSON_EXTRACT(object, '$.naam.voornamen'))",
        ];
        
        return $mapping[$field] ?? null;
    }
}


