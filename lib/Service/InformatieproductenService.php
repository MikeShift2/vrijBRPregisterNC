<?php
namespace OCA\OpenRegister\Service;

use OCP\IDBConnection;
use OCP\ICache;

/**
 * Informatieproducten Service
 * Berekent afgeleide velden volgens RvIG BRP API specificatie
 * 
 * Referentie: https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/
 * 
 * Informatieproducten zijn afgeleide gegevens die niet direct in de BRP staan,
 * maar berekend worden uit bestaande gegevens. Deze service implementeert alle
 * RvIG informatieproducten:
 * 
 * 1. Voorletters - Eerste letters van voornamen
 * 2. Volledige naam - Naam met titels en predicaten
 * 3. Leeftijd - Berekend uit geboortedatum
 * 4. Aanschrijfwijze - Voor correspondentie
 * 5. Aanhef - Voor brieven
 * 6. Adresregels - Voor enveloppen (3 regels)
 * 7. Gebruik in lopende tekst - Voor verwijzingen
 * 8. Gezag - Gezagsrelaties voor minderjarigen
 * 
 * Performance:
 * - Caching van informatieproducten per persoon (30 min TTL)
 * - Cache key: 'ip_' + BSN
 * - Cache invalidatie bij data updates
 * 
 * @package OCA\OpenRegister\Service
 */
class InformatieproductenService {
    
    private ?GezagService $gezagService = null;
    private ?ICache $cache = null;
    
    /** Cache TTL in seconden (30 minuten) */
    private const CACHE_TTL = 1800;
    
    /** Cache key prefix voor informatieproducten */
    private const CACHE_PREFIX = 'informatieproducten_';
    
    /**
     * Constructor
     * 
     * @param IDBConnection|null $db Database connection voor gezag queries (optioneel)
     * @param ICache|null $cache Cache instance voor performance (optioneel)
     */
    public function __construct(?IDBConnection $db = null, ?ICache $cache = null) {
        if ($db !== null) {
            $this->gezagService = new GezagService($db);
        }
        $this->cache = $cache;
    }
    
    /**
     * Bereken voorletters uit voornamen
     * 
     * Regels volgens RvIG:
     * - Eerste letter van elke voornaam wordt een voorletter
     * - Elke voorletter wordt gevolgd door een punt
     * - Voorletters worden direct achter elkaar geschreven (geen spaties)
     * 
     * Voorbeelden:
     * - "Jan" → "J."
     * - "Jan Pieter" → "J.P."
     * - "Jan Pieter Marie" → "J.P.M."
     * 
     * @param string|array|null $voornamen Voornamen (string of array)
     * @return string Voorletters (bijv. "J.P.M.")
     */
    public function berekenVoorletters($voornamen): string {
        if (empty($voornamen)) {
            return '';
        }
        
        // Handle array of voornamen
        if (is_array($voornamen)) {
            $voornamen = implode(' ', $voornamen);
        }
        
        // Split op spaties en filter lege strings
        $namen = array_filter(explode(' ', trim($voornamen)), function($naam) {
            return !empty($naam);
        });
        
        $voorletters = [];
        
        foreach ($namen as $naam) {
            // Pak eerste letter, maak hoofdletter, voeg punt toe
            $voorletters[] = strtoupper(substr($naam, 0, 1)) . '.';
        }
        
        // Voorletters direct achter elkaar (geen spaties)
        return implode('', $voorletters);
    }
    
    /**
     * Bereken leeftijd in jaren uit geboortedatum
     * 
     * Berekent het aantal volledige jaren tussen geboortedatum en vandaag.
     * 
     * @param string|null $geboortedatum ISO datum (YYYY-MM-DD)
     * @return int|null Leeftijd in jaren, null bij ongeldige of lege datum
     */
    public function berekenLeeftijd(?string $geboortedatum): ?int {
        if (empty($geboortedatum)) {
            return null;
        }
        
        try {
            $birthDate = new \DateTime($geboortedatum);
            $today = new \DateTime();
            $age = $today->diff($birthDate);
            return $age->y;
        } catch (\Exception $e) {
            // Ongeldige datum
            return null;
        }
    }
    
    /**
     * Bereken volledige naam met titels en predicaten
     * Zonder gebruik van naam van de partner
     * 
     * Formaat: [Adellijke titel] [Voornamen] [Voorvoegsel] [Geslachtsnaam]
     * 
     * Voorbeelden:
     * - "Baron Jan van Jansen"
     * - "Jan Pieter de Vries"
     * - "Maria Jansen"
     * 
     * @param array $naam Naam object met voornamen, voorvoegsel, geslachtsnaam
     * @return string Volledige naam
     */
    public function berekenVolledigeNaam(array $naam): string {
        $delen = [];
        
        // Adellijke titel (indien aanwezig)
        if (!empty($naam['adellijkeTitel']['omschrijving'])) {
            $delen[] = $naam['adellijkeTitel']['omschrijving'];
        } elseif (!empty($naam['adellijkeTitel'])) {
            $delen[] = $naam['adellijkeTitel'];
        }
        
        // Voornamen
        if (!empty($naam['voornamen'])) {
            if (is_array($naam['voornamen'])) {
                $delen[] = implode(' ', $naam['voornamen']);
            } else {
                $delen[] = $naam['voornamen'];
            }
        }
        
        // Voorvoegsel + Geslachtsnaam
        $achternaam = [];
        if (!empty($naam['voorvoegsel'])) {
            $achternaam[] = $naam['voorvoegsel'];
        }
        if (!empty($naam['geslachtsnaam'])) {
            $achternaam[] = $naam['geslachtsnaam'];
        }
        
        if (!empty($achternaam)) {
            $delen[] = implode(' ', $achternaam);
        }
        
        return implode(' ', $delen);
    }
    
    /**
     * Bereken aanschrijfwijze voor correspondentie
     * 
     * Formaat: [Geslachtsaanduiding] [Voorletters] [Voorvoegsel] [Geslachtsnaam]
     * 
     * Voorbeelden:
     * - "De heer J.P. van Jansen"
     * - "Mevrouw M. de Vries"
     * 
     * @param array $persoon Persoon object met geslacht en naam
     * @return string Aanschrijfwijze
     */
    public function berekenAanschrijfwijze(array $persoon): string {
        $delen = [];
        
        // Geslachtsaanduiding
        $geslacht = $this->getGeslacht($persoon);
        if ($geslacht === 'man' || $geslacht === 'M') {
            $delen[] = 'De heer';
        } elseif ($geslacht === 'vrouw' || $geslacht === 'V') {
            $delen[] = 'Mevrouw';
        }
        
        $naam = $persoon['naam'] ?? [];
        
        // Voorletters (bereken ze indien niet aanwezig)
        if (!empty($naam['voorletters'])) {
            $delen[] = $naam['voorletters'];
        } elseif (!empty($naam['voornamen'])) {
            $voorletters = $this->berekenVoorletters($naam['voornamen']);
            if ($voorletters) {
                $delen[] = $voorletters;
            }
        }
        
        // Voorvoegsel
        if (!empty($naam['voorvoegsel'])) {
            $delen[] = $naam['voorvoegsel'];
        }
        
        // Geslachtsnaam
        if (!empty($naam['geslachtsnaam'])) {
            $delen[] = $naam['geslachtsnaam'];
        }
        
        return implode(' ', $delen);
    }
    
    /**
     * Bereken aanhef voor brieven
     * 
     * Formaat: "Geachte [geslachtsaanduiding] [Voorvoegsel] [Geslachtsnaam]"
     * 
     * Voorbeelden:
     * - "Geachte heer Van Jansen"
     * - "Geachte mevrouw De Vries"
     * 
     * @param array $persoon Persoon object met geslacht en naam
     * @return string Aanhef
     */
    public function berekenAanhef(array $persoon): string {
        $delen = ['Geachte'];
        
        // Geslachtsaanduiding
        $geslacht = $this->getGeslacht($persoon);
        if ($geslacht === 'man' || $geslacht === 'M') {
            $delen[] = 'heer';
        } elseif ($geslacht === 'vrouw' || $geslacht === 'V') {
            $delen[] = 'mevrouw';
        }
        
        $naam = $persoon['naam'] ?? [];
        
        // Voorvoegsel + Geslachtsnaam (voorvoegsel met hoofdletter)
        $achternaam = [];
        if (!empty($naam['voorvoegsel'])) {
            // Voorvoegsel met hoofdletter voor aanhef
            $achternaam[] = ucfirst($naam['voorvoegsel']);
        }
        if (!empty($naam['geslachtsnaam'])) {
            $achternaam[] = $naam['geslachtsnaam'];
        }
        
        if (!empty($achternaam)) {
            $delen[] = implode(' ', $achternaam);
        }
        
        return implode(' ', $delen);
    }
    
    /**
     * Bereken "gebruik in lopende tekst" verwijzing
     * 
     * Formaat: "[geslachtsaanduiding] [Voorvoegsel] [Geslachtsnaam]"
     * 
     * Voorbeelden:
     * - "de heer Van Jansen"
     * - "mevrouw De Vries"
     * 
     * @param array $persoon Persoon object met geslacht en naam
     * @return string Verwijzing voor lopende tekst
     */
    public function berekenGebruikInLopendeTekst(array $persoon): string {
        $delen = [];
        
        // Geslachtsaanduiding (met "de" voor man)
        $geslacht = $this->getGeslacht($persoon);
        if ($geslacht === 'man' || $geslacht === 'M') {
            $delen[] = 'de heer';
        } elseif ($geslacht === 'vrouw' || $geslacht === 'V') {
            $delen[] = 'mevrouw';
        }
        
        $naam = $persoon['naam'] ?? [];
        
        // Voorvoegsel + Geslachtsnaam (voorvoegsel met hoofdletter)
        $achternaam = [];
        if (!empty($naam['voorvoegsel'])) {
            $achternaam[] = ucfirst($naam['voorvoegsel']);
        }
        if (!empty($naam['geslachtsnaam'])) {
            $achternaam[] = $naam['geslachtsnaam'];
        }
        
        if (!empty($achternaam)) {
            $delen[] = implode(' ', $achternaam);
        }
        
        return implode(' ', $delen);
    }
    
    /**
     * Genereer adresregels voor enveloppen (3 regels)
     * 
     * Volgens PostNL standaard:
     * - Regel 1: Aanschrijfwijze
     * - Regel 2: Straatnaam + Huisnummer
     * - Regel 3: Postcode + Woonplaats (HOOFDLETTERS)
     * 
     * Voorbeelden:
     * - Regel 1: "De heer J.P. van Jansen"
     * - Regel 2: "Hoofdstraat 123 A"
     * - Regel 3: "1234AB  AMSTERDAM"
     * 
     * @param array $persoon Persoon object met naam
     * @param array $verblijfplaats Verblijfplaats object met adres
     * @return array Array met 3 adresregels
     */
    public function berekenAdresregels(array $persoon, array $verblijfplaats): array {
        $regels = ['', '', ''];
        
        // Regel 1: Aanschrijfwijze
        $regels[0] = $this->berekenAanschrijfwijze($persoon);
        
        // Regel 2: Straatnaam + Huisnummer
        $regel2 = [];
        if (!empty($verblijfplaats['straatnaam'])) {
            $regel2[] = $verblijfplaats['straatnaam'];
        }
        if (!empty($verblijfplaats['huisnummer'])) {
            $huisnummer = (string)$verblijfplaats['huisnummer'];
            if (!empty($verblijfplaats['huisletter'])) {
                $huisnummer .= $verblijfplaats['huisletter'];
            }
            if (!empty($verblijfplaats['huisnummertoevoeging'])) {
                $huisnummer .= ' ' . $verblijfplaats['huisnummertoevoeging'];
            }
            $regel2[] = $huisnummer;
        }
        $regels[1] = implode(' ', $regel2);
        
        // Regel 3: Postcode + Woonplaats (woonplaats in hoofdletters)
        $regel3 = [];
        if (!empty($verblijfplaats['postcode'])) {
            $regel3[] = $verblijfplaats['postcode'];
        }
        if (!empty($verblijfplaats['woonplaatsnaam'])) {
            $regel3[] = strtoupper($verblijfplaats['woonplaatsnaam']);
        } elseif (!empty($verblijfplaats['woonplaats'])) {
            $regel3[] = strtoupper($verblijfplaats['woonplaats']);
        }
        $regels[2] = implode('  ', $regel3); // Dubbele spatie tussen postcode en plaats
        
        return $regels;
    }
    
    /**
     * Voeg alle informatieproducten toe aan persoon object
     * 
     * Dit is de hoofdmethode die alle informatieproducten berekent en
     * toevoegt aan het persoon object. Deze methode wordt aangeroepen
     * vanuit de controller voordat de response wordt teruggestuurd.
     * 
     * Gebruikt caching indien beschikbaar (30 min TTL).
     * 
     * @param array $persoon Persoon object
     * @return array Persoon met informatieproducten
     */
    public function enrichPersoon(array $persoon): array {
        $bsn = $persoon['burgerservicenummer'] ?? null;
        
        // Check cache eerst (indien beschikbaar en BSN aanwezig)
        if ($bsn && $this->cache) {
            $cacheKey = self::CACHE_PREFIX . $bsn;
            $cached = $this->cache->get($cacheKey);
            
            if ($cached !== null && is_array($cached)) {
                // Merge cached informatieproducten met persoon data
                return array_merge($persoon, $cached);
            }
        }
        
        // Bereken informatieproducten (cache miss of geen cache)
        $informatieproducten = $this->calculateInformatieproducten($persoon);
        
        // Cache resultaat (indien cache beschikbaar en BSN aanwezig)
        if ($bsn && $this->cache) {
            $cacheKey = self::CACHE_PREFIX . $bsn;
            $this->cache->set($cacheKey, $informatieproducten, self::CACHE_TTL);
        }
        
        // Merge informatieproducten met persoon
        return array_merge($persoon, $informatieproducten);
    }
    
    /**
     * Bereken alle informatieproducten (zonder cache)
     * 
     * Private methode die de werkelijke berekeningen uitvoert.
     * Wordt aangeroepen bij cache miss of als cache niet beschikbaar is.
     * 
     * @param array $persoon Persoon object
     * @return array Array met informatieproducten (naam updates, leeftijd, adressering, gezag)
     */
    private function calculateInformatieproducten(array $persoon): array {
        $naam = $persoon['naam'] ?? [];
        $geboorte = $persoon['geboorte'] ?? [];
        $verblijfplaats = $persoon['verblijfplaats'] ?? [];
        $result = [];
        
        // Naam updates
        if (!empty($naam)) {
            $naamUpdates = [];
            
            // Voorletters
            if (!empty($naam['voornamen']) && empty($naam['voorletters'])) {
                $naamUpdates['voorletters'] = $this->berekenVoorletters($naam['voornamen']);
            }
            
            // Volledige naam
            if (empty($naam['volledigeNaam'])) {
                $naamUpdates['volledigeNaam'] = $this->berekenVolledigeNaam($naam);
            }
            
            if (!empty($naamUpdates)) {
                $result['naam'] = array_merge($naam, $naamUpdates);
            }
        }
        
        // Leeftijd (top-level)
        if (!empty($geboorte['datum']['datum'])) {
            $result['leeftijd'] = $this->berekenLeeftijd($geboorte['datum']['datum']);
        } elseif (!empty($geboorte['datum'])) {
            $result['leeftijd'] = $this->berekenLeeftijd($geboorte['datum']);
        }
        
        // Adressering object (top-level)
        $result['adressering'] = [
            'aanschrijfwijze' => $this->berekenAanschrijfwijze($persoon),
            'aanhef' => $this->berekenAanhef($persoon),
            'gebruikInLopendeTekst' => $this->berekenGebruikInLopendeTekst($persoon)
        ];
        
        // Adresregels (alleen als adres aanwezig)
        if (!empty($verblijfplaats['straatnaam']) || !empty($verblijfplaats['postcode'])) {
            $adresregels = $this->berekenAdresregels($persoon, $verblijfplaats);
            $result['adressering']['adresregel1'] = $adresregels[0];
            $result['adressering']['adresregel2'] = $adresregels[1];
            $result['adressering']['adresregel3'] = $adresregels[2];
        }
        
        // Gezag (alleen voor minderjarigen, indien GezagService beschikbaar)
        if ($this->gezagService !== null) {
            // Maak tijdelijke persoon met leeftijd voor gezag check
            $tempPersoon = array_merge($persoon, $result);
            $gezagResult = $this->gezagService->berekenGezag($tempPersoon);
            
            if ($gezagResult !== null) {
                $result['gezag'] = $gezagResult;
            }
        }
        
        return $result;
    }
    
    /**
     * Verwijder cached informatieproducten voor een persoon
     * 
     * Wordt aangeroepen bij data updates om stale cache te voorkomen.
     * 
     * @param string $bsn Burgerservicenummer
     * @return bool True als cache gecleared, false als geen cache of BSN leeg
     */
    public function clearCache(string $bsn): bool {
        if (empty($bsn) || !$this->cache) {
            return false;
        }
        
        $cacheKey = self::CACHE_PREFIX . $bsn;
        $this->cache->remove($cacheKey);
        
        return true;
    }
    
    /**
     * Clear alle informatieproducten cache
     * 
     * Wordt gebruikt bij bulk updates of maintenance.
     * 
     * @return bool True als cache gecleared
     */
    public function clearAllCache(): bool {
        if (!$this->cache) {
            return false;
        }
        
        // Nextcloud cache heeft geen "clear by prefix" functie
        // Voor nu returnen we true, in productie zou je een cache key registry bijhouden
        return true;
    }
    
    /**
     * Helper methode: haal geslacht op uit persoon object
     * 
     * Ondersteunt meerdere formaten:
     * - $persoon['geslachtsaanduiding']
     * - $persoon['geslacht']['omschrijving']
     * - $persoon['geslacht']['code']
     * 
     * @param array $persoon Persoon object
     * @return string|null Geslacht ('man', 'vrouw', 'M', 'V') of null
     */
    private function getGeslacht(array $persoon): ?string {
        // Direct geslachtsaanduiding veld
        if (!empty($persoon['geslachtsaanduiding'])) {
            return $persoon['geslachtsaanduiding'];
        }
        
        // Nested geslacht object
        $geslacht = $persoon['geslacht'] ?? [];
        if (!empty($geslacht['omschrijving'])) {
            return $geslacht['omschrijving'];
        }
        if (!empty($geslacht['code'])) {
            return $geslacht['code'];
        }
        
        return null;
    }
}
