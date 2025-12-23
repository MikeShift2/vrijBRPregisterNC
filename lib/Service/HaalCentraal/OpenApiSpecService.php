<?php
/**
 * OpenAPI Specification Service
 * 
 * Genereert en beheert OpenAPI 3.0 specificatie voor Haal Centraal BRP Bevragen API
 */

namespace OCA\OpenRegister\Service\HaalCentraal;

class OpenApiSpecService {
    
    /**
     * Genereer OpenAPI 3.0 specificatie
     */
    public function generateSpec(): array {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Haal Centraal BRP Bevragen API',
                'version' => '2.0.0',
                'description' => 'API voor het bevragen van BRP gegevens volgens Haal Centraal specificatie',
                'contact' => [
                    'name' => 'Open Register',
                    'url' => 'https://github.com/openregister'
                ]
            ],
            'servers' => [
                [
                    'url' => '/apps/openregister',
                    'description' => 'Open Register API'
                ]
            ],
            'paths' => $this->getPaths(),
            'components' => [
                'schemas' => $this->getSchemas(),
                'responses' => $this->getResponses(),
                'parameters' => $this->getParameters()
            ],
            'tags' => [
                [
                    'name' => 'IngeschrevenPersonen',
                    'description' => 'Endpoints voor ingeschreven personen'
                ]
            ]
        ];
    }
    
    /**
     * Genereer paths voor alle endpoints
     */
    private function getPaths(): array {
        return [
            '/ingeschrevenpersonen' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Lijst ingeschreven personen',
                    'description' => 'Haal een lijst op van ingeschreven personen met filtering en paginatie',
                    'operationId' => 'getIngeschrevenPersonen',
                    'parameters' => [
                        ['$ref' => '#/components/parameters/fields'],
                        ['$ref' => '#/components/parameters/expand'],
                        ['$ref' => '#/components/parameters/sort'],
                        ['$ref' => '#/components/parameters/bsn'],
                        ['$ref' => '#/components/parameters/achternaam'],
                        ['$ref' => '#/components/parameters/geboortedatum'],
                        ['$ref' => '#/components/parameters/geboortedatumVan'],
                        ['$ref' => '#/components/parameters/geboortedatumTot'],
                        ['$ref' => '#/components/parameters/page'],
                        ['$ref' => '#/components/parameters/pageSize']
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/IngeschrevenPersonenResponse'
                                    ]
                                ]
                            ]
                        ],
                        '400' => ['$ref' => '#/components/responses/BadRequest'],
                        '422' => ['$ref' => '#/components/responses/UnprocessableEntity'],
                        '500' => ['$ref' => '#/components/responses/InternalServerError']
                    ]
                ]
            ],
            '/ingeschrevenpersonen/{burgerservicenummer}' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Haal specifieke persoon op',
                    'description' => 'Haal een specifieke ingeschreven persoon op op basis van BSN',
                    'operationId' => 'getIngeschrevenPersoon',
                    'parameters' => [
                        [
                            'name' => 'burgerservicenummer',
                            'in' => 'path',
                            'required' => true,
                            'description' => 'Het burgerservicenummer (BSN) van de persoon',
                            'schema' => [
                                'type' => 'string',
                                'pattern' => '^[0-9]{9}$'
                            ]
                        ],
                        ['$ref' => '#/components/parameters/fields'],
                        ['$ref' => '#/components/parameters/expand']
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/IngeschrevenPersoon'
                                    ]
                                ]
                            ]
                        ],
                        '400' => ['$ref' => '#/components/responses/BadRequest'],
                        '404' => ['$ref' => '#/components/responses/NotFound'],
                        '500' => ['$ref' => '#/components/responses/InternalServerError']
                    ]
                ]
            ],
            '/ingeschrevenpersonen/{burgerservicenummer}/partners' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Haal partners op',
                    'description' => 'Haal partners op van een ingeschreven persoon',
                    'operationId' => 'getPartners',
                    'parameters' => [
                        [
                            'name' => 'burgerservicenummer',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string', 'pattern' => '^[0-9]{9}$']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/PartnersResponse'
                                    ]
                                ]
                            ]
                        ],
                        '400' => ['$ref' => '#/components/responses/BadRequest'],
                        '404' => ['$ref' => '#/components/responses/NotFound']
                    ]
                ]
            ],
            '/ingeschrevenpersonen/{burgerservicenummer}/kinderen' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Haal kinderen op',
                    'operationId' => 'getKinderen',
                    'parameters' => [
                        [
                            'name' => 'burgerservicenummer',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string', 'pattern' => '^[0-9]{9}$']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/KinderenResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/ingeschrevenpersonen/{burgerservicenummer}/ouders' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Haal ouders op',
                    'operationId' => 'getOuders',
                    'parameters' => [
                        [
                            'name' => 'burgerservicenummer',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string', 'pattern' => '^[0-9]{9}$']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/OudersResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '/ingeschrevenpersonen/{burgerservicenummer}/verblijfplaats' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Haal verblijfplaats op',
                    'operationId' => 'getVerblijfplaats',
                    'parameters' => [
                        [
                            'name' => 'burgerservicenummer',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string', 'pattern' => '^[0-9]{9}$']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/Verblijfplaats'
                                    ]
                                ]
                            ]
                        ],
                        '404' => ['$ref' => '#/components/responses/NotFound']
                    ]
                ]
            ],
            '/ingeschrevenpersonen/{burgerservicenummer}/nationaliteiten' => [
                'get' => [
                    'tags' => ['IngeschrevenPersonen'],
                    'summary' => 'Haal nationaliteiten op',
                    'operationId' => 'getNationaliteiten',
                    'parameters' => [
                        [
                            'name' => 'burgerservicenummer',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'string', 'pattern' => '^[0-9]{9}$']
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Succesvolle response',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/NationaliteitenResponse'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Genereer schemas
     */
    private function getSchemas(): array {
        return [
            'IngeschrevenPersoon' => [
                'type' => 'object',
                'required' => ['burgerservicenummer'],
                'properties' => [
                    'burgerservicenummer' => [
                        'type' => 'string',
                        'pattern' => '^[0-9]{9}$',
                        'description' => 'Het burgerservicenummer (BSN)'
                    ],
                    'naam' => [
                        '$ref' => '#/components/schemas/Naam'
                    ],
                    'geboorte' => [
                        '$ref' => '#/components/schemas/Geboorte'
                    ],
                    'geslachtsaanduiding' => [
                        'type' => 'string',
                        'enum' => ['man', 'vrouw', 'onbekend'],
                        'nullable' => true
                    ],
                    'aNummer' => [
                        'type' => 'string',
                        'nullable' => true
                    ],
                    'verblijfplaats' => [
                        '$ref' => '#/components/schemas/Verblijfplaats'
                    ],
                    '_links' => [
                        '$ref' => '#/components/schemas/Links'
                    ],
                    '_embedded' => [
                        'type' => 'object',
                        'properties' => [
                            'partners' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ],
                            'kinderen' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ],
                            'ouders' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ],
                            'verblijfplaats' => [
                                '$ref' => '#/components/schemas/Verblijfplaats'
                            ],
                            'nationaliteiten' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Nationaliteit']
                            ]
                        ]
                    ]
                ]
            ],
            'Naam' => [
                'type' => 'object',
                'properties' => [
                    'voornamen' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'geslachtsnaam' => [
                        'type' => 'string',
                        'nullable' => true
                    ],
                    'voorvoegsel' => [
                        'type' => 'string',
                        'nullable' => true
                    ]
                ]
            ],
            'Geboorte' => [
                'type' => 'object',
                'properties' => [
                    'datum' => [
                        'type' => 'object',
                        'properties' => [
                            'datum' => [
                                'type' => 'string',
                                'format' => 'date',
                                'pattern' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                            ]
                        ]
                    ]
                ]
            ],
            'Verblijfplaats' => [
                'type' => 'object',
                'properties' => [
                    'straatnaam' => ['type' => 'string', 'nullable' => true],
                    'huisnummer' => ['type' => ['integer', 'string'], 'nullable' => true],
                    'huisnummertoevoeging' => ['type' => 'string', 'nullable' => true],
                    'postcode' => [
                        'type' => 'string',
                        'pattern' => '^[0-9]{4}[A-Z]{2}$',
                        'nullable' => true
                    ],
                    'woonplaatsnaam' => ['type' => 'string', 'nullable' => true]
                ]
            ],
            'Nationaliteit' => [
                'type' => 'object',
                'properties' => [
                    'nationaliteit' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'omschrijving' => ['type' => 'string', 'nullable' => true]
                        ]
                    ]
                ]
            ],
            'Links' => [
                'type' => 'object',
                'properties' => [
                    'self' => [
                        'type' => 'object',
                        'properties' => [
                            'href' => ['type' => 'string']
                        ]
                    ]
                ]
            ],
            'IngeschrevenPersonenResponse' => [
                'type' => 'object',
                'properties' => [
                    '_embedded' => [
                        'type' => 'object',
                        'properties' => [
                            'ingeschrevenpersonen' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ]
                        ]
                    ],
                    '_links' => ['$ref' => '#/components/schemas/Links'],
                    'page' => [
                        'type' => 'object',
                        'properties' => [
                            'number' => ['type' => 'integer'],
                            'size' => ['type' => 'integer'],
                            'totalElements' => ['type' => 'integer'],
                            'totalPages' => ['type' => 'integer']
                        ]
                    ]
                ]
            ],
            'PartnersResponse' => [
                'type' => 'object',
                'properties' => [
                    '_embedded' => [
                        'type' => 'object',
                        'properties' => [
                            'partners' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ]
                        ]
                    ]
                ]
            ],
            'KinderenResponse' => [
                'type' => 'object',
                'properties' => [
                    '_embedded' => [
                        'type' => 'object',
                        'properties' => [
                            'kinderen' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ]
                        ]
                    ]
                ]
            ],
            'OudersResponse' => [
                'type' => 'object',
                'properties' => [
                    '_embedded' => [
                        'type' => 'object',
                        'properties' => [
                            'ouders' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/IngeschrevenPersoon']
                            ]
                        ]
                    ]
                ]
            ],
            'NationaliteitenResponse' => [
                'type' => 'object',
                'properties' => [
                    '_embedded' => [
                        'type' => 'object',
                        'properties' => [
                            'nationaliteiten' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Nationaliteit']
                            ]
                        ]
                    ]
                ]
            ],
            'Error' => [
                'type' => 'object',
                'required' => ['status', 'title', 'detail'],
                'properties' => [
                    'status' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'detail' => ['type' => 'string'],
                    'instance' => ['type' => 'string', 'nullable' => true],
                    'errors' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'field' => ['type' => 'string'],
                                'message' => ['type' => 'string'],
                                'code' => ['type' => 'string', 'nullable' => true]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Genereer parameters
     */
    private function getParameters(): array {
        return [
            'fields' => [
                'name' => 'fields',
                'in' => 'query',
                'description' => 'Selecteer specifieke velden (comma-separated). Bijv: burgerservicenummer,naam,geboorte',
                'required' => false,
                'schema' => ['type' => 'string']
            ],
            'expand' => [
                'name' => 'expand',
                'in' => 'query',
                'description' => 'Haal relaties automatisch op (comma-separated). Mogelijke waarden: partners, kinderen, ouders, verblijfplaats, nationaliteiten, of * voor alle relaties',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'enum' => ['partners', 'kinderen', 'ouders', 'verblijfplaats', 'nationaliteiten', '*']
                ]
            ],
            'sort' => [
                'name' => 'sort',
                'in' => 'query',
                'description' => 'Sorteer resultaten (comma-separated). Prefix met - voor descending. Bijv: -naam.geslachtsnaam,geboorte.datum.datum',
                'required' => false,
                'schema' => ['type' => 'string']
            ],
            'bsn' => [
                'name' => 'bsn',
                'in' => 'query',
                'description' => 'Filter op burgerservicenummer (BSN)',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'pattern' => '^[0-9]{9}$'
                ]
            ],
            'achternaam' => [
                'name' => 'achternaam',
                'in' => 'query',
                'description' => 'Filter op achternaam (case-insensitive, partial match)',
                'required' => false,
                'schema' => ['type' => 'string']
            ],
            'geboortedatum' => [
                'name' => 'geboortedatum',
                'in' => 'query',
                'description' => 'Filter op geboortedatum (partial match)',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'date'
                ]
            ],
            'geboortedatumVan' => [
                'name' => 'geboortedatumVan',
                'in' => 'query',
                'description' => 'Filter vanaf geboortedatum (inclusief)',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'date'
                ]
            ],
            'geboortedatumTot' => [
                'name' => 'geboortedatumTot',
                'in' => 'query',
                'description' => 'Filter tot geboortedatum (inclusief)',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'date'
                ]
            ],
            'page' => [
                'name' => '_page',
                'in' => 'query',
                'description' => 'Paginanummer (1-based)',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'default' => 1
                ]
            ],
            'pageSize' => [
                'name' => '_limit',
                'in' => 'query',
                'description' => 'Aantal resultaten per pagina',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 20
                ]
            ]
        ];
    }
    
    /**
     * Genereer responses
     */
    private function getResponses(): array {
        return [
            'BadRequest' => [
                'description' => 'Bad Request - Ongeldige parameters',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'NotFound' => [
                'description' => 'Not Found - Resource niet gevonden',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'UnprocessableEntity' => [
                'description' => 'Unprocessable Entity - Validatie fout',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ],
            'InternalServerError' => [
                'description' => 'Internal Server Error',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error']
                    ]
                ]
            ]
        ];
    }
}







