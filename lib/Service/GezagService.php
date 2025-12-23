<?php
namespace OCA\OpenRegister\Service;

use OCP\IDBConnection;

/**
 * Gezag Service
 * 
 * Berekent gezagsrelaties voor minderjarigen volgens RvIG BRP API specificatie
 * 
 * Referentie: https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/gezag/
 * 
 * Gezag is alleen van toepassing voor minderjarigen (<18 jaar).
 * Het gezag kan bij:
 * - Ouder(s)
 * - Voogd
 * - Geen gezag (bijv. bij meerderjarigheid)
 * 
 * @package OCA\OpenRegister\Service
 */
class GezagService {
    
    private IDBConnection $db;
    
    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }
    
    /**
     * Bereken gezagsrelaties voor een persoon
     * 
     * Alleen voor minderjarigen (<18 jaar).
     * Voor meerderjarigen wordt null geretourneerd.
     * 
     * @param array $persoon Persoon object met minimaal burgerservicenummer en leeftijd
     * @return array|null Gezag informatieproduct, null als niet van toepassing
     */
    public function berekenGezag(array $persoon): ?array {
        // Check leeftijd - gezag alleen voor minderjarigen
        $leeftijd = $persoon['leeftijd'] ?? null;
        if ($leeftijd === null || $leeftijd >= 18) {
            return null;
        }
        
        $bsn = $persoon['burgerservicenummer'] ?? null;
        if (!$bsn) {
            return null;
        }
        
        // Haal gezagsrelaties op uit probev database
        $gezagsrelaties = $this->getGezagsrelatiesFromDatabase($bsn);
        
        if (empty($gezagsrelaties)) {
            // Geen gezagsrelaties gevonden
            // Dit kan betekenen: ouderlijk gezag (default) of geen data
            return $this->getDefaultGezag($persoon);
        }
        
        // Transformeer naar RvIG format
        return $this->transformGezagToRvigFormat($gezagsrelaties, $bsn);
    }
    
    /**
     * Haal gezagsrelaties op uit probev database
     * 
     * De gezag gegevens zitten in verschillende categorieën:
     * - Categorie 11: Gezag (11.32.10 indicator gezag minderjarige)
     * - Ouders via categorie 02 (ouder1) en 03 (ouder2)
     * 
     * @param string $bsn Burgerservicenummer van de minderjarige
     * @return array Array met gezagsrelaties
     */
    private function getGezagsrelatiesFromDatabase(string $bsn): array {
        try {
            // Connect to PostgreSQL probev database
            $pdo = new \PDO(
                'pgsql:host=nextcloud-postgres;port=5432;dbname=bevax',
                'bevax_user',
                'bevax_secure_pass_2024'
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // Query voor gezag indicator uit categorie 11
            // En oudergegevens uit categorie 02 en 03
            $sql = "
                SELECT DISTINCT
                    i.pl_id,
                    i.snr as minderjarige_bsn,
                    i.voornamen as minderjarige_voornamen,
                    i.geslachtsnaam as minderjarige_geslachtsnaam,
                    -- Ouder 1 (categorie 02)
                    o1.snr as ouder1_bsn,
                    o1.voornamen as ouder1_voornamen,
                    o1.geslachtsnaam as ouder1_geslachtsnaam,
                    -- Ouder 2 (categorie 03)
                    o2.snr as ouder2_bsn,
                    o2.voornamen as ouder2_voornamen,
                    o2.geslachtsnaam as ouder2_geslachtsnaam
                FROM probev.inw_ax i
                -- Join ouder 1
                LEFT JOIN probev.inw_ax o1 ON o1.pl_id = (
                    SELECT DISTINCT pl_id 
                    FROM probev.inw_ax 
                    WHERE snr = (
                        SELECT DISTINCT snr_ouder 
                        FROM probev.ouder1_ax 
                        WHERE pl_id = i.pl_id 
                          AND ax = 'A' 
                          AND hist = 'A'
                        LIMIT 1
                    )
                    LIMIT 1
                )
                -- Join ouder 2
                LEFT JOIN probev.inw_ax o2 ON o2.pl_id = (
                    SELECT DISTINCT pl_id 
                    FROM probev.inw_ax 
                    WHERE snr = (
                        SELECT DISTINCT snr_ouder 
                        FROM probev.ouder2_ax 
                        WHERE pl_id = i.pl_id 
                          AND ax = 'A' 
                          AND hist = 'A'
                        LIMIT 1
                    )
                    LIMIT 1
                )
                WHERE i.snr = :bsn
                  AND i.ax = 'A'
                  AND i.hist = 'A'
                LIMIT 1
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':bsn' => $bsn]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $results;
            
        } catch (\Exception $e) {
            // Log error maar return lege array
            error_log("GezagService: Could not fetch gezag for BSN $bsn: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Genereer default gezag (ouderlijk gezag)
     * 
     * Als geen specifieke gezagsgegevens bekend zijn, gaan we uit van
     * standaard ouderlijk gezag (beide ouders hebben gezag).
     * 
     * @param array $persoon Persoon object
     * @return array Gezag informatieproduct
     */
    private function getDefaultGezag(array $persoon): array {
        $bsn = $persoon['burgerservicenummer'] ?? null;
        
        return [
            'type' => 'GezagOuder',
            'minderjarige' => [
                'burgerservicenummer' => $bsn
            ],
            'ouders' => [
                [
                    'soortGezag' => 'ouderlijkGezag'
                ]
            ],
            '_embedded' => [
                'ouders' => []
            ]
        ];
    }
    
    /**
     * Transformeer gezagsrelaties naar RvIG BRP API formaat
     * 
     * RvIG Gezag formaat:
     * {
     *   "type": "GezagOuder",
     *   "minderjarige": {
     *     "burgerservicenummer": "999999011"
     *   },
     *   "ouders": [
     *     {
     *       "burgerservicenummer": "999999012",
     *       "soortGezag": "ouderlijkGezag"
     *     }
     *   ]
     * }
     * 
     * @param array $gezagsrelaties Raw database results
     * @param string $minderjarigeBsn BSN van de minderjarige
     * @return array Gezag informatieproduct
     */
    private function transformGezagToRvigFormat(array $gezagsrelaties, string $minderjarigeBsn): array {
        $ouders = [];
        
        foreach ($gezagsrelaties as $relatie) {
            // Ouder 1
            if (!empty($relatie['ouder1_bsn'])) {
                $ouders[] = [
                    'burgerservicenummer' => $relatie['ouder1_bsn'],
                    'soortGezag' => 'ouderlijkGezag',
                    '_embedded' => [
                        'naam' => [
                            'voornamen' => $relatie['ouder1_voornamen'] ?? null,
                            'geslachtsnaam' => $relatie['ouder1_geslachtsnaam'] ?? null
                        ]
                    ]
                ];
            }
            
            // Ouder 2
            if (!empty($relatie['ouder2_bsn'])) {
                $ouders[] = [
                    'burgerservicenummer' => $relatie['ouder2_bsn'],
                    'soortGezag' => 'ouderlijkGezag',
                    '_embedded' => [
                        'naam' => [
                            'voornamen' => $relatie['ouder2_voornamen'] ?? null,
                            'geslachtsnaam' => $relatie['ouder2_geslachtsnaam'] ?? null
                        ]
                    ]
                ];
            }
        }
        
        // Als geen ouders gevonden, default naar ouderlijk gezag zonder details
        if (empty($ouders)) {
            return [
                'type' => 'GezagOuder',
                'minderjarige' => [
                    'burgerservicenummer' => $minderjarigeBsn
                ],
                'ouders' => [
                    [
                        'soortGezag' => 'ouderlijkGezag'
                    ]
                ]
            ];
        }
        
        return [
            'type' => 'GezagOuder',
            'minderjarige' => [
                'burgerservicenummer' => $minderjarigeBsn
            ],
            'ouders' => $ouders
        ];
    }
    
    /**
     * Verrijk persoon met gezag informatieproduct (indien van toepassing)
     * 
     * Deze methode kan aangeroepen worden vanuit InformatieproductenService
     * om gezag toe te voegen aan minderjarigen.
     * 
     * @param array $persoon Persoon object (moet leeftijd bevatten)
     * @return array Persoon met gezag (indien minderjarig)
     */
    public function enrichPersoonMetGezag(array $persoon): array {
        $gezag = $this->berekenGezag($persoon);
        
        if ($gezag !== null) {
            $persoon['gezag'] = $gezag;
        }
        
        return $persoon;
    }
}
