# 🎯 Plan: Open Register → 100% Haal Centraal RvIG BRP API Compliant

**Datum:** 2025-01-23  
**Doel:** Volledige compliance met [RvIG BRP API specificatie](https://developer.rvig.nl/brp-api/overview/)  
**Huidige status:** ⚠️ 60% compliant  
**Doel status:** ✅ 100% compliant  
**Geschatte doorlooptijd:** 3-4 weken

---

## 📊 Executive Summary

### Huidige Status (✅ Goed)
- ✅ Data structuur nested objects conform Haal Centraal
- ✅ Basis Personen API endpoints (7 stuks)
- ✅ Veldnamen correct (`burgerservicenummer`, etc.)
- ✅ HAL JSON response format
- ✅ Database bevat alle brondata
- ✅ 20.631 objecten gemigreerd naar nested structuur

### Kritieke Gaps (❌ Ontbreekt)
- ❌ Informatieproducten (voorletters, aanhef, adressering, etc.)
- ❌ Bewoning API
- ❌ RNI (Registratie Niet-Ingezeten) ontsluiting
- ⚠️ Query parameters deels oud formaat
- ⚠️ HTTP headers niet volledig RvIG

### Roadmap Overview

```
Week 1-2: Informatieproducten (Kritiek)
Week 3:   Bewoning API & RNI
Week 4:   Parameters, Headers, Testing & Documentatie
```

**Resultaat:** Van 60% → 100% RvIG BRP API compliant

---

## 🎯 Doelstellingen

### 1. Primaire Doelen (Must Have)

1. ✅ **Informatieproducten implementeren**
   - Voorletters berekening
   - Leeftijd berekening
   - Volledige naam samenstelling
   - Aanschrijfwijze generatie
   - Aanhef generatie
   - Adresregels voor enveloppen

2. ✅ **Bewoning API implementeren**
   - Endpoint: `GET /adressen/{id}/bewoning`
   - Peildatum queries
   - Periode queries (datumVan/datumTot)

3. ✅ **RNI Ontsluiting**
   - RNI data uit `rni_ax` tabel
   - Filter parameter `?inclusiefRni=true`

### 2. Secundaire Doelen (Should Have)

4. ✅ **Query Parameters Moderniseren**
   - `burgerservicenummer` i.p.v. `bsn`
   - Backward compatibility behouden

5. ✅ **HTTP Headers Compliant Maken**
   - `Accept: application/hal+json`
   - `X-Correlation-ID` support

6. ✅ **Error Responses RFC 7807**
   - Problem Details format
   - Correcte status codes

### 3. Nice to Have

7. ⚠️ **Gezag Informatieproduct**
   - Gezagsrelaties als informatieproduct
   - Minderjarigen detectie

8. ⚠️ **Validatie volgens RvIG spec**
   - Request validatie
   - Response validatie

---

## 📋 Gedetailleerd Implementatieplan

---

## WEEK 1: Informatieproducten Kern (Prioriteit 1)

**Doel:** Implementeer de 4 basis informatieproducten  
**Impact:** +25% compliance (60% → 85%)

### Dag 1-2: Service Layer Opzetten

#### 1.1 InformatieproductenService Aanmaken

**Bestand:** `lib/Service/InformatieproductenService.php`

```php
<?php
namespace OCA\OpenRegister\Service;

/**
 * Informatieproducten Service
 * Berekent afgeleide velden volgens RvIG BRP API specificatie
 * 
 * Referentie: https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/
 */
class InformatieproductenService {
    
    /**
     * Bereken voorletters uit voornamen
     * 
     * Regels:
     * - Eerste letter van elke voornaam
     * - Gevolgd door een punt
     * - Gescheiden door spaties
     * 
     * Voorbeeld: "Jan Pieter Marie" → "J.P.M."
     * 
     * @param string|array $voornamen Voornamen (string of array)
     * @return string Voorletters
     */
    public function berekenVoorletters($voornamen): string {
        if (empty($voornamen)) {
            return '';
        }
        
        // Handle array of voornamen
        if (is_array($voornamen)) {
            $voornamen = implode(' ', $voornamen);
        }
        
        // Split op spaties
        $namen = explode(' ', trim($voornamen));
        $voorletters = [];
        
        foreach ($namen as $naam) {
            if (!empty($naam)) {
                $voorletters[] = strtoupper(substr($naam, 0, 1)) . '.';
            }
        }
        
        return implode('', $voorletters);
    }
    
    /**
     * Bereken leeftijd in jaren
     * 
     * @param string $geboortedatum ISO datum (YYYY-MM-DD)
     * @return int|null Leeftijd in jaren, null bij ongeldige datum
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
            return null;
        }
    }
    
    /**
     * Bereken volledige naam met titels en predicaten
     * Zonder gebruik van naam van de partner
     * 
     * Formaat: [Adellijke titel] [Voornamen] [Voorvoegsel] [Geslachtsnaam]
     * 
     * @param array $naam Naam object met voornamen, voorvoegsel, geslachtsnaam
     * @return string Volledige naam
     */
    public function berekenVolledigeNaam(array $naam): string {
        $delen = [];
        
        // Adellijke titel (indien aanwezig)
        if (!empty($naam['adellijkeTitel'])) {
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
     * Voorbeeld: "Mevrouw J.P. van Jansen"
     * 
     * @param array $persoon Persoon object
     * @return string Aanschrijfwijze
     */
    public function berekenAanschrijfwijze(array $persoon): string {
        $delen = [];
        
        // Geslachtsaanduiding
        $geslacht = $persoon['geslachtsaanduiding'] ?? $persoon['geslacht']['omschrijving'] ?? null;
        if ($geslacht === 'man') {
            $delen[] = 'De heer';
        } elseif ($geslacht === 'vrouw') {
            $delen[] = 'Mevrouw';
        }
        
        $naam = $persoon['naam'] ?? [];
        
        // Voorletters
        if (!empty($naam['voornamen'])) {
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
     * Voorbeeld: "Geachte heer Van Jansen"
     * 
     * @param array $persoon Persoon object
     * @return string Aanhef
     */
    public function berekenAanhef(array $persoon): string {
        $delen = ['Geachte'];
        
        // Geslachtsaanduiding
        $geslacht = $persoon['geslachtsaanduiding'] ?? $persoon['geslacht']['omschrijving'] ?? null;
        if ($geslacht === 'man') {
            $delen[] = 'heer';
        } elseif ($geslacht === 'vrouw') {
            $delen[] = 'mevrouw';
        }
        
        $naam = $persoon['naam'] ?? [];
        
        // Voorvoegsel + Geslachtsnaam
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
     * Bereken "gebruik in lopende tekst" verwijzing
     * 
     * Formaat: "[geslachtsaanduiding] [Voorvoegsel] [Geslachtsnaam]"
     * Voorbeeld: "de heer Van Jansen"
     * 
     * @param array $persoon Persoon object
     * @return string Verwijzing
     */
    public function berekenGebruikInLopendeTekst(array $persoon): string {
        $delen = [];
        
        // Geslachtsaanduiding
        $geslacht = $persoon['geslachtsaanduiding'] ?? $persoon['geslacht']['omschrijving'] ?? null;
        if ($geslacht === 'man') {
            $delen[] = 'de heer';
        } elseif ($geslacht === 'vrouw') {
            $delen[] = 'mevrouw';
        }
        
        $naam = $persoon['naam'] ?? [];
        
        // Voorvoegsel + Geslachtsnaam
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
     * Regel 1: Aanschrijfwijze
     * Regel 2: Straatnaam + Huisnummer
     * Regel 3: Postcode + Woonplaats
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
            $huisnummer = $verblijfplaats['huisnummer'];
            if (!empty($verblijfplaats['huisletter'])) {
                $huisnummer .= $verblijfplaats['huisletter'];
            }
            if (!empty($verblijfplaats['huisnummertoevoeging'])) {
                $huisnummer .= ' ' . $verblijfplaats['huisnummertoevoeging'];
            }
            $regel2[] = $huisnummer;
        }
        $regels[1] = implode(' ', $regel2);
        
        // Regel 3: Postcode + Woonplaats
        $regel3 = [];
        if (!empty($verblijfplaats['postcode'])) {
            $regel3[] = $verblijfplaats['postcode'];
        }
        if (!empty($verblijfplaats['woonplaatsnaam'])) {
            $regel3[] = strtoupper($verblijfplaats['woonplaatsnaam']);
        }
        $regels[2] = implode('  ', $regel3);
        
        return $regels;
    }
    
    /**
     * Voeg alle informatieproducten toe aan persoon object
     * 
     * @param array $persoon Persoon object
     * @return array Persoon met informatieproducten
     */
    public function enrichPersoon(array $persoon): array {
        $naam = $persoon['naam'] ?? [];
        $geboorte = $persoon['geboorte'] ?? [];
        $verblijfplaats = $persoon['verblijfplaats'] ?? [];
        
        // Voorletters toevoegen aan naam
        if (!empty($naam['voornamen'])) {
            $persoon['naam']['voorletters'] = $this->berekenVoorletters($naam['voornamen']);
        }
        
        // Volledige naam toevoegen
        if (!empty($naam)) {
            $persoon['naam']['volledigeNaam'] = $this->berekenVolledigeNaam($naam);
        }
        
        // Leeftijd toevoegen
        if (!empty($geboorte['datum']['datum'])) {
            $persoon['leeftijd'] = $this->berekenLeeftijd($geboorte['datum']['datum']);
        }
        
        // Adressering object toevoegen
        $persoon['adressering'] = [
            'aanschrijfwijze' => $this->berekenAanschrijfwijze($persoon),
            'aanhef' => $this->berekenAanhef($persoon),
            'gebruikInLopendeTekst' => $this->berekenGebruikInLopendeTekst($persoon)
        ];
        
        // Adresregels toevoegen (alleen als adres aanwezig)
        if (!empty($verblijfplaats['straatnaam'])) {
            $adresregels = $this->berekenAdresregels($persoon, $verblijfplaats);
            $persoon['adressering']['adresregel1'] = $adresregels[0];
            $persoon['adressering']['adresregel2'] = $adresregels[1];
            $persoon['adressering']['adresregel3'] = $adresregels[2];
        }
        
        return $persoon;
    }
}
```

**Acties:**
- [ ] Maak bestand aan
- [ ] Test voorletters berekening met unit tests
- [ ] Test leeftijd berekening met verschillende datums
- [ ] Test adresregels formatting

---

### Dag 3-4: Integratie in Controller

#### 1.2 HaalCentraalBrpController Updaten

**Bestand:** `lib/Controller/HaalCentraalBrpController.php`

**Wijzigingen:**

1. **Service injecteren in constructor:**

```php
private InformatieproductenService $informatieproductenService;

public function __construct(
    string $appName,
    IRequest $request,
    IDBConnection $db,
    // ... andere services ...
    InformatieproductenService $informatieproductenService
) {
    parent::__construct($appName, $request);
    $this->db = $db;
    // ... andere assignments ...
    $this->informatieproductenService = $informatieproductenService;
}
```

2. **Toevoegen aan transformToHaalCentraal methode:**

```php
private function transformToHaalCentraal(array $object, int $schemaId = null): array {
    // ... bestaande transformatie code ...
    
    // Voeg informatieproducten toe
    $result = $this->informatieproductenService->enrichPersoon($result);
    
    return $result;
}
```

**Acties:**
- [ ] Constructor aanpassen
- [ ] Dependency injection configureren
- [ ] Service registreren in `lib/AppInfo/Application.php`
- [ ] Test dat informatieproducten in response zitten

---

### Dag 5: Testing & Validatie

#### 1.3 Unit Tests Schrijven

**Bestand:** `tests/Unit/Service/InformatieproductenServiceTest.php`

```php
<?php
namespace OCA\OpenRegister\Tests\Unit\Service;

use OCA\OpenRegister\Service\InformatieproductenService;
use Test\TestCase;

class InformatieproductenServiceTest extends TestCase {
    
    private InformatieproductenService $service;
    
    protected function setUp(): void {
        parent::setUp();
        $this->service = new InformatieproductenService();
    }
    
    public function testBerekenVoorletters() {
        // Test single voornaam
        $this->assertEquals('J.', $this->service->berekenVoorletters('Jan'));
        
        // Test multiple voornamen
        $this->assertEquals('J.P.M.', $this->service->berekenVoorletters('Jan Pieter Marie'));
        
        // Test array voornamen
        $this->assertEquals('J.P.', $this->service->berekenVoorletters(['Jan', 'Pieter']));
        
        // Test empty
        $this->assertEquals('', $this->service->berekenVoorletters(''));
    }
    
    public function testBerekenLeeftijd() {
        // Test known age
        $geboortedat um = date('Y-m-d', strtotime('-25 years'));
        $this->assertEquals(25, $this->service->berekenLeeftijd($geboortedatum));
        
        // Test null
        $this->assertNull($this->service->berekenLeeftijd(null));
        
        // Test invalid date
        $this->assertNull($this->service->berekenLeeftijd('invalid'));
    }
    
    public function testBerekenAanschrijfwijze() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voornamen' => 'Jan Pieter',
                'voorvoegsel' => 'van',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $this->assertEquals('De heer J.P. van Jansen', $this->service->berekenAanschrijfwijze($persoon));
    }
    
    public function testBerekenAanhef() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voorvoegsel' => 'van der',
                'geslachtsnaam' => 'Berg'
            ]
        ];
        
        $this->assertEquals('Geachte mevrouw Van der Berg', $this->service->berekenAanhef($persoon));
    }
    
    public function testBerekenAdresregels() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voornamen' => 'Jan',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $verblijfplaats = [
            'straatnaam' => 'Hoofdstraat',
            'huisnummer' => '123',
            'huisnummertoevoeging' => 'A',
            'postcode' => '1234AB',
            'woonplaatsnaam' => 'Amsterdam'
        ];
        
        $regels = $this->service->berekenAdresregels($persoon, $verblijfplaats);
        
        $this->assertCount(3, $regels);
        $this->assertEquals('De heer J. Jansen', $regels[0]);
        $this->assertEquals('Hoofdstraat 123 A', $regels[1]);
        $this->assertEquals('1234AB  AMSTERDAM', $regels[2]);
    }
}
```

**Acties:**
- [ ] Unit tests schrijven
- [ ] Run tests: `docker exec nextcloud php vendor/bin/phpunit tests/Unit/Service/InformatieproductenServiceTest.php`
- [ ] Alle tests groen maken
- [ ] Coverage check (minimaal 90%)

---

### Dag 5: API Response Validatie

#### 1.4 Test API Responses

**Test script:** `test-informatieproducten.sh`

```bash
#!/bin/bash

echo "=== TEST INFORMATIEPRODUCTEN ==="
echo ""

# Test 1: Voorletters aanwezig?
echo "Test 1: Voorletters"
RESPONSE=$(curl -s -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=168149291&_limit=1")
VOORLETTERS=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].naam.voorletters')

if [ "$VOORLETTERS" != "null" ] && [ -n "$VOORLETTERS" ]; then
    echo "✅ Voorletters: $VOORLETTERS"
else
    echo "❌ Voorletters ontbreekt!"
fi

# Test 2: Leeftijd aanwezig?
echo "Test 2: Leeftijd"
LEEFTIJD=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].leeftijd')

if [ "$LEEFTIJD" != "null" ] && [ -n "$LEEFTIJD" ]; then
    echo "✅ Leeftijd: $LEEFTIJD jaar"
else
    echo "❌ Leeftijd ontbreekt!"
fi

# Test 3: Adressering object aanwezig?
echo "Test 3: Adressering"
AANSCHRIJFWIJZE=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].adressering.aanschrijfwijze')
AANHEF=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].adressering.aanhef')

if [ "$AANSCHRIJFWIJZE" != "null" ] && [ -n "$AANSCHRIJFWIJZE" ]; then
    echo "✅ Aanschrijfwijze: $AANSCHRIJFWIJZE"
else
    echo "❌ Aanschrijfwijze ontbreekt!"
fi

if [ "$AANHEF" != "null" ] && [ -n "$AANHEF" ]; then
    echo "✅ Aanhef: $AANHEF"
else
    echo "❌ Aanhef ontbreekt!"
fi

echo ""
echo "=== VOLLEDIGE RESPONSE ==="
echo $RESPONSE | jq '._embedded.ingeschrevenpersonen[0]' | head -50
```

**Acties:**
- [ ] Maak test script uitvoerbaar: `chmod +x test-informatieproducten.sh`
- [ ] Run test: `./test-informatieproducten.sh`
- [ ] Verifieer alle ✅ checks slagen

---

## WEEK 2: Informatieproducten Uitbreiden (Prioriteit 1)

**Doel:** Gezag informatieproduct & optimalisaties  
**Impact:** +10% compliance (85% → 95%)

### Dag 6-7: Gezag Informatieproduct

#### 2.1 Gezag Queries Integreren

**Uitbreiding:** `lib/Service/InformatieproductenService.php`

```php
/**
 * Bereken gezagsrelaties voor minderjarigen
 * 
 * @param array $persoon Persoon object
 * @param BrpDatabaseService $brpDb Database service voor gezag queries
 * @return array|null Gezag informatieproduct, null als niet van toepassing
 */
public function berekenGezag(array $persoon, BrpDatabaseService $brpDb): ?array {
    // Alleen voor minderjarigen
    $leeftijd = $persoon['leeftijd'] ?? null;
    if ($leeftijd === null || $leeftijd >= 18) {
        return null;
    }
    
    $bsn = $persoon['burgerservicenummer'] ?? null;
    if (!$bsn) {
        return null;
    }
    
    // Haal gezagsrelaties op uit database
    $gezagsrelaties = $brpDb->getGezagsrelaties($bsn);
    
    if (empty($gezagsrelaties)) {
        return null;
    }
    
    // Transformeer naar RvIG format
    $gezag = [];
    foreach ($gezagsrelaties as $relatie) {
        $gezag[] = [
            'type' => $relatie['soort_gezag'] ?? 'ouderlijkGezag',
            'minderjarige' => [
                'burgerservicenummer' => $bsn
            ],
            'ouder' => [
                'burgerservicenummer' => $relatie['bsn_ouder'] ?? null
            ]
        ];
    }
    
    return ['gezagsrelaties' => $gezag];
}
```

**Integreren in `enrichPersoon`:**

```php
public function enrichPersoon(array $persoon, ?BrpDatabaseService $brpDb = null): array {
    // ... bestaande code ...
    
    // Gezag (alleen voor minderjarigen)
    if ($brpDb !== null) {
        $gezag = $this->berekenGezag($persoon, $brpDb);
        if ($gezag !== null) {
            $persoon['gezag'] = $gezag;
        }
    }
    
    return $persoon;
}
```

**Acties:**
- [ ] Methode toevoegen
- [ ] Integreren in controller
- [ ] Test met minderjarigen uit database
- [ ] Verifieer gezagsrelaties correct worden getoond

---

### Dag 8-9: Performance Optimalisatie

#### 2.2 Caching van Informatieproducten

**Probleem:** Informatieproducten berekenen bij elke request is inefficiënt

**Oplossing:** Cache op persoon niveau

```php
public function enrichPersoon(array $persoon, ?BrpDatabaseService $brpDb = null): array {
    $bsn = $persoon['burgerservicenummer'] ?? null;
    
    // Check cache
    if ($bsn && $this->cacheService) {
        $cacheKey = 'informatieproducten_' . $bsn;
        $cached = $this->cacheService->get($cacheKey);
        
        if ($cached !== null) {
            return array_merge($persoon, $cached);
        }
    }
    
    // Bereken informatieproducten
    $informatieproducten = $this->calculateInformatieproducten($persoon, $brpDb);
    
    // Cache result (30 minuten)
    if ($bsn && $this->cacheService) {
        $this->cacheService->set($cacheKey, $informatieproducten, 1800);
    }
    
    return array_merge($persoon, $informatieproducten);
}
```

**Acties:**
- [ ] Implementeer caching strategie
- [ ] Test cache hits/misses
- [ ] Meet performance verbetering
- [ ] Cache invalidatie bij data wijzigingen

---

### Dag 10: Testing & Documentatie

#### 2.3 Integratie Tests

**Test alle informatieproducten samen:**

```bash
#!/bin/bash
# test-all-informatieproducten.sh

echo "=== VOLLEDIGE INFORMATIEPRODUCTEN TEST ==="

# Test meerdere personen
BSNS=("168149291" "216007574" "999999011")

for BSN in "${BSNS[@]}"; do
    echo ""
    echo "Testing BSN: $BSN"
    echo "---"
    
    RESPONSE=$(curl -s -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=$BSN&_limit=1")
    
    # Check alle velden
    VOORLETTERS=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].naam.voorletters')
    LEEFTIJD=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].leeftijd')
    AANSCHRIJFWIJZE=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].adressering.aanschrijfwijze')
    AANHEF=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].adressering.aanhef')
    ADRESREGEL1=$(echo $RESPONSE | jq -r '._embedded.ingeschrevenpersonen[0].adressering.adresregel1')
    
    echo "Voorletters: $VOORLETTERS"
    echo "Leeftijd: $LEEFTIJD"
    echo "Aanschrijfwijze: $AANSCHRIJFWIJZE"
    echo "Aanhef: $AANHEF"
    echo "Adresregel1: $ADRESREGEL1"
done

echo ""
echo "=== TEST VOLTOOID ==="
```

**Documentatie updaten:**

**Bestand:** `docs/INFORMATIEPRODUCTEN.md`

```markdown
# Informatieproducten Implementatie

## Overzicht

Alle 6 RvIG informatieproducten zijn geïmplementeerd volgens:
https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/

## Geïmplementeerde Producten

1. **Voorletters** - `naam.voorletters`
2. **Volledige Naam** - `naam.volledigeNaam`
3. **Leeftijd** - `leeftijd`
4. **Aanschrijfwijze** - `adressering.aanschrijfwijze`
5. **Aanhef** - `adressering.aanhef`
6. **Adresregels** - `adressering.adresregel1/2/3`
7. **Gezag** - `gezag` (voor minderjarigen)

## Gebruik

```php
// Informatieproducten worden automatisch toegevoegd aan alle
// personen responses via InformatieproductenService
$persoon = $controller->getIngeschrevenPersoon($bsn);

// Bevat nu:
// - $persoon['naam']['voorletters']
// - $persoon['leeftijd']
// - $persoon['adressering']['aanschrijfwijze']
// etc.
```

## Testing

Run tests:
```bash
./test-all-informatieproducten.sh
```
```

**Acties:**
- [ ] Run volledige test suite
- [ ] Documentatie schrijven
- [ ] Update API specificatie documenten
- [ ] Code review

---

## WEEK 3: Bewoning API & RNI (Prioriteit 2)

**Doel:** Implementeer ontbrekende RvIG functies  
**Impact:** +5% compliance (95% → 100%)

### Dag 11-13: Bewoning API

#### 3.1 BewoningController Aanmaken

**Bestand:** `lib/Controller/BewoningController.php`

```php
<?php
namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IDBConnection;

/**
 * Bewoning Controller
 * Implementeert RvIG Bewoning API
 * 
 * Referentie: https://developer.rvig.nl/brp-api/bewoning/specificatie/
 */
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
     * @param string $adresseerbaarObjectIdentificatie BAG ID
     * @return JSONResponse
     */
    public function getBewoning(string $adresseerbaarObjectIdentificatie): JSONResponse {
        try {
            $peildatum = $this->request->getParam('peildatum');
            $datumVan = $this->request->getParam('datumVan');
            $datumTot = $this->request->getParam('datumTot');
            
            // Validatie
            if (!$peildatum && (!$datumVan || !$datumTot)) {
                return new JSONResponse([
                    'type' => 'https://developer.rvig.nl/problems/invalid-params',
                    'title' => 'Een of meer parameters zijn niet correct',
                    'status' => 400,
                    'detail' => 'Specificeer peildatum OF datumVan+datumTot',
                    'invalid-params' => [
                        ['name' => 'peildatum', 'reason' => 'Peildatum of periode vereist']
                    ]
                ], 400);
            }
            
            // Haal bewoners op
            if ($peildatum) {
                $bewoners = $this->getBewonersPeildatum($adresseerbaarObjectIdentificatie, $peildatum);
            } else {
                $bewoners = $this->getBewonersPeriode($adresseerbaarObjectIdentificatie, $datumVan, $datumTot);
            }
            
            return new JSONResponse([
                '_embedded' => [
                    'bewoning' => $bewoners
                ],
                '_links' => [
                    'self' => [
                        'href' => '/adressen/' . $adresseerbaarObjectIdentificatie . '/bewoning'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'type' => 'https://developer.rvig.nl/problems/internal-server-error',
                'title' => 'Internal Server Error',
                'status' => 500,
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Haal bewoners op voor specifieke peildatum
     */
    private function getBewonersPeildatum(string $adresId, string $peildatum): array {
        $qb = $this->db->getQueryBuilder();
        
        // Query probev.vb_ax voor verblijfplaats historie
        // Deze tabel bevat alle verblijven met begin/eind datum
        
        // PDO connectie naar PostgreSQL
        $pdo = new \PDO(
            'pgsql:host=nextcloud-postgres;port=5432;dbname=bevax',
            'bevax_user',
            'bevax_secure_pass_2024'
        );
        
        // Query voor bewoners op peildatum
        $sql = "
            SELECT DISTINCT
                i.snr as burgerservicenummer,
                i.voornamen,
                i.geslachtsnaam,
                i.voorvoegsel,
                v.datum_begin as datumAanvangAdres,
                v.datum_einde as datumEindeAdres
            FROM probev.vb_ax v
            JOIN probev.inw_ax i ON v.pl_id = i.pl_id
            WHERE v.ax = 'A'
              AND v.hist = 'A'
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
        $bewoners = [];
        foreach ($results as $row) {
            $bewoners[] = [
                'burgerservicenummer' => $row['burgerservicenummer'],
                'naam' => [
                    'voornamen' => $row['voornamen'],
                    'voorvoegsel' => $row['voorvoegsel'],
                    'geslachtsnaam' => $row['geslachtsnaam']
                ],
                'datumAanvangAdres' => [
                    'datum' => $row['datumaanvangadres']
                ]
            ];
        }
        
        return $bewoners;
    }
    
    /**
     * Haal bewoners op voor periode
     */
    private function getBewonersPeriode(string $adresId, string $datumVan, string $datumTot): array {
        // Vergelijkbare query als peildatum, maar dan met periode check
        $pdo = new \PDO(
            'pgsql:host=nextcloud-postgres;port=5432;dbname=bevax',
            'bevax_user',
            'bevax_secure_pass_2024'
        );
        
        $sql = "
            SELECT DISTINCT
                i.snr as burgerservicenummer,
                i.voornamen,
                i.geslachtsnaam,
                i.voorvoegsel,
                v.datum_begin as datumAanvangAdres,
                v.datum_einde as datumEindeAdres
            FROM probev.vb_ax v
            JOIN probev.inw_ax i ON v.pl_id = i.pl_id
            WHERE v.ax = 'A'
              AND v.bag_id = :adres_id
              AND (
                  (v.datum_begin BETWEEN :datum_van AND :datum_tot)
                  OR (v.datum_einde BETWEEN :datum_van AND :datum_tot)
                  OR (v.datum_begin <= :datum_van AND (v.datum_einde IS NULL OR v.datum_einde >= :datum_tot))
              )
            ORDER BY v.datum_begin, i.geslachtsnaam
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':adres_id' => $adresId,
            ':datum_van' => $datumVan,
            ':datum_tot' => $datumTot
        ]);
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Transformeer naar RvIG format (groepeer per bewoning)
        $bewoningen = [];
        $currentGroup = null;
        
        foreach ($results as $row) {
            $key = $row['datumaanvangadres'] . '_' . ($row['datumeinde adres'] ?? 'huidig');
            
            if (!isset($bewoningen[$key])) {
                $bewoningen[$key] = [
                    'periode' => [
                        'datumVan' => $row['datumaanvangadres'],
                        'datumTot' => $row['datumeinde adres']
                    ],
                    'bewoners' => []
                ];
            }
            
            $bewoningen[$key]['bewoners'][] = [
                'burgerservicenummer' => $row['burgerservicenummer'],
                'naam' => [
                    'voornamen' => $row['voornamen'],
                    'voorvoegsel' => $row['voorvoegsel'],
                    'geslachtsnaam' => $row['geslachtsnaam']
                ]
            ];
        }
        
        return array_values($bewoningen);
    }
}
```

**Routes toevoegen in `appinfo/routes.php`:**

```php
[
    'name' => 'Bewoning#getBewoning',
    'url' => '/adressen/{adresseerbaarObjectIdentificatie}/bewoning',
    'verb' => 'GET'
],
```

**Acties:**
- [ ] Controller aanmaken
- [ ] Routes registreren
- [ ] PostgreSQL queries testen
- [ ] Response format valideren tegen RvIG spec

---

### Dag 14: RNI Ontsluiting

#### 3.2 RNI Support Toevoegen

**Uitbreiding in `HaalCentraalBrpController.php`:**

```php
public function getIngeschrevenPersonen(): JSONResponse {
    // ... bestaande code ...
    
    $inclusiefRni = $this->request->getParam('inclusiefRni') === 'true';
    
    // Pas query aan om RNI mee te nemen
    $objects = $this->getObjectsFromDatabase(
        $limit, 
        $page, 
        $search, 
        $schemaId, 
        $bsn, 
        $anummer, 
        $achternaam, 
        $geboortedatum, 
        $geboortedatumVan, 
        $geboortedatumTot, 
        $sort,
        $inclusiefRni  // Nieuwe parameter
    );
    
    // ...
}

private function getObjectsFromDatabase(
    int $limit,
    int $page,
    ?string $search = null,
    int $schemaId = null,
    ?string $bsn = null,
    ?string $anummer = null,
    ?string $achternaam = null,
    ?string $geboortedatum = null,
    ?string $geboortedatumVan = null,
    ?string $geboortedatumTot = null,
    ?string $sort = null,
    bool $inclusiefRni = false  // Nieuwe parameter
): array {
    // ... bestaande query ...
    
    if (!$inclusiefRni) {
        // Exclude RNI records (indicatie in database)
        $qb->andWhere($qb->expr()->neq(
            $qb->createFunction('JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.rni') . ')'),
            $qb->createNamedParameter('true')
        ));
    }
    
    // ... rest van query ...
}
```

**RNI Data in PostgreSQL:**

```php
/**
 * Haal RNI data op uit rni_ax tabel
 */
private function getRniDataFromPostgres(int $plId): ?array {
    $pdo = new \PDO(
        'pgsql:host=nextcloud-postgres;port=5432;dbname=bevax',
        'bevax_user',
        'bevax_secure_pass_2024'
    );
    
    $sql = "
        SELECT 
            land_code,
            land_omschrijving,
            datum_inschrijving,
            datum_uitschrijving
        FROM probev.rni_ax
        WHERE pl_id = :pl_id
          AND ax = 'A'
          AND hist = 'A'
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pl_id' => $plId]);
    $rni = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$rni) {
        return null;
    }
    
    return [
        'indicatieRNI' => true,
        'land' => [
            'code' => $rni['land_code'],
            'omschrijving' => $rni['land_omschrijving']
        ],
        'datumInschrijving' => [
            'datum' => $rni['datum_inschrijving']
        ]
    ];
}
```

**Acties:**
- [ ] Parameter `inclusiefRni` toevoegen
- [ ] Query aanpassen voor RNI filtering
- [ ] RNI data ophalen uit `rni_ax` tabel
- [ ] Test met RNI personen

---

### Dag 15: Week 3 Testing

**Test Bewoning API:**

```bash
#!/bin/bash
# test-bewoning.sh

echo "=== BEWONING API TEST ==="

# Test met peildatum
echo "Test 1: Bewoning op peildatum"
curl -u admin:admin "http://localhost:8080/apps/openregister/adressen/0363200000218705/bewoning?peildatum=2024-01-01" | jq

# Test met periode
echo "Test 2: Bewoning in periode"
curl -u admin:admin "http://localhost:8080/apps/openregister/adressen/0363200000218705/bewoning?datumVan=2023-01-01&datumTot=2024-12-31" | jq
```

**Test RNI:**

```bash
#!/bin/bash
# test-rni.sh

echo "=== RNI TEST ==="

# Test zonder RNI
echo "Test 1: Zonder RNI (default)"
curl -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?_limit=5" | jq '._embedded.ingeschrevenpersonen | length'

# Test met RNI
echo "Test 2: Met RNI"
curl -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?inclusiefRni=true&_limit=5" | jq '._embedded.ingeschrevenpersonen | length'
```

**Acties:**
- [ ] Test bewoning met verschillende adressen
- [ ] Test RNI filtering
- [ ] Verifieer response format tegen RvIG spec
- [ ] Performance test (query snelheid)

---

## WEEK 4: Finalisering & Testing (Prioriteit 3)

**Doel:** Details afwerken & volledige compliance valideren  
**Impact:** Laatste 5% (95% → 100%)

### Dag 16-17: Query Parameters Moderniseren

#### 4.1 Backward Compatible Parameter Support

**Update `HaalCentraalBrpController.php`:**

```php
public function getIngeschrevenPersonen(): JSONResponse {
    // Support both old and new parameter names
    $bsn = $this->request->getParam('burgerservicenummer') 
        ?? $this->request->getParam('bsn');
    
    $anummer = $this->request->getParam('aNummer')
        ?? $this->request->getParam('anummer')
        ?? $this->request->getParam('anr');
    
    // ... rest of method ...
}
```

**Update frontend JavaScript files:**

**`templates/prefilltest.php`:**
```javascript
// Was:
searchParams.bsn = searchTerm.trim();

// Wordt:
searchParams.burgerservicenummer = searchTerm.trim();
```

**`templates/haalcentraaltest.php`:**
```javascript
// Update all parameter references
var url = API_BASE + '/ingeschrevenpersonen?burgerservicenummer=' + bsn;
```

**Acties:**
- [ ] Controller updaten voor beide parameter namen
- [ ] Frontend updaten naar nieuwe namen
- [ ] Test dat oude parameters nog werken (backward compat)
- [ ] Documentatie updaten

---

### Dag 18: HTTP Headers & Error Responses

#### 4.2 HAL JSON Headers

**Update controllers:**

```php
public function getIngeschrevenPersonen(): JSONResponse {
    try {
        // ... bestaande code ...
        
        $response = new JSONResponse([
            '_embedded' => [
                'ingeschrevenpersonen' => $ingeschrevenPersonen
            ],
            // ...
        ]);
        
        // Set HAL JSON content type
        $response->addHeader('Content-Type', 'application/hal+json');
        
        // Support X-Correlation-ID
        $correlationId = $this->request->getHeader('X-Correlation-ID');
        if ($correlationId) {
            $response->addHeader('X-Correlation-ID', $correlationId);
        }
        
        return $response;
        
    } catch (\Exception $e) {
        return $this->createProblemResponse($e);
    }
}

/**
 * Create RFC 7807 Problem Details response
 */
private function createProblemResponse(\Exception $e, int $status = 500): JSONResponse {
    $response = new JSONResponse([
        'type' => 'https://developer.rvig.nl/problems/internal-server-error',
        'title' => 'Internal Server Error',
        'status' => $status,
        'detail' => $e->getMessage(),
        'instance' => $this->request->getRequestUri()
    ], $status);
    
    $response->addHeader('Content-Type', 'application/problem+json');
    
    return $response;
}
```

**Frontend header support:**

```javascript
headers: {
    'Accept': 'application/hal+json',
    'Content-Type': 'application/json',
    'X-Correlation-ID': generateUUID()
}

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}
```

**Acties:**
- [ ] HAL JSON content type headers toevoegen
- [ ] X-Correlation-ID support
- [ ] RFC 7807 error responses
- [ ] Test error scenarios

---

### Dag 19-20: Volledige Compliance Testing

#### 4.3 RvIG Compliance Test Suite

**Bestand:** `tests/Integration/RvigComplianceTest.php`

```php
<?php
namespace OCA\OpenRegister\Tests\Integration;

use Test\TestCase;

/**
 * RvIG BRP API Compliance Test Suite
 * 
 * Valideert volledige compliance met:
 * https://developer.rvig.nl/brp-api/overview/
 */
class RvigComplianceTest extends TestCase {
    
    private $baseUrl = 'http://localhost:8080/apps/openregister';
    private $auth = ['admin', 'admin'];
    
    public function testPersonenEndpointExists() {
        $response = $this->get('/ingeschrevenpersonen?_limit=1');
        $this->assertEquals(200, $response['status']);
    }
    
    public function testHalJsonResponseFormat() {
        $response = $this->get('/ingeschrevenpersonen?bsn=168149291');
        
        // Check HAL JSON structure
        $this->assertArrayHasKey('_embedded', $response['data']);
        $this->assertArrayHasKey('ingeschrevenpersonen', $response['data']['_embedded']);
        $this->assertArrayHasKey('_links', $response['data']);
    }
    
    public function testNestedObjectStructure() {
        $response = $this->get('/ingeschrevenpersonen?bsn=168149291');
        $persoon = $response['data']['_embedded']['ingeschrevenpersonen'][0];
        
        // Check nested objects
        $this->assertArrayHasKey('naam', $persoon);
        $this->assertArrayHasKey('voornamen', $persoon['naam']);
        $this->assertArrayHasKey('geslachtsnaam', $persoon['naam']);
        
        $this->assertArrayHasKey('geboorte', $persoon);
        $this->assertArrayHasKey('datum', $persoon['geboorte']);
    }
    
    public function testInformatieproductenPresent() {
        $response = $this->get('/ingeschrevenpersonen?bsn=168149291');
        $persoon = $response['data']['_embedded']['ingeschrevenpersonen'][0];
        
        // Check informatieproducten
        $this->assertArrayHasKey('voorletters', $persoon['naam']);
        $this->assertArrayHasKey('leeftijd', $persoon);
        $this->assertArrayHasKey('adressering', $persoon);
        
        // Check adressering details
        $this->assertArrayHasKey('aanschrijfwijze', $persoon['adressering']);
        $this->assertArrayHasKey('aanhef', $persoon['adressering']);
        $this->assertArrayHasKey('gebruikInLopendeTekst', $persoon['adressering']);
    }
    
    public function testBewoningEndpoint() {
        $response = $this->get('/adressen/0363200000218705/bewoning?peildatum=2024-01-01');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('_embedded', $response['data']);
        $this->assertArrayHasKey('bewoning', $response['data']['_embedded']);
    }
    
    public function testRniParameter() {
        // Without RNI
        $response1 = $this->get('/ingeschrevenpersonen?_limit=100');
        $count1 = count($response1['data']['_embedded']['ingeschrevenpersonen']);
        
        // With RNI
        $response2 = $this->get('/ingeschrevenpersonen?inclusiefRni=true&_limit=100');
        $count2 = count($response2['data']['_embedded']['ingeschrevenpersonen']);
        
        // RNI should give more results (if RNI data exists)
        $this->assertGreaterThanOrEqual($count1, $count2);
    }
    
    public function testErrorResponseFormat() {
        $response = $this->get('/ingeschrevenpersonen?bsn=invalid');
        
        // Should return RFC 7807 problem details
        $this->assertArrayHasKey('type', $response['data']);
        $this->assertArrayHasKey('title', $response['data']);
        $this->assertArrayHasKey('status', $response['data']);
        $this->assertArrayHasKey('detail', $response['data']);
    }
    
    private function get(string $path): array {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', $this->auth));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/hal+json',
            'X-Correlation-ID: test-' . uniqid()
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $status,
            'data' => json_decode($response, true)
        ];
    }
}
```

**Run compliance tests:**

```bash
docker exec nextcloud php vendor/bin/phpunit tests/Integration/RvigComplianceTest.php
```

**Acties:**
- [ ] Schrijf alle compliance tests
- [ ] Run test suite
- [ ] Fix failing tests
- [ ] Achieve 100% pass rate

---

### Dag 20: Documentatie & Launch

#### 4.4 Documentatie Finaliseren

**Bestand:** `docs/RVIG-COMPLIANCE.md`

```markdown
# RvIG BRP API Compliance

## Status: ✅ 100% Compliant

Onze implementatie voldoet volledig aan de RvIG BRP API specificatie:
https://developer.rvig.nl/brp-api/overview/

## Functie 1: Personen API ✅

### Endpoints
- ✅ `GET /ingeschrevenpersonen`
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}`
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}/partners`
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}/kinderen`
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}/ouders`
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}/nationaliteiten`
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}/verblijfplaats`

### Informatieproducten ✅
1. ✅ Voorletters (`naam.voorletters`)
2. ✅ Volledige naam (`naam.volledigeNaam`)
3. ✅ Leeftijd (`leeftijd`)
4. ✅ Aanschrijfwijze (`adressering.aanschrijfwijze`)
5. ✅ Aanhef (`adressering.aanhef`)
6. ✅ Adresregels (`adressering.adresregel1/2/3`)
7. ✅ Gezag (`gezag`, voor minderjarigen)

## Functie 2: Bewoning API ✅

### Endpoints
- ✅ `GET /adressen/{id}/bewoning?peildatum={datum}`
- ✅ `GET /adressen/{id}/bewoning?datumVan={datum}&datumTot={datum}`

## Functie 3: Verblijfplaatshistorie ✅

### Endpoints
- ✅ `GET /ingeschrevenpersonen/{bsn}/verblijfplaatshistorie`
- ✅ Support voor `peildatum` parameter
- ✅ Support voor `datumVan`/`datumTot` parameters

## Query Parameters ✅

- ✅ `burgerservicenummer` (met backward compatibility voor `bsn`)
- ✅ `aNummer` (met backward compatibility voor `anr`)
- ✅ `achternaam`
- ✅ `geboortedatum`
- ✅ `inclusiefRni`

## Response Format ✅

- ✅ HAL JSON (`application/hal+json`)
- ✅ `_embedded` structure
- ✅ `_links` for HATEOAS
- ✅ `page` paginatie info

## Error Handling ✅

- ✅ RFC 7807 Problem Details
- ✅ Correcte HTTP status codes
- ✅ Duidelijke error messages

## HTTP Headers ✅

- ✅ `Accept: application/hal+json`
- ✅ `Content-Type: application/hal+json`
- ✅ `X-Correlation-ID` support

## Testing

Run volledige compliance test:
```bash
./test-rvig-compliance.sh
```

## Certificering

Deze implementatie is gevalideerd tegen de RvIG BRP API specificatie versie 2.0.
```

**Changelog:**

```markdown
# Changelog - RvIG Compliance Implementatie

## Week 1-2: Informatieproducten
- ✅ InformatieproductenService geïmplementeerd
- ✅ Voorletters berekening
- ✅ Leeftijd berekening
- ✅ Aanschrijfwijze generatie
- ✅ Aanhef generatie
- ✅ Adresregels voor enveloppen
- ✅ Gezag informatieproduct
- ✅ Performance caching

## Week 3: Bewoning & RNI
- ✅ BewoningController geïmplementeerd
- ✅ Peildatum queries
- ✅ Periode queries
- ✅ RNI filtering parameter
- ✅ RNI data ontsluiting

## Week 4: Finalisering
- ✅ Query parameters gemoderniseerd
- ✅ HAL JSON headers
- ✅ RFC 7807 error responses
- ✅ X-Correlation-ID support
- ✅ Volledige test suite
- ✅ Documentatie

## Compliance Score
- Voor: 60%
- Na: 100% ✅
```

**Acties:**
- [ ] Documentatie volledig maken
- [ ] Changelog schrijven
- [ ] API specificatie exporteren
- [ ] Stakeholders informeren

---

## 📊 Implementatie Overzicht

### Planning & Tijdsinschatting

| Week | Focus | Dagen | Deliverables |
|------|-------|-------|--------------|
| **Week 1** | Informatieproducten Kern | 5 | Service layer, voorletters, leeftijd, adressering |
| **Week 2** | Informatieproducten Uitbreiden | 5 | Gezag, caching, optimalisatie |
| **Week 3** | Bewoning & RNI | 5 | Bewoning API, RNI ontsluiting |
| **Week 4** | Finalisering | 5 | Parameters, headers, testing, docs |

**Totaal:** 20 werkdagen = 4 weken

---

### Resource Requirements

**Team:**
- 1 Senior PHP Developer (20 dagen)
- 1 Tester (10 dagen, parallel)
- 1 Tech Writer (5 dagen, parallel)

**Infrastructure:**
- ✅ Bestaande setup (geen wijzigingen)
- ✅ PostgreSQL probev database (al beschikbaar)
- ✅ MariaDB nextcloud database (al beschikbaar)

**Dependencies:**
- ✅ Alle benodigde data al in probev
- ✅ Database structuur correct
- ✅ Nested objects al geïmplementeerd

---

### Success Criteria

**Functioneel:**
- ✅ Alle 6 informatieproducten werken correct
- ✅ Bewoning API peildatum/periode queries
- ✅ RNI filtering functionaliteit
- ✅ 100% compliance test suite groen

**Non-Functioneel:**
- ✅ Response tijd < 500ms (P95)
- ✅ Caching effectief (>80% hit rate)
- ✅ Backward compatibility behouden
- ✅ Zero breaking changes

**Kwaliteit:**
- ✅ Code coverage > 90%
- ✅ Alle PHPUnit tests groen
- ✅ RvIG compliance tests 100% pass
- ✅ Documentatie compleet

---

## 🎯 Prioritering

### Must Have (Week 1-2)
1. ✅ **Informatieproducten** - Grootste gap, kritiek voor compliance
2. ✅ **Caching** - Performance essentieel

### Should Have (Week 3)
3. ✅ **Bewoning API** - RvIG functie 2, belangrijk
4. ✅ **RNI Ontsluiting** - Volledigheid

### Could Have (Week 4)
5. ⚠️ **Query parameters** - Nice to have, backward compat OK
6. ⚠️ **Headers** - Details, niet kritiek

---

## 🚀 Quick Start

### Voor Developers

**Start implementatie:**

```bash
# 1. Maak feature branch
git checkout -b feature/rvig-compliance

# 2. Maak service file
touch lib/Service/InformatieproductenService.php

# 3. Volg plan week 1 dag 1-2
# ... implementeer InformatieproductenService

# 4. Run tests
docker exec nextcloud php vendor/bin/phpunit tests/Unit/Service/InformatieproductenServiceTest.php

# 5. Integreer in controller
# ... volg plan week 1 dag 3-4

# 6. Test API response
./test-informatieproducten.sh
```

---

## 📝 Checklist

### Week 1
- [ ] InformatieproductenService aangemaakt
- [ ] Voorletters berekening geïmplementeerd
- [ ] Leeftijd berekening geïmplementeerd
- [ ] Aanschrijfwijze geïmplementeerd
- [ ] Aanhef geïmplementeerd
- [ ] Adresregels geïmplementeerd
- [ ] Service geïntegreerd in controller
- [ ] Unit tests geschreven (>90% coverage)
- [ ] API response test geslaagd

### Week 2
- [ ] Gezag informatieproduct geïmplementeerd
- [ ] Caching geïmplementeerd
- [ ] Performance geoptimaliseerd
- [ ] Integratie tests geschreven
- [ ] Documentatie geüpdatet

### Week 3
- [ ] BewoningController aangemaakt
- [ ] Peildatum queries werkend
- [ ] Periode queries werkend
- [ ] RNI parameter geïmplementeerd
- [ ] RNI data ontsluiting werkend
- [ ] Bewoning tests geslaagd

### Week 4
- [ ] Query parameters gemoderniseerd
- [ ] HAL JSON headers toegevoegd
- [ ] RFC 7807 errors geïmplementeerd
- [ ] X-Correlation-ID support
- [ ] RvIG compliance test suite 100%
- [ ] Documentatie compleet
- [ ] Changelog geschreven
- [ ] Stakeholder demo

---

## 🎉 Resultaat

**Van:**
```
⚠️ 60% RvIG BRP API Compliant
- ✅ Basis endpoints
- ✅ Data structuur
- ❌ Informatieproducten
- ❌ Bewoning API
- ❌ RNI
```

**Naar:**
```
✅ 100% RvIG BRP API Compliant
- ✅ Alle endpoints
- ✅ Data structuur
- ✅ Informatieproducten (6x)
- ✅ Bewoning API
- ✅ RNI ontsluiting
- ✅ HAL JSON format
- ✅ RFC 7807 errors
```

**Impact:** Production-ready, volledig certificeerbare RvIG BRP API implementatie! 🚀

---

## 📞 Contact & Support

**Vragen tijdens implementatie:**
- Tech Lead: [naam]
- RvIG Support: info@rvig.nl
- Developer Portal: https://developer.rvig.nl/

**Documentatie:**
- RvIG BRP API: https://developer.rvig.nl/brp-api/overview/
- Informatieproducten: https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/
- Bewoning: https://developer.rvig.nl/brp-api/bewoning/specificatie/

---

**Status:** 📋 Plan Ready for Execution  
**Go/No-Go:** Pending stakeholder approval  
**Start datum:** TBD  
**Eind datum:** Start + 4 weken
