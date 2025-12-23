<?php
/**
 * Haal Centraal BRP Historie API Validator
 * 
 * Valideert responses tegen de Haal Centraal BRP Historie API 2.0 specificatie
 * 
 * @see https://brp-api.github.io/Haal-Centraal-BRP-historie-bevragen/redoc
 */

namespace OCA\OpenRegister\Service;

class HaalCentraalHistorieValidator {
    
    /**
     * Valideer verblijfplaatshistorie response
     * 
     * @param array $response De response array om te valideren
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public function validateVerblijfplaatshistorieResponse(array $response): array {
        $errors = [];
        
        // Valideer top-level structuur
        if (!isset($response['_embedded'])) {
            $errors[] = 'Response moet _embedded bevatten';
        } else {
            if (!isset($response['_embedded']['verblijfplaatshistorie'])) {
                $errors[] = '_embedded moet verblijfplaatshistorie bevatten';
            } else {
                if (!is_array($response['_embedded']['verblijfplaatshistorie'])) {
                    $errors[] = 'verblijfplaatshistorie moet een array zijn';
                } else {
                    // Valideer elk verblijfplaats item
                    foreach ($response['_embedded']['verblijfplaatshistorie'] as $index => $verblijfplaats) {
                        $itemErrors = $this->validateVerblijfplaatsItem($verblijfplaats, $index);
                        $errors = array_merge($errors, $itemErrors);
                    }
                }
            }
        }
        
        // Valideer _links structuur (optioneel maar aanbevolen)
        if (isset($response['_links'])) {
            $linkErrors = $this->validateLinks($response['_links']);
            $errors = array_merge($errors, $linkErrors);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Valideer een individueel verblijfplaats item
     * 
     * @param array $verblijfplaats Het verblijfplaats object
     * @param int $index Index voor error reporting
     * @return string[] Array van error messages
     */
    private function validateVerblijfplaatsItem(array $verblijfplaats, int $index): array {
        $errors = [];
        $prefix = "Verblijfplaats [$index]";
        
        // Valideer dat er minimaal één veld is
        $requiredFields = ['straatnaam', 'huisnummer', 'postcode', 'woonplaatsnaam'];
        $hasAtLeastOneField = false;
        foreach ($requiredFields as $field) {
            if (isset($verblijfplaats[$field]) && $verblijfplaats[$field] !== null && $verblijfplaats[$field] !== '') {
                $hasAtLeastOneField = true;
                break;
            }
        }
        
        if (!$hasAtLeastOneField) {
            $errors[] = "$prefix: Moet minimaal één adresveld bevatten (straatnaam, huisnummer, postcode, of woonplaatsnaam)";
        }
        
        // Valideer straatnaam (optioneel, maar als aanwezig moet het een string zijn)
        if (isset($verblijfplaats['straatnaam'])) {
            if (!is_string($verblijfplaats['straatnaam'])) {
                $errors[] = "$prefix: straatnaam moet een string zijn";
            } elseif (empty(trim($verblijfplaats['straatnaam']))) {
                $errors[] = "$prefix: straatnaam mag niet leeg zijn";
            }
        }
        
        // Valideer huisnummer (optioneel, maar als aanwezig moet het een integer of string zijn)
        if (isset($verblijfplaats['huisnummer'])) {
            if (!is_int($verblijfplaats['huisnummer']) && !is_string($verblijfplaats['huisnummer'])) {
                $errors[] = "$prefix: huisnummer moet een integer of string zijn";
            } elseif (is_string($verblijfplaats['huisnummer']) && empty(trim($verblijfplaats['huisnummer']))) {
                $errors[] = "$prefix: huisnummer mag niet leeg zijn";
            }
        }
        
        // Valideer huisnummertoevoeging (optioneel, maar als aanwezig moet het een string zijn)
        if (isset($verblijfplaats['huisnummertoevoeging'])) {
            if (!is_string($verblijfplaats['huisnummertoevoeging'])) {
                $errors[] = "$prefix: huisnummertoevoeging moet een string zijn";
            }
        }
        
        // Valideer postcode (optioneel, maar als aanwezig moet het een string zijn)
        if (isset($verblijfplaats['postcode'])) {
            if (!is_string($verblijfplaats['postcode'])) {
                $errors[] = "$prefix: postcode moet een string zijn";
            } elseif (empty(trim($verblijfplaats['postcode']))) {
                $errors[] = "$prefix: postcode mag niet leeg zijn";
            } else {
                // Valideer postcode formaat (NL formaat: 1234AB of 1234 AB)
                $postcode = trim($verblijfplaats['postcode']);
                if (!preg_match('/^\d{4}\s?[A-Z]{2}$/i', $postcode)) {
                    $errors[] = "$prefix: postcode heeft ongeldig formaat (verwacht: 1234AB)";
                }
            }
        }
        
        // Valideer woonplaatsnaam (optioneel, maar als aanwezig moet het een string zijn)
        if (isset($verblijfplaats['woonplaatsnaam'])) {
            if (!is_string($verblijfplaats['woonplaatsnaam'])) {
                $errors[] = "$prefix: woonplaatsnaam moet een string zijn";
            } elseif (empty(trim($verblijfplaats['woonplaatsnaam']))) {
                $errors[] = "$prefix: woonplaatsnaam mag niet leeg zijn";
            }
        }
        
        // Valideer datumAanvangAdres (optioneel)
        if (isset($verblijfplaats['datumAanvangAdres'])) {
            $datumErrors = $this->validateDatumObject($verblijfplaats['datumAanvangAdres'], "$prefix.datumAanvangAdres");
            $errors = array_merge($errors, $datumErrors);
        }
        
        // Valideer datumIngangGeldigheid (optioneel)
        if (isset($verblijfplaats['datumIngangGeldigheid'])) {
            $datumErrors = $this->validateDatumObject($verblijfplaats['datumIngangGeldigheid'], "$prefix.datumIngangGeldigheid");
            $errors = array_merge($errors, $datumErrors);
        }
        
        // Valideer datumEindeGeldigheid (optioneel)
        if (isset($verblijfplaats['datumEindeGeldigheid'])) {
            $datumErrors = $this->validateDatumObject($verblijfplaats['datumEindeGeldigheid'], "$prefix.datumEindeGeldigheid");
            $errors = array_merge($errors, $datumErrors);
        }
        
        return $errors;
    }
    
    /**
     * Valideer een datum object (bijv. {"datum": "2020-01-01"})
     * 
     * @param mixed $datumObject Het datum object
     * @param string $fieldPath Pad voor error reporting
     * @return string[] Array van error messages
     */
    private function validateDatumObject($datumObject, string $fieldPath): array {
        $errors = [];
        
        if (!is_array($datumObject)) {
            $errors[] = "$fieldPath: Moet een object zijn met 'datum' veld";
            return $errors;
        }
        
        if (!isset($datumObject['datum'])) {
            $errors[] = "$fieldPath: Moet 'datum' veld bevatten";
            return $errors;
        }
        
        $datum = $datumObject['datum'];
        
        if (!is_string($datum)) {
            $errors[] = "$fieldPath.datum: Moet een string zijn";
            return $errors;
        }
        
        // Valideer ISO 8601 datum formaat (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) {
            $errors[] = "$fieldPath.datum: Moet ISO 8601 formaat zijn (YYYY-MM-DD), kreeg: $datum";
            return $errors;
        }
        
        // Valideer dat het een geldige datum is
        $parts = explode('-', $datum);
        if (count($parts) !== 3) {
            $errors[] = "$fieldPath.datum: Ongeldig datum formaat";
            return $errors;
        }
        
        $jaar = (int)$parts[0];
        $maand = (int)$parts[1];
        $dag = (int)$parts[2];
        
        if (!checkdate($maand, $dag, $jaar)) {
            $errors[] = "$fieldPath.datum: Ongeldige datum: $datum";
        }
        
        // Valideer dat datum niet in de toekomst ligt (voor historie kan dit wel, maar we checken op redelijkheid)
        $datumTimestamp = strtotime($datum);
        $maxDatum = strtotime('+10 years'); // Maximaal 10 jaar in de toekomst (voor edge cases)
        if ($datumTimestamp > $maxDatum) {
            $errors[] = "$fieldPath.datum: Datum ligt te ver in de toekomst: $datum";
        }
        
        return $errors;
    }
    
    /**
     * Valideer _links structuur
     * 
     * @param array $links De _links array
     * @return string[] Array van error messages
     */
    private function validateLinks(array $links): array {
        $errors = [];
        
        // Valideer dat links een object is
        if (!is_array($links)) {
            $errors[] = '_links moet een object zijn';
            return $errors;
        }
        
        // Valideer self link (aanbevolen)
        if (isset($links['self'])) {
            if (!is_array($links['self']) || !isset($links['self']['href'])) {
                $errors[] = '_links.self moet een object zijn met href veld';
            } elseif (!is_string($links['self']['href']) || empty($links['self']['href'])) {
                $errors[] = '_links.self.href moet een niet-lege string zijn';
            }
        }
        
        // Valideer ingeschrevenpersoon link (aanbevolen)
        if (isset($links['ingeschrevenpersoon'])) {
            if (!is_array($links['ingeschrevenpersoon']) || !isset($links['ingeschrevenpersoon']['href'])) {
                $errors[] = '_links.ingeschrevenpersoon moet een object zijn met href veld';
            } elseif (!is_string($links['ingeschrevenpersoon']['href']) || empty($links['ingeschrevenpersoon']['href'])) {
                $errors[] = '_links.ingeschrevenpersoon.href moet een niet-lege string zijn';
            }
        }
        
        return $errors;
    }
}







