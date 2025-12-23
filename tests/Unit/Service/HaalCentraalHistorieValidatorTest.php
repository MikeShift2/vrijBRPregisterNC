<?php
/**
 * Unit Tests voor HaalCentraalHistorieValidator
 * 
 * Test alle validatie scenario's voor de Haal Centraal BRP Historie API 2.0
 */

namespace OCA\OpenRegister\Tests\Unit\Service;

use OCA\OpenRegister\Service\HaalCentraalHistorieValidator;
use PHPUnit\Framework\TestCase;

class HaalCentraalHistorieValidatorTest extends TestCase {
    
    private HaalCentraalHistorieValidator $validator;
    
    protected function setUp(): void {
        parent::setUp();
        $this->validator = new HaalCentraalHistorieValidator();
    }
    
    /**
     * Test: Valide response met één verblijfplaats
     */
    public function testValidResponseWithOneVerblijfplaats(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => [
                            'datum' => '2020-01-01'
                        ]
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => '/ingeschrevenpersonen/123456789/verblijfplaatshistorie'
                ],
                'ingeschrevenpersoon' => [
                    'href' => '/ingeschrevenpersonen/123456789'
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid'], 'Response moet valide zijn');
        $this->assertEmpty($result['errors'], 'Geen errors verwacht');
    }
    
    /**
     * Test: Valide response met meerdere verblijfplaatsen
     */
    public function testValidResponseWithMultipleVerblijfplaatsen(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => ['datum' => '2020-01-01'],
                        'datumEindeGeldigheid' => ['datum' => '2021-01-01']
                    ],
                    [
                        'straatnaam' => 'Nieuwstraat',
                        'huisnummer' => 456,
                        'postcode' => '5678CD',
                        'woonplaatsnaam' => 'Utrecht',
                        'datumAanvangAdres' => ['datum' => '2021-01-02']
                    ]
                ]
            ],
            '_links' => [
                'self' => ['href' => '/ingeschrevenpersonen/123456789/verblijfplaatshistorie'],
                'ingeschrevenpersoon' => ['href' => '/ingeschrevenpersonen/123456789']
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test: Valide response met lege historie array
     */
    public function testValidResponseWithEmptyHistorie(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => []
            ],
            '_links' => [
                'self' => ['href' => '/ingeschrevenpersonen/123456789/verblijfplaatshistorie'],
                'ingeschrevenpersoon' => ['href' => '/ingeschrevenpersonen/123456789']
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test: Response zonder _embedded
     */
    public function testResponseWithoutEmbedded(): void {
        $response = [
            '_links' => [
                'self' => ['href' => '/ingeschrevenpersonen/123456789/verblijfplaatshistorie']
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('_embedded', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Response zonder verblijfplaatshistorie
     */
    public function testResponseWithoutVerblijfplaatshistorie(): void {
        $response = [
            '_embedded' => [
                'otherData' => []
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('verblijfplaatshistorie', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Verblijfplaats zonder adresvelden
     */
    public function testVerblijfplaatsWithoutAddressFields(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'datumAanvangAdres' => ['datum' => '2020-01-01']
                        // Geen adresvelden
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('minimaal één adresveld', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Ongeldig postcode formaat
     */
    public function testInvalidPostcodeFormat(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '12345', // Ongeldig formaat
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('postcode', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Postcode met spatie (moet ook werken)
     */
    public function testPostcodeWithSpace(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234 AB', // Met spatie
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test: Ongeldig datum formaat
     */
    public function testInvalidDatumFormat(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => [
                            'datum' => '2020/01/01' // Ongeldig formaat
                        ]
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('datum', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Ongeldige datum (niet-bestaande datum)
     */
    public function testInvalidDate(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => [
                            'datum' => '2020-02-30' // Ongeldige datum
                        ]
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Ongeldige datum', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Datum te ver in de toekomst
     */
    public function testDateTooFarInFuture(): void {
        $futureDate = date('Y-m-d', strtotime('+11 years'));
        
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => [
                            'datum' => $futureDate
                        ]
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('toekomst', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Lege string voor straatnaam
     */
    public function testEmptyStraatnaam(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => '', // Lege string
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('straatnaam', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Verkeerd type voor huisnummer (moet integer of string zijn)
     */
    public function testInvalidHuisnummerType(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => [], // Verkeerd type
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('huisnummer', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Huisnummer als string (moet werken)
     */
    public function testHuisnummerAsString(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => '123', // String is OK
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test: Verkeerd type voor huisnummertoevoeging
     */
    public function testInvalidHuisnummertoevoegingType(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'huisnummertoevoeging' => 456, // Moet string zijn
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('huisnummertoevoeging', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Ongeldige _links structuur
     */
    public function testInvalidLinksStructure(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ],
            '_links' => [
                'self' => 'invalid' // Moet object zijn met href
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('_links', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Lege href in _links
     */
    public function testEmptyHrefInLinks(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam'
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => '' // Lege href
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('href', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Verblijfplaats met alleen woonplaats maar met andere velden (moet werken)
     */
    public function testVerblijfplaatsWithoutWoonplaats(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB'
                        // Geen woonplaatsnaam, maar heeft andere velden
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test: Alle datum velden aanwezig
     */
    public function testAllDatumFieldsPresent(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => ['datum' => '2020-01-01'],
                        'datumIngangGeldigheid' => ['datum' => '2020-01-01'],
                        'datumEindeGeldigheid' => ['datum' => '2021-01-01']
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * Test: Datum object zonder datum veld
     */
    public function testDatumObjectWithoutDatumField(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => [
                    [
                        'straatnaam' => 'Hoofdstraat',
                        'huisnummer' => 123,
                        'postcode' => '1234AB',
                        'woonplaatsnaam' => 'Amsterdam',
                        'datumAanvangAdres' => [
                            'other' => 'value' // Geen 'datum' veld
                        ]
                    ]
                ]
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('datum', implode(' ', $result['errors']));
    }
    
    /**
     * Test: Verblijfplaatshistorie is geen array
     */
    public function testVerblijfplaatshistorieNotArray(): void {
        $response = [
            '_embedded' => [
                'verblijfplaatshistorie' => 'not an array'
            ]
        ];
        
        $result = $this->validator->validateVerblijfplaatshistorieResponse($response);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('array', implode(' ', $result['errors']));
    }
}







