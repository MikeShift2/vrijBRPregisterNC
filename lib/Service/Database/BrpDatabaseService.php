<?php
/**
 * BRP Database Service
 * 
 * Database queries voor validatie tegen probev schema
 */

namespace OCA\OpenRegister\Service\Database;

use PDO;
use PDOException;

class BrpDatabaseService {
    private ?PDO $pdo = null;
    
    private function getConnection(): PDO {
        if ($this->pdo === null) {
            try {
                $this->pdo = new PDO(
                    'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
                    'postgres',
                    'postgres',
                    [
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                error_log("PostgreSQL connection error: " . $e->getMessage());
                throw new \RuntimeException("Database connection failed", 0, $e);
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Check of BSN bestaat in BRP
     */
    public function findPersonByBsn(string $bsn): ?array {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    pl_id,
                    bsn,
                    status,
                    overlijdensdatum,
                    geschorst,
                    d_geboorte
                FROM inw_ax
                WHERE bsn = :bsn
                AND ax = 'A'
                AND hist = 'A'
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch();
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error finding person by BSN: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Haal leeftijd op voor een BSN
     * 
     * @param string $bsn
     * @return int|null Leeftijd in jaren, of null als niet gevonden
     */
    public function getAge(string $bsn): ?int {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    EXTRACT(YEAR FROM AGE(
                        TO_DATE(inw.d_geboorte::text, 'YYYYMMDD')
                    )) as leeftijd
                FROM probev.inw_ax inw
                WHERE inw.bsn = :bsn
                AND inw.ax = 'A'
                AND inw.hist = 'A'
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch();
            
            return $result ? (int)$result['leeftijd'] : null;
        } catch (PDOException $e) {
            error_log("Error getting age: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Haal geboortedatum op als DateTime
     */
    public function getBirthDate(string $bsn): ?\DateTime {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT d_geboorte
                FROM probev.inw_ax
                WHERE bsn = :bsn
                AND ax = 'A'
                AND hist = 'A'
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            $row = $stmt->fetch();
            if (!$row || empty($row['d_geboorte'])) {
                return null;
            }

            // d_geboorte is YYYYMMDD numeriek
            $dateString = (string)$row['d_geboorte'];
            $date = \DateTime::createFromFormat('Ymd', $dateString);
            return $date ?: null;
        } catch (PDOException $e) {
            error_log("Error getting birth date: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check obstructions voor een BSN
     */
    public function checkObstructions(string $bsn): array {
        $obstructions = [];
        
        try {
            $person = $this->findPersonByBsn($bsn);
            
            if (!$person) {
                $obstructions[] = 'NO_PERSON_RECORD_FOUND';
                return $obstructions;
            }
            
            // Check status
            if (isset($person['status']) && $person['status'] === 'BLOCKED') {
                $obstructions[] = 'PERSON_RECORD_IS_BLOCKED';
            }
            
            // Check overlijden
            if (isset($person['overlijdensdatum']) && $person['overlijdensdatum'] !== null) {
                $obstructions[] = 'PERSON_IS_DECEASED';
            }
            
            // Check geschorst
            if (isset($person['geschorst']) && $person['geschorst'] === true) {
                $obstructions[] = 'PERSONLIST_SUSPENDED';
            }
            
        } catch (PDOException $e) {
            error_log("Error checking obstructions: " . $e->getMessage());
        }
        
        return $obstructions;
    }
    
    /**
     * Check of er lopende verhuizingen zijn
     */
    public function findActiveRelocations(string $bsn): array {
        // TODO: Implementeer wanneer dossiers register bestaat
        // Voor nu: return lege array
        return [];
    }
    
    /**
     * Haal relaties op voor een BSN
     */
    public function findRelatives(string $bsn): array {
        // Gebruik bestaande relaties endpoint logica
        // TODO: Implementeer of gebruik HaalCentraalBrpController logica
        return [];
    }
    
    /**
     * Valideer adres (conceptueel - afhankelijk van adresregister)
     */
    public function validateAddress(array $address): bool {
        // TODO: Implementeer wanneer adresregister beschikbaar is
        return true;
    }
    
    /**
     * Check of persoon actueel getrouwd is
     * 
     * @param string $bsn
     * @return bool True als persoon getrouwd is of geregistreerd partnerschap heeft
     */
    public function isPersonMarried(string $bsn): bool {
        try {
            // Methode 1: Via huw_ax tabel (actuele huwelijken/partnerschappen)
            $stmt = $this->getConnection()->prepare("
                SELECT COUNT(*) as count
                FROM probev.huw_ax huw
                WHERE huw.pl_id = (
                    SELECT pl_id FROM probev.inw_ax 
                    WHERE bsn = :bsn 
                    AND ax = 'A' 
                    AND hist = 'A'
                )
                AND huw.ax = 'A'
                AND huw.hist = 'A'
                AND (huw.datum_ontbinding IS NULL OR huw.datum_ontbinding > CURRENT_DATE)
            ");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch();
            
            if ($result && (int)$result['count'] > 0) {
                return true;
            }
            
            // Methode 2: Via PL tabel (burgerlijke staat)
            $stmt = $this->getConnection()->prepare("
                SELECT burgerlijke_staat
                FROM probev.pl
                WHERE bsn = :bsn
                AND overlijdensdatum IS NULL
            ");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch();
            
            if ($result) {
                $maritalStatus = strtolower($result['burgerlijke_staat'] ?? '');
                return in_array($maritalStatus, ['gehuwd', 'geregistreerd_partnerschap', 'gps']);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error checking marriage status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Haal huwelijksinfo op
     * 
     * @param string $bsn
     * @return array|null Array met huwelijksinfo, of null als niet gevonden
     */
    public function getMarriageInfo(string $bsn): ?array {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    huw.datum_huwelijk,
                    huw.datum_ontbinding,
                    huw.soort,
                    huw.pl_id_partner
                FROM probev.huw_ax huw
                WHERE huw.pl_id = (
                    SELECT pl_id FROM probev.inw_ax 
                    WHERE bsn = :bsn 
                    AND ax = 'A' 
                    AND hist = 'A'
                )
                AND huw.ax = 'A'
                AND huw.hist = 'A'
                AND (huw.datum_ontbinding IS NULL OR huw.datum_ontbinding > CURRENT_DATE)
                ORDER BY huw.datum_huwelijk DESC
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getting marriage info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check of persoon onder curatele staat
     * 
     * @param string $bsn
     * @return bool True als persoon onder curatele, bewind of mentorschap staat
     */
    public function isPersonUnderCuratorship(string $bsn): bool {
        try {
            // Methode 1: Via gezag_ax tabel
            $stmt = $this->getConnection()->prepare("
                SELECT COUNT(*) as count
                FROM probev.gezag_ax gezag
                WHERE gezag.pl_id = (
                    SELECT pl_id FROM probev.inw_ax 
                    WHERE bsn = :bsn 
                    AND ax = 'A' 
                    AND hist = 'A'
                )
                AND gezag.ax = 'A'
                AND gezag.hist = 'A'
                AND (gezag.datum_einde IS NULL OR gezag.datum_einde > CURRENT_DATE)
                AND gezag.soort_gezag IN ('curatele', 'bewind', 'mentorschap')
            ");
            $stmt->execute(['bsn' => $bsn]);
            $result = $stmt->fetch();
            
            if ($result && (int)$result['count'] > 0) {
                return true;
            }
            
            // Methode 2: Via PL tabel (als curatele indicator bestaat)
            try {
                $stmt = $this->getConnection()->prepare("
                    SELECT 
                        CASE 
                            WHEN curatele_indicator = true THEN true
                            WHEN datum_curatele_begin IS NOT NULL 
                                AND (datum_curatele_einde IS NULL OR datum_curatele_einde > CURRENT_DATE) 
                            THEN true
                            ELSE false
                        END as is_under_curatorship
                    FROM probev.pl
                    WHERE bsn = :bsn
                    AND overlijdensdatum IS NULL
                ");
                $stmt->execute(['bsn' => $bsn]);
                $result = $stmt->fetch();
                
                return $result && ($result['is_under_curatorship'] === true || $result['is_under_curatorship'] === 't');
            } catch (PDOException $e) {
                // Veld bestaat mogelijk niet, negeer deze check
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error checking curatorship: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Haal curatele info op
     * 
     * @param string $bsn
     * @return array|null Array met curatele info, of null als niet gevonden
     */
    public function getCuratorshipInfo(string $bsn): ?array {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    gezag.soort_gezag,
                    gezag.datum_begin,
                    gezag.datum_einde,
                    gezag.pl_id_curator
                FROM probev.gezag_ax gezag
                WHERE gezag.pl_id = (
                    SELECT pl_id FROM probev.inw_ax 
                    WHERE bsn = :bsn 
                    AND ax = 'A' 
                    AND hist = 'A'
                )
                AND gezag.ax = 'A'
                AND gezag.hist = 'A'
                AND (gezag.datum_einde IS NULL OR gezag.datum_einde > CURRENT_DATE)
                AND gezag.soort_gezag IN ('curatele', 'bewind', 'mentorschap')
                ORDER BY gezag.datum_begin DESC
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getting curatorship info: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Haal huidig adres op voor BSN
     */
    public function getCurrentAddress(string $bsn): ?array {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT 
                    postcode,
                    huisnummer,
                    straat,
                    woonplaats
                FROM v_vb_ax_haal_centraal
                WHERE bsn = :bsn
                ORDER BY ingangsdatum DESC
                LIMIT 1
            ");
            $stmt->execute(['bsn' => $bsn]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("Error getting current address: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Genereer uniek dossier ID
     */
    public function generateDossierId(): string {
        return 'DOSSIER-' . date('Ymd') . '-' . uniqid();
    }
}
