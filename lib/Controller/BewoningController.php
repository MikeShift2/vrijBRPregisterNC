<?php
/**
 * Bewoning Controller
 * 
 * Implementeert RvIG Bewoning API voor het raadplegen van bewoners van een adres
 * 
 * Referentie: https://developer.rvig.nl/brp-api/bewoning/specificatie/
 * 
 * Endpoints:
 * - GET /adressen/{adresseerbaarObjectIdentificatie}/bewoning
 * 
 * Parameters:
 * - peildatum: Raadpleeg bewoners op specifieke datum (YYYY-MM-DD)
 * - datumVan + datumTot: Raadpleeg bewoners in periode
 * 
 * @package OCA\OpenRegister\Controller
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IDBConnection;

class BewoningController extends Controller {
    
    private IDBConnection $db;
    
    public function __construct(
        string $appName,
        IRequest $request,
        IDBConnection $db
    ) {
        parent::__construct($appName, $request);
        $this->db = $db;
    }
    
    /**
     * GET /adressen/{adresseerbaarObjectIdentificatie}/bewoning
     * 
     * Raadpleeg bewoning van een adres op peildatum of in periode
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @param string $adresseerbaarObjectIdentificatie BAG identificatie van het adres
     * @return JSONResponse
     */
    public function getBewoning(string $adresseerbaarObjectIdentificatie): JSONResponse {
        $startTime = microtime(true);
        
        try {
            // Haal query parameters op
            $peildatum = $this->request->getParam('peildatum');
            $datumVan = $this->request->getParam('datumVan');
            $datumTot = $this->request->getParam('datumTot');
            
            // Validatie: of peildatum, of datumVan+datumTot
            if (!$peildatum && (!$datumVan || !$datumTot)) {
                return $this->createErrorResponse(
                    400,
                    'invalid-params',
                    'Een of meer parameters zijn niet correct',
                    'Specificeer peildatum OF datumVan+datumTot',
                    [
                        [
                            'name' => 'peildatum',
                            'code' => 'required',
                            'reason' => 'Peildatum of periode (datumVan+datumTot) is verplicht'
                        ]
                    ]
                );
            }
            
            // Validatie: niet beide
            if ($peildatum && ($datumVan || $datumTot)) {
                return $this->createErrorResponse(
                    400,
                    'invalid-params',
                    'Een of meer parameters zijn niet correct',
                    'Specificeer peildatum OF datumVan+datumTot, niet beide',
                    [
                        [
                            'name' => 'peildatum',
                            'code' => 'conflicting',
                            'reason' => 'Gebruik peildatum OF periode, niet beide'
                        ]
                    ]
                );
            }
            
            // Valideer datum formaten
            if ($peildatum && !$this->isValidDate($peildatum)) {
                return $this->createErrorResponse(
                    400,
                    'invalid-params',
                    'Een of meer parameters zijn niet correct',
                    'Peildatum heeft ongeldig formaat',
                    [
                        [
                            'name' => 'peildatum',
                            'code' => 'date',
                            'reason' => 'Formaat moet YYYY-MM-DD zijn'
                        ]
                    ]
                );
            }
            
            if ($datumVan && !$this->isValidDate($datumVan)) {
                return $this->createErrorResponse(
                    400,
                    'invalid-params',
                    'Een of meer parameters zijn niet correct',
                    'DatumVan heeft ongeldig formaat',
                    [
                        [
                            'name' => 'datumVan',
                            'code' => 'date',
                            'reason' => 'Formaat moet YYYY-MM-DD zijn'
                        ]
                    ]
                );
            }
            
            if ($datumTot && !$this->isValidDate($datumTot)) {
                return $this->createErrorResponse(
                    400,
                    'invalid-params',
                    'Een of meer parameters zijn niet correct',
                    'DatumTot heeft ongeldig formaat',
                    [
                        [
                            'name' => 'datumTot',
                            'code' => 'date',
                            'reason' => 'Formaat moet YYYY-MM-DD zijn'
                        ]
                    ]
                );
            }
            
            // Haal bewoners op
            if ($peildatum) {
                $bewoning = $this->getBewoningPeildatum($adresseerbaarObjectIdentificatie, $peildatum);
            } else {
                $bewoning = $this->getBewoningPeriode($adresseerbaarObjectIdentificatie, $datumVan, $datumTot);
            }
            
            // Response volgens RvIG HAL JSON formaat
            $response = [
                'adresseerbaarObjectIdentificatie' => $adresseerbaarObjectIdentificatie,
                '_embedded' => [
                    'bewoning' => $bewoning
                ],
                '_links' => [
                    'self' => [
                        'href' => '/adressen/' . $adresseerbaarObjectIdentificatie . '/bewoning'
                    ]
                ]
            ];
            
            // Performance metrics
            $duration = (microtime(true) - $startTime) * 1000;
            $response['_meta'] = [
                'duration_ms' => round($duration, 2),
                'count' => count($bewoning)
            ];
            
            return new JSONResponse($response, 200, [
                'Content-Type' => 'application/hal+json'
            ]);
            
        } catch (\Exception $e) {
            error_log("BewoningController error: " . $e->getMessage());
            
            return $this->createErrorResponse(
                500,
                'server-error',
                'Internal Server Error',
                $e->getMessage()
            );
        }
    }
    
    /**
     * Haal bewoners op voor specifieke peildatum
     * 
     * Bewoners zijn personen die op de peildatum op het adres ingeschreven stonden.
     * 
     * @param string $adresId BAG identificatie
     * @param string $peildatum Datum in YYYY-MM-DD formaat
     * @return array Array met bewoners
     */
    private function getBewoningPeildatum(string $adresId, string $peildatum): array {
        try {
            // Connect naar PostgreSQL probev database
            $pdo = new \PDO(
                'pgsql:host=nextcloud-postgres;port=5432;dbname=bevax',
                'bevax_user',
                'bevax_secure_pass_2024'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Query voor bewoners op peildatum
            // vb_ax tabel bevat verblijfplaats historie met datum_begin en datum_einde
            $sql = "
                SELECT DISTINCT
                    i.snr as burgerservicenummer,
                    i.voornamen,
                    i.voorvoegsel,
                    i.geslachtsnaam,
                    v.datum_begin as datum_aanvang_adres,
                    v.datum_einde as datum_einde_adres,
                    v.bag_id
                FROM probev.vb_ax v
                INNER JOIN probev.inw_ax i ON v.pl_id = i.pl_id AND i.ax = 'A' AND i.hist = 'A'
                WHERE v.ax = 'A'
                  AND v.bag_id = :adres_id
                  AND v.datum_begin <= :peildatum
                  AND (v.datum_einde IS NULL OR v.datum_einde >= :peildatum)
                ORDER BY i.geslachtsnaam, i.voornamen
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':adres_id' => $adresId,
                ':peildatum' => $peildatum
            ]);
            
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Transformeer naar RvIG format
            return $this->transformToBewoningFormat($results, 'peildatum', $peildatum);
            
        } catch (\Exception $e) {
            error_log("BewoningController: Database error for peildatum query: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal bewoners op voor periode
     * 
     * Bewoners zijn personen die in de periode op het adres ingeschreven stonden.
     * 
     * @param string $adresId BAG identificatie
     * @param string $datumVan Start van periode (YYYY-MM-DD)
     * @param string $datumTot Einde van periode (YYYY-MM-DD)
     * @return array Array met bewoningsperiodes
     */
    private function getBewoningPeriode(string $adresId, string $datumVan, string $datumTot): array {
        try {
            // Connect naar PostgreSQL probev database
            $pdo = new \PDO(
                'pgsql:host=nextcloud-postgres;port=5432;dbname=bevax',
                'bevax_user',
                'bevax_secure_pass_2024'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Query voor bewoners in periode
            // Overlap detectie: verblijf overlapt met gevraagde periode
            $sql = "
                SELECT DISTINCT
                    i.snr as burgerservicenummer,
                    i.voornamen,
                    i.voorvoegsel,
                    i.geslachtsnaam,
                    v.datum_begin as datum_aanvang_adres,
                    v.datum_einde as datum_einde_adres,
                    v.bag_id
                FROM probev.vb_ax v
                INNER JOIN probev.inw_ax i ON v.pl_id = i.pl_id AND i.ax = 'A' AND i.hist = 'A'
                WHERE v.ax = 'A'
                  AND v.bag_id = :adres_id
                  AND (
                      -- Verblijf start in periode
                      (v.datum_begin BETWEEN :datum_van AND :datum_tot)
                      -- Verblijf eindigt in periode
                      OR (v.datum_einde BETWEEN :datum_van AND :datum_tot)
                      -- Verblijf omvat hele periode
                      OR (v.datum_begin <= :datum_van AND (v.datum_einde IS NULL OR v.datum_einde >= :datum_tot))
                  )
                ORDER BY v.datum_begin DESC, i.geslachtsnaam, i.voornamen
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':adres_id' => $adresId,
                ':datum_van' => $datumVan,
                ':datum_tot' => $datumTot
            ]);
            
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Transformeer naar RvIG format (gegroepeerd per periode)
            return $this->transformToBewoningPeriodeFormat($results, $datumVan, $datumTot);
            
        } catch (\Exception $e) {
            error_log("BewoningController: Database error for periode query: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Transformeer database resultaten naar RvIG bewoning formaat
     * 
     * @param array $results Database resultaten
     * @param string $type 'peildatum' of 'periode'
     * @param string $datum Peildatum (voor type peildatum)
     * @return array Bewoning in RvIG formaat
     */
    private function transformToBewoningFormat(array $results, string $type, string $datum): array {
        $bewoners = [];
        
        foreach ($results as $row) {
            $bewoner = [
                'burgerservicenummer' => $row['burgerservicenummer'],
                'naam' => [
                    'voornamen' => $row['voornamen']
                ]
            ];
            
            // Voorvoegsel (optioneel)
            if (!empty($row['voorvoegsel'])) {
                $bewoner['naam']['voorvoegsel'] = $row['voorvoegsel'];
            }
            
            // Geslachtsnaam
            $bewoner['naam']['geslachtsnaam'] = $row['geslachtsnaam'];
            
            // Verblijfsperiode
            $bewoner['verblijfplaats'] = [
                'datumAanvangAdreshouding' => [
                    'datum' => $row['datum_aanvang_adres']
                ]
            ];
            
            if (!empty($row['datum_einde_adres'])) {
                $bewoner['verblijfplaats']['datumEindeAdreshouding'] = [
                    'datum' => $row['datum_einde_adres']
                ];
            }
            
            $bewoners[] = $bewoner;
        }
        
        // Voor peildatum: return als flat lijst
        if ($type === 'peildatum') {
            return $bewoners;
        }
        
        return $bewoners;
    }
    
    /**
     * Transformeer database resultaten naar RvIG bewoning periode formaat
     * 
     * Groepeert bewoners per bewoningsperiode
     * 
     * @param array $results Database resultaten
     * @param string $datumVan Start periode
     * @param string $datumTot Einde periode
     * @return array Bewoningen gegroepeerd per periode
     */
    private function transformToBewoningPeriodeFormat(array $results, string $datumVan, string $datumTot): array {
        $bewoningen = [];
        
        // Groepeer per unieke periode (datum_begin + datum_einde combinatie)
        $periodes = [];
        
        foreach ($results as $row) {
            $periodeKey = $row['datum_aanvang_adres'] . '_' . ($row['datum_einde_adres'] ?? 'huidig');
            
            if (!isset($periodes[$periodeKey])) {
                $periodes[$periodeKey] = [
                    'periode' => [
                        'datumVan' => max($row['datum_aanvang_adres'], $datumVan),
                        'datumTot' => $row['datum_einde_adres'] 
                            ? min($row['datum_einde_adres'], $datumTot)
                            : $datumTot
                    ],
                    'bewoners' => []
                ];
            }
            
            $bewoner = [
                'burgerservicenummer' => $row['burgerservicenummer'],
                'naam' => [
                    'voornamen' => $row['voornamen']
                ]
            ];
            
            if (!empty($row['voorvoegsel'])) {
                $bewoner['naam']['voorvoegsel'] = $row['voorvoegsel'];
            }
            
            $bewoner['naam']['geslachtsnaam'] = $row['geslachtsnaam'];
            
            $periodes[$periodeKey]['bewoners'][] = $bewoner;
        }
        
        return array_values($periodes);
    }
    
    /**
     * Valideer datum formaat (YYYY-MM-DD)
     * 
     * @param string $date Datum string
     * @return bool True als geldig
     */
    private function isValidDate(string $date): bool {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Maak RFC 7807 Problem Details error response
     * 
     * @param int $status HTTP status code
     * @param string $type Error type
     * @param string $title Error title
     * @param string $detail Error detail
     * @param array $invalidParams Invalid parameters (optioneel)
     * @return JSONResponse
     */
    private function createErrorResponse(
        int $status,
        string $type,
        string $title,
        string $detail,
        array $invalidParams = []
    ): JSONResponse {
        $error = [
            'type' => 'https://developer.rvig.nl/problems/' . $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $this->request->getRequestUri()
        ];
        
        if (!empty($invalidParams)) {
            $error['invalidParams'] = $invalidParams;
        }
        
        return new JSONResponse($error, $status, [
            'Content-Type' => 'application/problem+json'
        ]);
    }
}
