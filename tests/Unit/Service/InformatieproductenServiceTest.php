<?php
namespace OCA\OpenRegister\Tests\Unit\Service;

use OCA\OpenRegister\Service\InformatieproductenService;
use Test\TestCase;

/**
 * Unit tests voor InformatieproductenService
 * 
 * Test alle informatieproducten volgens RvIG BRP API specificatie
 */
class InformatieproductenServiceTest extends TestCase {
    
    private InformatieproductenService $service;
    
    protected function setUp(): void {
        parent::setUp();
        $this->service = new InformatieproductenService();
    }
    
    // ========================================================================
    // Test berekenVoorletters()
    // ========================================================================
    
    public function testBerekenVoorletters_SingleName() {
        $result = $this->service->berekenVoorletters('Jan');
        $this->assertEquals('J.', $result);
    }
    
    public function testBerekenVoorletters_MultipleNames() {
        $result = $this->service->berekenVoorletters('Jan Pieter Marie');
        $this->assertEquals('J.P.M.', $result);
    }
    
    public function testBerekenVoorletters_ArrayInput() {
        $result = $this->service->berekenVoorletters(['Jan', 'Pieter']);
        $this->assertEquals('J.P.', $result);
    }
    
    public function testBerekenVoorletters_Empty() {
        $result = $this->service->berekenVoorletters('');
        $this->assertEquals('', $result);
    }
    
    public function testBerekenVoorletters_Null() {
        $result = $this->service->berekenVoorletters(null);
        $this->assertEquals('', $result);
    }
    
    public function testBerekenVoorletters_ExtraSpaces() {
        $result = $this->service->berekenVoorletters('  Jan   Pieter  ');
        $this->assertEquals('J.P.', $result);
    }
    
    // ========================================================================
    // Test berekenLeeftijd()
    // ========================================================================
    
    public function testBerekenLeeftijd_ValidDate() {
        // Test met bekende leeftijd (25 jaar geleden)
        $geboortedatum = date('Y-m-d', strtotime('-25 years'));
        $result = $this->service->berekenLeeftijd($geboortedatum);
        $this->assertEquals(25, $result);
    }
    
    public function testBerekenLeeftijd_50Years() {
        $geboortedatum = date('Y-m-d', strtotime('-50 years'));
        $result = $this->service->berekenLeeftijd($geboortedatum);
        $this->assertEquals(50, $result);
    }
    
    public function testBerekenLeeftijd_Null() {
        $result = $this->service->berekenLeeftijd(null);
        $this->assertNull($result);
    }
    
    public function testBerekenLeeftijd_EmptyString() {
        $result = $this->service->berekenLeeftijd('');
        $this->assertNull($result);
    }
    
    public function testBerekenLeeftijd_InvalidDate() {
        $result = $this->service->berekenLeeftijd('invalid-date');
        $this->assertNull($result);
    }
    
    public function testBerekenLeeftijd_BRPFormat() {
        // Test met echte BRP datum (JJJJ-MM-DD formaat)
        $result = $this->service->berekenLeeftijd('1974-03-15');
        $expectedAge = (int)date('Y') - 1974;
        // Afhankelijk van huidige datum kan dit 50 of 51 zijn
        $this->assertGreaterThanOrEqual(50, $result);
        $this->assertLessThanOrEqual(51, $result);
    }
    
    // ========================================================================
    // Test berekenVolledigeNaam()
    // ========================================================================
    
    public function testBerekenVolledigeNaam_Basic() {
        $naam = [
            'voornamen' => 'Jan',
            'geslachtsnaam' => 'Jansen'
        ];
        $result = $this->service->berekenVolledigeNaam($naam);
        $this->assertEquals('Jan Jansen', $result);
    }
    
    public function testBerekenVolledigeNaam_MetVoorvoegsel() {
        $naam = [
            'voornamen' => 'Jan Pieter',
            'voorvoegsel' => 'van',
            'geslachtsnaam' => 'Jansen'
        ];
        $result = $this->service->berekenVolledigeNaam($naam);
        $this->assertEquals('Jan Pieter van Jansen', $result);
    }
    
    public function testBerekenVolledigeNaam_MetAdel() {
        $naam = [
            'adellijkeTitel' => 'Baron',
            'voornamen' => 'Jan',
            'geslachtsnaam' => 'Jansen'
        ];
        $result = $this->service->berekenVolledigeNaam($naam);
        $this->assertEquals('Baron Jan Jansen', $result);
    }
    
    public function testBerekenVolledigeNaam_AllesCompleet() {
        $naam = [
            'adellijkeTitel' => ['omschrijving' => 'Graaf'],
            'voornamen' => 'Jan Pieter Marie',
            'voorvoegsel' => 'van den',
            'geslachtsnaam' => 'Berg'
        ];
        $result = $this->service->berekenVolledigeNaam($naam);
        $this->assertEquals('Graaf Jan Pieter Marie van den Berg', $result);
    }
    
    // ========================================================================
    // Test berekenAanschrijfwijze()
    // ========================================================================
    
    public function testBerekenAanschrijfwijze_Man() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voornamen' => 'Jan Pieter',
                'voorvoegsel' => 'van',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        $result = $this->service->berekenAanschrijfwijze($persoon);
        $this->assertEquals('De heer J.P. van Jansen', $result);
    }
    
    public function testBerekenAanschrijfwijze_Vrouw() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voornamen' => 'Maria',
                'voorvoegsel' => 'de',
                'geslachtsnaam' => 'Vries'
            ]
        ];
        $result = $this->service->berekenAanschrijfwijze($persoon);
        $this->assertEquals('Mevrouw M. de Vries', $result);
    }
    
    public function testBerekenAanschrijfwijze_MetGeslachtCode() {
        $persoon = [
            'geslachtsaanduiding' => 'M',
            'naam' => [
                'voornamen' => 'Jan',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        $result = $this->service->berekenAanschrijfwijze($persoon);
        $this->assertEquals('De heer J. Jansen', $result);
    }
    
    public function testBerekenAanschrijfwijze_MetBestaandeVoorletters() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voorletters' => 'M.A.',
                'voornamen' => 'Maria Anna',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        $result = $this->service->berekenAanschrijfwijze($persoon);
        $this->assertEquals('Mevrouw M.A. Jansen', $result);
    }
    
    // ========================================================================
    // Test berekenAanhef()
    // ========================================================================
    
    public function testBerekenAanhef_Man() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voorvoegsel' => 'van',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        $result = $this->service->berekenAanhef($persoon);
        $this->assertEquals('Geachte heer Van Jansen', $result);
    }
    
    public function testBerekenAanhef_Vrouw() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voorvoegsel' => 'de',
                'geslachtsnaam' => 'Vries'
            ]
        ];
        $result = $this->service->berekenAanhef($persoon);
        $this->assertEquals('Geachte mevrouw De Vries', $result);
    }
    
    public function testBerekenAanhef_VoorvoegselMetHoofdletter() {
        // Voorvoegsel moet met hoofdletter in aanhef
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voorvoegsel' => 'van den',
                'geslachtsnaam' => 'Berg'
            ]
        ];
        $result = $this->service->berekenAanhef($persoon);
        $this->assertEquals('Geachte heer Van den Berg', $result);
    }
    
    // ========================================================================
    // Test berekenGebruikInLopendeTekst()
    // ========================================================================
    
    public function testBerekenGebruikInLopendeTekst_Man() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voorvoegsel' => 'van',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        $result = $this->service->berekenGebruikInLopendeTekst($persoon);
        $this->assertEquals('de heer Van Jansen', $result);
    }
    
    public function testBerekenGebruikInLopendeTekst_Vrouw() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voorvoegsel' => 'de',
                'geslachtsnaam' => 'Vries'
            ]
        ];
        $result = $this->service->berekenGebruikInLopendeTekst($persoon);
        $this->assertEquals('mevrouw De Vries', $result);
    }
    
    // ========================================================================
    // Test berekenAdresregels()
    // ========================================================================
    
    public function testBerekenAdresregels_Volledig() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voornamen' => 'Jan',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $verblijfplaats = [
            'straatnaam' => 'Hoofdstraat',
            'huisnummer' => 123,
            'huisnummertoevoeging' => 'A',
            'postcode' => '1234AB',
            'woonplaatsnaam' => 'Amsterdam'
        ];
        
        $result = $this->service->berekenAdresregels($persoon, $verblijfplaats);
        
        $this->assertCount(3, $result);
        $this->assertEquals('De heer J. Jansen', $result[0]);
        $this->assertEquals('Hoofdstraat 123 A', $result[1]);
        $this->assertEquals('1234AB  AMSTERDAM', $result[2]);
    }
    
    public function testBerekenAdresregels_MetHuisletter() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voornamen' => 'Maria',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $verblijfplaats = [
            'straatnaam' => 'Dorpsstraat',
            'huisnummer' => 45,
            'huisletter' => 'B',
            'postcode' => '5678CD',
            'woonplaatsnaam' => 'Rotterdam'
        ];
        
        $result = $this->service->berekenAdresregels($persoon, $verblijfplaats);
        
        $this->assertEquals('Dorpsstraat 45B', $result[1]);
        $this->assertEquals('5678CD  ROTTERDAM', $result[2]);
    }
    
    public function testBerekenAdresregels_ZonderToevoeging() {
        $persoon = [
            'geslachtsaanduiding' => 'man',
            'naam' => [
                'voornamen' => 'Pieter',
                'geslachtsnaam' => 'de Vries'
            ]
        ];
        
        $verblijfplaats = [
            'straatnaam' => 'Kerkstraat',
            'huisnummer' => 100,
            'postcode' => '9012EF',
            'woonplaatsnaam' => 'Den Haag'
        ];
        
        $result = $this->service->berekenAdresregels($persoon, $verblijfplaats);
        
        $this->assertEquals('Kerkstraat 100', $result[1]);
        $this->assertEquals('9012EF  DEN HAAG', $result[2]);
    }
    
    public function testBerekenAdresregels_WoonplaatsHoofdletters() {
        $persoon = [
            'geslachtsaanduiding' => 'vrouw',
            'naam' => [
                'voornamen' => 'Anna',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $verblijfplaats = [
            'straatnaam' => 'Laan',
            'huisnummer' => 1,
            'postcode' => '1111AA',
            'woonplaatsnaam' => 's-Hertogenbosch'
        ];
        
        $result = $this->service->berekenAdresregels($persoon, $verblijfplaats);
        
        // Woonplaats moet hoofdletters zijn
        $this->assertEquals('1111AA  \'S-HERTOGENBOSCH', $result[2]);
    }
    
    // ========================================================================
    // Test enrichPersoon() - Integratietest
    // ========================================================================
    
    public function testEnrichPersoon_Volledig() {
        $persoon = [
            'burgerservicenummer' => '123456789',
            'naam' => [
                'voornamen' => 'Jan Pieter',
                'voorvoegsel' => 'van',
                'geslachtsnaam' => 'Jansen'
            ],
            'geboorte' => [
                'datum' => [
                    'datum' => date('Y-m-d', strtotime('-42 years'))
                ]
            ],
            'geslachtsaanduiding' => 'man',
            'verblijfplaats' => [
                'straatnaam' => 'Hoofdstraat',
                'huisnummer' => 123,
                'postcode' => '1234AB',
                'woonplaatsnaam' => 'Amsterdam'
            ]
        ];
        
        $result = $this->service->enrichPersoon($persoon);
        
        // Check voorletters toegevoegd
        $this->assertArrayHasKey('voorletters', $result['naam']);
        $this->assertEquals('J.P.', $result['naam']['voorletters']);
        
        // Check volledige naam
        $this->assertArrayHasKey('volledigeNaam', $result['naam']);
        $this->assertEquals('Jan Pieter van Jansen', $result['naam']['volledigeNaam']);
        
        // Check leeftijd
        $this->assertArrayHasKey('leeftijd', $result);
        $this->assertEquals(42, $result['leeftijd']);
        
        // Check adressering object
        $this->assertArrayHasKey('adressering', $result);
        $this->assertArrayHasKey('aanschrijfwijze', $result['adressering']);
        $this->assertArrayHasKey('aanhef', $result['adressering']);
        $this->assertArrayHasKey('gebruikInLopendeTekst', $result['adressering']);
        
        // Check adresregels
        $this->assertArrayHasKey('adresregel1', $result['adressering']);
        $this->assertArrayHasKey('adresregel2', $result['adressering']);
        $this->assertArrayHasKey('adresregel3', $result['adressering']);
    }
    
    public function testEnrichPersoon_ZonderAdres() {
        $persoon = [
            'burgerservicenummer' => '123456789',
            'naam' => [
                'voornamen' => 'Maria',
                'geslachtsnaam' => 'de Vries'
            ],
            'geboorte' => [
                'datum' => [
                    'datum' => '1980-06-15'
                ]
            ],
            'geslachtsaanduiding' => 'vrouw'
        ];
        
        $result = $this->service->enrichPersoon($persoon);
        
        // Adressering object moet er wel zijn
        $this->assertArrayHasKey('adressering', $result);
        
        // Maar geen adresregels (want geen adres)
        $this->assertArrayNotHasKey('adresregel1', $result['adressering']);
    }
    
    public function testEnrichPersoon_MetBestaandeVoorletters() {
        // Als voorletters al bestaan, niet overschrijven
        $persoon = [
            'naam' => [
                'voorletters' => 'J.P.M.',
                'voornamen' => 'Jan Pieter',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $result = $this->service->enrichPersoon($persoon);
        
        // Bestaande voorletters blijven
        $this->assertEquals('J.P.M.', $result['naam']['voorletters']);
    }
}
