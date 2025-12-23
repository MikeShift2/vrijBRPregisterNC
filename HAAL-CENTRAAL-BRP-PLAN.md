# Plan: Haal Centraal BRP Bevragen Service op OpenRegister

## Overzicht

Dit plan beschrijft hoe je een **Haal Centraal BRP Bevragen**-compatibele API service kunt bouwen bovenop het OpenRegister "Personen" schema.

## Haal Centraal BRP Bevragen API

De [Haal Centraal BRP Bevragen API](https://github.com/BRP-API/Haal-Centraal-BRP-bevragen) is een gestandaardiseerde REST API voor het bevragen van BRP gegevens volgens Nederlandse overheid standaarden.

### Belangrijkste Endpoints

1. **GET /ingeschrevenpersonen** - Lijst ingeschreven personen
2. **GET /ingeschrevenpersonen/{burgerservicenummer}** - Specifieke persoon op BSN
3. **GET /ingeschrevenpersonen/{burgerservicenummer}/partners** - Partners van persoon
4. **GET /ingeschrevenpersonen/{burgerservicenummer}/kinderen** - Kinderen van persoon
5. **GET /ingeschrevenpersonen/{burgerservicenummer}/ouders** - Ouders van persoon

## Implementatie Strategie

### Optie 1: Custom Controller

Maak een nieuwe controller die de Haal Centraal BRP Bevragen endpoints implementeert en deze mapt naar OpenRegister objecten.

**Voordelen:**
- ✅ Volledige controle over de API structuur
- ✅ Kan exact de Haal Centraal specificatie volgen
- ✅ Kan data transformeren van OpenRegister formaat naar Haal Centraal formaat
- ✅ Kan caching en optimalisatie toevoegen

**Nadelen:**
- ⚠️ Vereist custom code ontwikkeling
- ⚠️ Moet onderhouden worden bij OpenRegister updates

### Optie 2: API Gateway/Proxy

Bouw een aparte API service (bijv. Node.js/Python) die:
- De Haal Centraal BRP Bevragen endpoints exposeert
- Data ophaalt uit OpenRegister via de REST API
- Transformeert naar Haal Centraal formaat

**Voordelen:**
- ✅ Gescheiden van OpenRegister codebase
- ✅ Kan in andere taal geschreven worden
- ✅ Makkelijker te deployen en schalen

**Nadelen:**
- ⚠️ Extra service om te onderhouden
- ⚠️ Extra network hop

## Data Mapping

### OpenRegister → Haal Centraal BRP Bevragen

| OpenRegister Veld | Haal Centraal Veld | Opmerking |
|-------------------|-------------------|-----------|
| `bsn` | `burgerservicenummer` | Direct mapping |
| `voornamen` | `naam.voornamen` | Array van voornamen |
| `geslachtsnaam` | `naam.geslachtsnaam` | Direct mapping |
| `voorvoegsel` | `naam.voorvoegsel` | Direct mapping |
| `geboortedatum` | `geboorte.datum.datum` | Formaat conversie nodig |
| `geslacht` | `geslachtsaanduiding` | Code mapping (V→vrouw, M→man) |
| `anr` | `aNummer` | Direct mapping |

### Vereiste Transformaties

1. **Datum formaat**: `19820308` (JJJJMMDD) → ISO 8601 `1982-03-08`
2. **Geslacht codes**: `V` → `vrouw`, `M` → `man`, `O` → `onbekend`
3. **Naam structuur**: Flat velden → Geneste structuur met `naam.voornamen[]`, `naam.geslachtsnaam`, etc.
4. **BSN validatie**: Haal Centraal vereist 9-cijferig BSN

## Implementatie Stappen

### Stap 1: Custom Controller Aanmaken

Maak een nieuwe controller: `HaalCentraalBrpController.php`

```php
<?php
namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class HaalCentraalBrpController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request,
        private ObjectService $objectService,
        private SchemaMapper $schemaMapper
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /ingeschrevenpersonen
     * Haal Centraal BRP Bevragen endpoint
     */
    public function getIngeschrevenPersonen(): JSONResponse {
        // Haal personen op uit OpenRegister
        // Transformeer naar Haal Centraal formaat
        // Return JSON response
    }
    
    /**
     * GET /ingeschrevenpersonen/{bsn}
     */
    public function getIngeschrevenPersoon(string $bsn): JSONResponse {
        // Zoek persoon op BSN
        // Transformeer naar Haal Centraal formaat
        // Return JSON response
    }
}
```

### Stap 2: Routes Registreren

Voeg routes toe aan `appinfo/routes.php`:

```php
return [
    'routes' => [
        // Haal Centraal BRP Bevragen endpoints
        ['name' => 'HaalCentraalBrp#getIngeschrevenPersonen', 'url' => '/ingeschrevenpersonen', 'verb' => 'GET'],
        ['name' => 'HaalCentraalBrp#getIngeschrevenPersoon', 'url' => '/ingeschrevenpersonen/{bsn}', 'verb' => 'GET'],
    ]
];
```

### Stap 3: Data Transformatie Service

Maak een service die OpenRegister data transformeert naar Haal Centraal formaat:

```php
class HaalCentraalTransformService {
    public function transformPersoon(array $openRegisterObject): array {
        return [
            'burgerservicenummer' => $openRegisterObject['bsn'],
            'naam' => [
                'voornamen' => explode(' ', $openRegisterObject['voornamen'] ?? ''),
                'geslachtsnaam' => $openRegisterObject['geslachtsnaam'] ?? '',
                'voorvoegsel' => $openRegisterObject['voorvoegsel'] ?? null,
            ],
            'geboorte' => [
                'datum' => [
                    'datum' => $this->formatDatum($openRegisterObject['geboortedatum'] ?? null),
                ],
            ],
            'geslachtsaanduiding' => $this->mapGeslacht($openRegisterObject['geslacht'] ?? null),
            'aNummer' => $openRegisterObject['anr'] ?? null,
        ];
    }
    
    private function formatDatum(?string $datum): ?string {
        if (!$datum || strlen($datum) !== 8) return null;
        return substr($datum, 0, 4) . '-' . substr($datum, 4, 2) . '-' . substr($datum, 6, 2);
    }
    
    private function mapGeslacht(?string $geslacht): ?string {
        return match($geslacht) {
            'V' => 'vrouw',
            'M' => 'man',
            'O' => 'onbekend',
            default => null,
        };
    }
}
```

## Volgende Stappen

1. **Bekijk Haal Centraal specificatie**: Download de OpenAPI spec van https://github.com/BRP-API/Haal-Centraal-BRP-bevragen
2. **Implementeer basis endpoints**: Start met `GET /ingeschrevenpersonen/{bsn}`
3. **Test tegen Haal Centraal test suite**: Gebruik de Cucumber tests uit de repo
4. **Voeg authenticatie toe**: Haal Centraal gebruikt API keys
5. **Implementeer filtering**: Query parameters zoals `fields`, `expand`, etc.

## Referenties

- [Haal Centraal BRP Bevragen GitHub](https://github.com/BRP-API/Haal-Centraal-BRP-bevragen)
- [Haal Centraal Documentatie](https://brp-api.github.io/Haal-Centraal-BRP-bevragen/)
- [OpenRegister API Documentatie](./OPENREGISTER-API-GUIDE.md)








