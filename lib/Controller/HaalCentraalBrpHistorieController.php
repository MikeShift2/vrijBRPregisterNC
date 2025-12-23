<?php
/**
 * Haal Centraal BRP Historie Controller
 * 
 * Implementeert de Haal Centraal BRP Historie API 2.0 specificatie
 * bovenop OpenRegister Personen schema
 * 
 * @see https://brp-api.github.io/Haal-Centraal-BRP-historie-bevragen/redoc
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCA\OpenRegister\Service\ObjectService;
use OCA\OpenRegister\Service\SearchTrailService;
use OCA\OpenRegister\Service\HaalCentraalHistorieValidator;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Db\ObjectEntityMapper;

class HaalCentraalBrpHistorieController extends Controller {
    
    private const REGISTER_ID = 2; // vrijBRPpersonen
    private const REGISTER_ID_ADRESSEN = 3; // Adressen register
    private const SCHEMA_ID_VRIJBRP = 6;   // Personen (niet-GGM)
    private const SCHEMA_ID_GGM = 21;      // GGM IngeschrevenPersoon
    private const SCHEMA_ID_ADRESSEN = 7; // Adressen schema
    
    public function __construct(
        $appName,
        IRequest $request,
        private ObjectService $objectService,
        private SchemaMapper $schemaMapper,
        private ObjectEntityMapper $objectMapper,
        private IDBConnection $db,
        private SearchTrailService $searchTrailService,
        private HaalCentraalHistorieValidator $validator
    ) {
        parent::__construct($appName, $request);
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
     * GET /ingeschrevenpersonen/{burgerservicenummer}/verblijfplaatshistorie
     * Haal Centraal BRP Historie 2.0: Verblijfplaatshistorie van persoon
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getVerblijfplaatshistorie(string $burgerservicenummer): JSONResponse {
        try {
            // Valideer BSN formaat (9 cijfers)
            if (!preg_match('/^\d{9}$/', $burgerservicenummer)) {
                return new JSONResponse([
                    'status' => 400,
                    'title' => 'Bad Request',
                    'detail' => 'Invalid BSN format. BSN must be 9 digits.'
                ], 400);
            }
            
            // Haal historische verblijfplaatsen op uit PostgreSQL
            $verblijfplaatshistorie = $this->getVerblijfplaatshistorieFromPostgres($burgerservicenummer);
            
            if (empty($verblijfplaatshistorie)) {
                return new JSONResponse([
                    '_embedded' => [
                        'verblijfplaatshistorie' => []
                    ],
                    '_links' => [
                        'self' => [
                            'href' => '/ingeschrevenpersonen/' . $burgerservicenummer . '/verblijfplaatshistorie'
                        ],
                        'ingeschrevenpersoon' => [
                            'href' => '/ingeschrevenpersonen/' . $burgerservicenummer
                        ]
                    ]
                ]);
            }
            
            $response = [
                '_embedded' => [
                    'verblijfplaatshistorie' => $verblijfplaatshistorie
                ],
                '_links' => [
                    'self' => [
                        'href' => '/ingeschrevenpersonen/' . $burgerservicenummer . '/verblijfplaatshistorie'
                    ],
                    'ingeschrevenpersoon' => [
                        'href' => '/ingeschrevenpersonen/' . $burgerservicenummer
                    ]
                ]
            ];
            
            // Valideer response tegen Haal Centraal BRP Historie API 2.0 specificatie
            $validation = $this->validator->validateVerblijfplaatshistorieResponse($response);
            
            if (!$validation['valid']) {
                // Log validatiefouten maar blokkeer niet de response (voor development)
                error_log('Haal Centraal Historie API validatiefouten: ' . implode('; ', $validation['errors']));
                
                // In productie zou je hier kunnen kiezen om een error te retourneren
                // Voor nu loggen we alleen en sturen we de response door
                // Uncomment de volgende regels voor strikte validatie:
                /*
                return new JSONResponse([
                    'status' => 500,
                    'title' => 'Validation Error',
                    'detail' => 'Response voldoet niet aan Haal Centraal BRP Historie API 2.0 specificatie',
                    'errors' => $validation['errors']
                ], 500);
                */
            }
            
            return new JSONResponse($response);
            
        } catch (\Exception $e) {
            error_log("Exception in getVerblijfplaatshistorie for BSN $burgerservicenummer: " . $e->getMessage());
            return new JSONResponse([
                'status' => 500,
                'title' => 'Internal Server Error',
                'detail' => $e->getMessage() ?: 'Unknown error occurred'
            ], 500);
        }
    }
    
    /**
     * Haal verblijfplaatshistorie op uit PostgreSQL via vb_ax tabel
     * Retourneert alle historische verblijfplaatsen (niet alleen actuele)
     */
    private function getVerblijfplaatshistorieFromPostgres(string $bsn): array {
        if (empty($bsn)) {
            return [];
        }
        
        try {
            // Directe PostgreSQL connectie via PDO
            $pdo = new \PDO(
                'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                'postgres',
                'postgres',
                [
                    \PDO::ATTR_TIMEOUT => 10,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                ]
            );
            
            // Haal eerst pl_id op via BSN
            $plIdStmt = $pdo->prepare("SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1");
            $plIdStmt->execute(['bsn' => $bsn]);
            $plIdRow = $plIdStmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$plIdRow || !isset($plIdRow['pl_id']) || $plIdRow['pl_id'] == -1) {
                return [];
            }
            
            $plId = $plIdRow['pl_id'];
            
            // Query alle verblijfplaatsen (historisch) via vb_ax tabel
            // vb_ax bevat alle verblijfplaatsen met historie
            // Kolommen: d_geld (geldigheidsdatum), d_aanv (aanvangsdatum/ingangsdatum), d_vertrek (vertrekdatum/einddatum)
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    COALESCE(vb.pc::text, '') as postcode,
                    COALESCE(vb.hnr::text, '') as huisnummer,
                    COALESCE(
                        CASE 
                            WHEN vb.hnr_l != ' ' AND vb.hnr_l != '' THEN vb.hnr_l::text
                            WHEN vb.hnr_t != ' ' AND vb.hnr_t != '' THEN vb.hnr_t::text
                            WHEN vb.hnr_a != ' ' AND vb.hnr_a != '' THEN vb.hnr_a::text
                            ELSE ''
                        END, ''
                    ) as huisnummertoevoeging,
                    COALESCE(s.straat::text, '') as straatnaam,
                    COALESCE(w.plaats::text, '') as woonplaats,
                    vb.d_geld as geldigheidsdatum,
                    vb.d_aanv as ingangsdatum,
                    vb.d_vertrek as einddatum,
                    vb.ax as actueel,
                    vb.hist as historie
                FROM vb_ax vb
                LEFT JOIN straat s ON vb.c_straat = s.c_straat
                LEFT JOIN plaats w ON vb.c_wpl = w.c_plaats
                WHERE vb.pl_id = :pl_id 
                ORDER BY 
                    CASE WHEN vb.d_geld = -1 THEN 99999999 ELSE vb.d_geld END DESC,
                    CASE WHEN vb.d_aanv = -1 THEN 99999999 ELSE vb.d_aanv END DESC,
                    vb.a1, vb.a2, vb.a3
            ");
            $stmt->execute(['pl_id' => $plId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $historie = [];
            foreach ($results as $row) {
                $verblijfplaats = [];
                
                // Basis adresgegevens
                if (!empty($row['straatnaam'])) {
                    $verblijfplaats['straatnaam'] = trim($row['straatnaam']);
                }
                if (!empty($row['huisnummer'])) {
                    $huisnummer = trim($row['huisnummer']);
                    $verblijfplaats['huisnummer'] = is_numeric($huisnummer) ? (int)$huisnummer : $huisnummer;
                }
                if (!empty($row['huisnummertoevoeging'])) {
                    $verblijfplaats['huisnummertoevoeging'] = trim($row['huisnummertoevoeging']);
                }
                if (!empty($row['postcode'])) {
                    $verblijfplaats['postcode'] = trim($row['postcode']);
                }
                if (!empty($row['woonplaats'])) {
                    $verblijfplaats['woonplaatsnaam'] = trim($row['woonplaats']);
                }
                
                // Datum informatie
                if (!empty($row['geldigheidsdatum']) && $row['geldigheidsdatum'] != -1) {
                    $geldigheidsdatum = $this->formatDatumFromInteger($row['geldigheidsdatum']);
                    if ($geldigheidsdatum) {
                        $verblijfplaats['datumAanvangAdres'] = [
                            'datum' => $geldigheidsdatum
                        ];
                    }
                }
                
                if (!empty($row['ingangsdatum']) && $row['ingangsdatum'] != -1) {
                    $ingangsdatum = $this->formatDatumFromInteger($row['ingangsdatum']);
                    if ($ingangsdatum) {
                        $verblijfplaats['datumIngangGeldigheid'] = [
                            'datum' => $ingangsdatum
                        ];
                    }
                }
                
                if (!empty($row['einddatum']) && $row['einddatum'] != -1) {
                    $einddatum = $this->formatDatumFromInteger($row['einddatum']);
                    if ($einddatum) {
                        $verblijfplaats['datumEindeGeldigheid'] = [
                            'datum' => $einddatum
                        ];
                    }
                }
                
                // Alleen toevoegen als er minimaal één veld is
                if (!empty($verblijfplaats)) {
                    $historie[] = $verblijfplaats;
                }
            }
            
            return $historie;
            
        } catch (\Exception $e) {
            error_log("PostgreSQL query error in getVerblijfplaatshistorieFromPostgres for BSN $bsn: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Format datum van integer (JJJJMMDD) naar ISO 8601 (JJJJ-MM-DD)
     */
    private function formatDatumFromInteger(?int $datum): ?string {
        if (empty($datum) || $datum == -1) {
            return null;
        }
        
        $datumStr = str_pad((string)$datum, 8, '0', STR_PAD_LEFT);
        if (strlen($datumStr) !== 8) {
            return null;
        }
        
        $jaar = substr($datumStr, 0, 4);
        $maand = substr($datumStr, 4, 2);
        $dag = substr($datumStr, 6, 2);
        
        // Valideer datum
        if (!checkdate((int)$maand, (int)$dag, (int)$jaar)) {
            return null;
        }
        
        return $jaar . '-' . $maand . '-' . $dag;
    }
}

