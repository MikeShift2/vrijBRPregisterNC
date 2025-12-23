<?php
namespace OCA\OpenRegister\Tests\Unit\Service;

use OCA\OpenRegister\Service\GezagService;
use OCP\IDBConnection;
use Test\TestCase;

/**
 * Unit tests voor GezagService
 * 
 * Test gezagsrelaties voor minderjarigen
 */
class GezagServiceTest extends TestCase {
    
    private GezagService $service;
    private $dbMock;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Mock database connection
        $this->dbMock = $this->createMock(IDBConnection::class);
        $this->service = new GezagService($this->dbMock);
    }
    
    // ========================================================================
    // Test berekenGezag() - Leeftijd checks
    // ========================================================================
    
    public function testBerekenGezag_Meerderjarig_ReturnsNull() {
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => 18
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result, 'Gezag moet null zijn voor meerderjarigen (18+)');
    }
    
    public function testBerekenGezag_25Jaar_ReturnsNull() {
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => 25
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result);
    }
    
    public function testBerekenGezag_GeenLeeftijd_ReturnsNull() {
        $persoon = [
            'burgerservicenummer' => '123456789'
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result, 'Gezag moet null zijn als leeftijd ontbreekt');
    }
    
    public function testBerekenGezag_LeeftijdNull_ReturnsNull() {
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => null
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result);
    }
    
    public function testBerekenGezag_GeenBSN_ReturnsNull() {
        $persoon = [
            'leeftijd' => 10
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result, 'Gezag moet null zijn als BSN ontbreekt');
    }
    
    // ========================================================================
    // Test berekenGezag() - Minderjarigen
    // ========================================================================
    
    public function testBerekenGezag_17Jaar_ReturnsGezag() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 17
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertIsArray($result, 'Gezag moet een array zijn voor minderjarigen');
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('minderjarige', $result);
        $this->assertEquals('GezagOuder', $result['type']);
    }
    
    public function testBerekenGezag_10Jaar_ReturnsGezag() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 10
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('minderjarige', $result);
        $this->assertEquals('999999011', $result['minderjarige']['burgerservicenummer']);
    }
    
    public function testBerekenGezag_0Jaar_ReturnsGezag() {
        // Baby's hebben ook gezag
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 0
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
    }
    
    // ========================================================================
    // Test enrichPersoonMetGezag()
    // ========================================================================
    
    public function testEnrichPersoonMetGezag_Minderjarig_AddsGezag() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 15,
            'naam' => [
                'voornamen' => 'Jan',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $result = $this->service->enrichPersoonMetGezag($persoon);
        
        $this->assertArrayHasKey('gezag', $result, 'Gezag moet toegevoegd zijn voor minderjarigen');
        $this->assertIsArray($result['gezag']);
    }
    
    public function testEnrichPersoonMetGezag_Meerderjarig_NoGezag() {
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => 20,
            'naam' => [
                'voornamen' => 'Jan',
                'geslachtsnaam' => 'Jansen'
            ]
        ];
        
        $result = $this->service->enrichPersoonMetGezag($persoon);
        
        $this->assertArrayNotHasKey('gezag', $result, 'Gezag mag niet toegevoegd zijn voor meerderjarigen');
    }
    
    public function testEnrichPersoonMetGezag_PreservesExistingFields() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 15,
            'naam' => [
                'voornamen' => 'Jan',
                'geslachtsnaam' => 'Jansen'
            ],
            'adressering' => [
                'aanschrijfwijze' => 'De heer J. Jansen'
            ]
        ];
        
        $result = $this->service->enrichPersoonMetGezag($persoon);
        
        // Check dat bestaande velden behouden blijven
        $this->assertArrayHasKey('naam', $result);
        $this->assertArrayHasKey('adressering', $result);
        $this->assertEquals('De heer J. Jansen', $result['adressering']['aanschrijfwijze']);
        
        // Check dat gezag is toegevoegd
        $this->assertArrayHasKey('gezag', $result);
    }
    
    // ========================================================================
    // Test gezag structure
    // ========================================================================
    
    public function testGezagStructure_HasRequiredFields() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 12
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        // Check RvIG required fields
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('minderjarige', $result);
        $this->assertArrayHasKey('ouders', $result);
        
        // Check minderjarige structure
        $this->assertIsArray($result['minderjarige']);
        $this->assertArrayHasKey('burgerservicenummer', $result['minderjarige']);
        
        // Check ouders is array
        $this->assertIsArray($result['ouders']);
    }
    
    public function testGezagStructure_TypeIsGezagOuder() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 12
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertEquals('GezagOuder', $result['type']);
    }
    
    public function testGezagStructure_MinderjarigeBSNCorrect() {
        $bsn = '999999011';
        $persoon = [
            'burgerservicenummer' => $bsn,
            'leeftijd' => 12
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertEquals($bsn, $result['minderjarige']['burgerservicenummer']);
    }
    
    public function testGezagStructure_OudersIsNotEmpty() {
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 12
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        // Moet minimaal 1 ouder hebben (of default gezag)
        $this->assertIsArray($result['ouders']);
        $this->assertNotEmpty($result['ouders']);
    }
    
    // ========================================================================
    // Test edge cases
    // ========================================================================
    
    public function testBerekenGezag_Exactly18_ReturnsNull() {
        // 18 jaar is meerderjarig
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => 18
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result);
    }
    
    public function testBerekenGezag_Exactly17_ReturnsGezag() {
        // 17 jaar is minderjarig
        $persoon = [
            'burgerservicenummer' => '999999011',
            'leeftijd' => 17
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertIsArray($result);
    }
    
    public function testBerekenGezag_NegativeAge_ReturnsNull() {
        // Edge case: negatieve leeftijd (data error)
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => -1
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        // Negatieve leeftijd wordt behandeld als invalid
        $this->assertNull($result);
    }
    
    public function testBerekenGezag_VeryOld_ReturnsNull() {
        // Edge case: zeer oude persoon
        $persoon = [
            'burgerservicenummer' => '123456789',
            'leeftijd' => 120
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertNull($result);
    }
    
    // ========================================================================
    // Test default gezag
    // ========================================================================
    
    public function testDefaultGezag_HasOuderlijkGezag() {
        // Als geen data gevonden, default naar ouderlijk gezag
        $persoon = [
            'burgerservicenummer' => '999999999', // BSN dat niet in DB zit
            'leeftijd' => 12
        ];
        
        $result = $this->service->berekenGezag($persoon);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('ouders', $result);
        
        // Check dat er minimaal 1 ouder is met soortGezag
        $this->assertNotEmpty($result['ouders']);
        $this->assertArrayHasKey('soortGezag', $result['ouders'][0]);
        $this->assertEquals('ouderlijkGezag', $result['ouders'][0]['soortGezag']);
    }
}
