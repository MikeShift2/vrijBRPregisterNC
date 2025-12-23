<?php
/**
 * Expand Service
 * 
 * Implementeert expand functionaliteit (expand parameter) voor Haal Centraal API
 * Haalt automatisch relaties op (partners, kinderen, ouders, etc.)
 */

namespace OCA\OpenRegister\Service\HaalCentraal;

use OCA\OpenRegister\Controller\HaalCentraalBrpController;

class ExpandService {
    private HaalCentraalBrpController $controller;
    
    public function __construct(HaalCentraalBrpController $controller) {
        $this->controller = $controller;
    }
    
    /**
     * Pas expand functionaliteit toe op data volgens expand parameter
     * 
     * @param array $data Volledige data object
     * @param string|null $expandParam Comma-separated lijst van relaties (bijv. "partners,kinderen")
     * @param string|null $bsn BSN van de persoon (nodig om relaties op te halen)
     * @return array Data met uitgebreide relaties in _embedded
     */
    public function applyExpand(array $data, ?string $expandParam, ?string $bsn = null): array {
        if (empty($expandParam) || empty($bsn)) {
            return $data;
        }
        
        // Haal BSN op als niet gegeven
        if ($bsn === null) {
            $bsn = $data['burgerservicenummer'] ?? null;
        }
        
        if (empty($bsn)) {
            return $data;
        }
        
        $expands = array_map('trim', explode(',', $expandParam));
        
        // Ondersteuning voor wildcard (*) om alle relaties op te halen
        if (in_array('*', $expands)) {
            $expands = ['partners', 'kinderen', 'ouders', 'verblijfplaats', 'nationaliteiten'];
        }
        
        // Initialiseer _embedded als die nog niet bestaat
        if (!isset($data['_embedded'])) {
            $data['_embedded'] = [];
        }
        
        // Haal relaties op via controller methodes
        if (in_array('partners', $expands)) {
            try {
                $partners = $this->getPartners($bsn);
                if (!empty($partners)) {
                    $data['_embedded']['partners'] = $partners;
                }
            } catch (\Exception $e) {
                error_log("Error expanding partners for BSN $bsn: " . $e->getMessage());
            }
        }
        
        if (in_array('kinderen', $expands)) {
            try {
                $kinderen = $this->getKinderen($bsn);
                if (!empty($kinderen)) {
                    $data['_embedded']['kinderen'] = $kinderen;
                }
            } catch (\Exception $e) {
                error_log("Error expanding kinderen for BSN $bsn: " . $e->getMessage());
            }
        }
        
        if (in_array('ouders', $expands)) {
            try {
                $ouders = $this->getOuders($bsn);
                if (!empty($ouders)) {
                    $data['_embedded']['ouders'] = $ouders;
                }
            } catch (\Exception $e) {
                error_log("Error expanding ouders for BSN $bsn: " . $e->getMessage());
            }
        }
        
        if (in_array('verblijfplaats', $expands)) {
            try {
                $verblijfplaats = $this->getVerblijfplaats($bsn);
                if (!empty($verblijfplaats)) {
                    $data['_embedded']['verblijfplaats'] = $verblijfplaats;
                }
            } catch (\Exception $e) {
                error_log("Error expanding verblijfplaats for BSN $bsn: " . $e->getMessage());
            }
        }
        
        if (in_array('nationaliteiten', $expands)) {
            try {
                $nationaliteiten = $this->getNationaliteiten($bsn);
                if (!empty($nationaliteiten)) {
                    $data['_embedded']['nationaliteiten'] = $nationaliteiten;
                }
            } catch (\Exception $e) {
                error_log("Error expanding nationaliteiten for BSN $bsn: " . $e->getMessage());
            }
        }
        
        return $data;
    }
    
    /**
     * Haal partners op voor BSN
     */
    private function getPartners(string $bsn): array {
        try {
            $response = $this->controller->getPartners($bsn);
            $data = $response->getData();
            return $data['_embedded']['partners'] ?? [];
        } catch (\Exception $e) {
            error_log("Error getting partners for BSN $bsn: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal kinderen op voor BSN
     */
    private function getKinderen(string $bsn): array {
        try {
            $response = $this->controller->getKinderen($bsn);
            $data = $response->getData();
            return $data['_embedded']['kinderen'] ?? [];
        } catch (\Exception $e) {
            error_log("Error getting kinderen for BSN $bsn: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal ouders op voor BSN
     */
    private function getOuders(string $bsn): array {
        try {
            $response = $this->controller->getOuders($bsn);
            $data = $response->getData();
            return $data['_embedded']['ouders'] ?? [];
        } catch (\Exception $e) {
            error_log("Error getting ouders for BSN $bsn: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Haal verblijfplaats op voor BSN
     */
    private function getVerblijfplaats(string $bsn): ?array {
        try {
            $response = $this->controller->getVerblijfplaats($bsn);
            return $response->getData();
        } catch (\Exception $e) {
            // 404 is OK, betekent gewoon geen verblijfplaats
            return null;
        }
    }
    
    /**
     * Haal nationaliteiten op voor BSN
     */
    private function getNationaliteiten(string $bsn): array {
        try {
            $response = $this->controller->getNationaliteiten($bsn);
            $data = $response->getData();
            return $data['_embedded']['nationaliteiten'] ?? [];
        } catch (\Exception $e) {
            error_log("Error getting nationaliteiten for BSN $bsn: " . $e->getMessage());
            return [];
        }
    }
}

