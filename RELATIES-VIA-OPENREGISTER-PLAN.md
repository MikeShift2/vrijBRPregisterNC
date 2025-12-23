# Plan: Relaties beschikbaar maken via Open Register

## Huidige Situatie

### Wat werkt nu:
- ✅ Haal Centraal API endpoints voor relaties (partners, kinderen, ouders, verblijfplaats, nationaliteiten)
- ✅ Endpoints halen data direct uit PostgreSQL (`probev` database)
- ✅ Data wordt getransformeerd naar Haal Centraal formaat

### Wat ontbreekt:
- ⚠️ Relaties zijn **niet** opgeslagen in Open Register
- ⚠️ Relaties worden **direct** uit PostgreSQL gehaald, niet via Open Register API
- ⚠️ Geen historie/versiebeheer voor relaties
- ⚠️ Geen eventing voor relatie-mutaties

## Doelstelling

Relaties (partners, kinderen, ouders, verblijfplaats, nationaliteiten) beschikbaar maken via:
1. **Open Register API** - zodat andere applicaties relaties kunnen ophalen
2. **Haal Centraal API** - blijft werken zoals nu, maar haalt data uit Open Register

## Architectuur Opties

### Optie 1: Embedded Objects in Personen Schema (Aanbevolen)

**Concept:** Relaties worden opgeslagen als embedded objects in het Personen object in Open Register.

**Voordelen:**
- ✅ Eenvoudig te implementeren
- ✅ Relaties zijn altijd beschikbaar bij het ophalen van een persoon
- ✅ Geen extra registers/schemas nodig
- ✅ Past bij Haal Centraal API structuur (embedded objects)

**Nadelen:**
- ⚠️ Relaties worden gedupliceerd (in PostgreSQL en Open Register)
- ⚠️ Mutaties moeten op beide plekken worden bijgewerkt

**Implementatie:**
```json
{
  "bsn": "168149291",
  "voornamen": "Janne Malu...",
  "geslachtsnaam": "Naiima Isman Adan",
  "_embedded": {
    "partners": [
      {
        "bsn": "164287061",
        "naam": {...},
        "geboorte": {...}
      }
    ],
    "kinderen": [...],
    "ouders": [...],
    "nationaliteiten": [...]
  },
  "verblijfplaats": {...}
}
```

### Optie 2: Aparte Registers voor Relaties

**Concept:** Elke relatie-type krijgt een eigen register en schema.

**Voordelen:**
- ✅ Volledige scheiding van concerns
- ✅ Elke relatie heeft eigen historie/versiebeheer
- ✅ Betere datagovernance

**Nadelen:**
- ⚠️ Complexer om te implementeren
- ⚠️ Meerdere API calls nodig om alle relaties op te halen
- ⚠️ Meer registers/schemas om te beheren

**Implementatie:**
- Register "Partners" (schema: Partner)
- Register "Kinderen" (schema: Kind)
- Register "Ouders" (schema: Ouder)
- Register "Nationaliteiten" (schema: Nationaliteit)
- Register "Verblijfplaatsen" (schema: Verblijfplaats) - bestaat al (ID 7)

### Optie 3: Hybrid Approach (Aanbevolen voor Productie)

**Concept:** 
- Relaties worden opgeslagen als **references** in Open Register
- Haal Centraal API endpoints resolven deze references naar volledige objecten
- Data blijft in PostgreSQL (single source of truth)

**Voordelen:**
- ✅ Geen data duplicatie
- ✅ Single source of truth (PostgreSQL)
- ✅ Open Register fungeert als API-laag
- ✅ Volledige historie/versiebeheer mogelijk

**Nadelen:**
- ⚠️ Complexer om te implementeren
- ⚠️ Vereist reference resolution logic

**Implementatie:**
```json
{
  "bsn": "168149291",
  "_links": {
    "partners": ["164287061"],
    "kinderen": ["382651765"],
    "ouders": ["73218832", "73218327"],
    "nationaliteiten": [{"code": "1"}],
    "verblijfplaats": "100720432"
  }
}
```

## Aanbevolen Implementatie Strategie

### Fase 1: Embedded Objects (Quick Win)

**Doel:** Relaties beschikbaar maken via Open Register zonder grote architectuurwijzigingen.

**Stappen:**
1. Update `transformToHaalCentraal()` om relaties op te halen
2. Voeg relaties toe als embedded objects in het Personen object
3. Update Open Register object bij elke mutatie

**Code wijzigingen:**
- `HaalCentraalBrpController::transformToHaalCentraal()` - voeg `_embedded` sectie toe
- Import script - voeg relaties toe bij import van personen
- Mutatie handler - update relaties bij wijzigingen

### Fase 2: Reference-based (Productie)

**Doel:** Volledige Common Ground-compliant implementatie met references.

**Stappen:**
1. Voeg `_links` sectie toe aan Personen schema
2. Implementeer reference resolution in Haal Centraal endpoints
3. Update mutatie handlers om references bij te werken

## Concrete Implementatie Stappen

### Stap 1: Update Personen Schema

Voeg relatie-velden toe aan Schema ID 6 (Personen):

```json
{
  "properties": {
    "bsn": {...},
    "voornamen": {...},
    ...
    "_embedded": {
      "type": "object",
      "description": "Embedded relaties volgens Haal Centraal specificatie",
      "properties": {
        "partners": {
          "type": "array",
          "items": {"$ref": "#/definitions/IngeschrevenPersoon"}
        },
        "kinderen": {...},
        "ouders": {...},
        "nationaliteiten": {...}
      }
    },
    "verblijfplaats": {
      "type": "object",
      "properties": {
        "straatnaam": {...},
        "huisnummer": {...},
        ...
      }
    }
  }
}
```

### Stap 2: Update Import Script

Wijzig `import-personen-to-openregister.php` om relaties op te halen:

```php
function getPersonWithRelations($bsn) {
    $persoon = getPersonFromPostgres($bsn);
    
    // Haal relaties op
    $plId = $persoon['pl_id'];
    $persoon['_embedded'] = [
        'partners' => getPartnersForPlId($plId),
        'kinderen' => getKinderenForPlId($plId),
        'ouders' => getOudersForPlId($plId),
        'nationaliteiten' => getNationaliteitenForPlId($plId)
    ];
    $persoon['verblijfplaats'] = getVerblijfplaatsForPlId($plId);
    
    return $persoon;
}
```

### Stap 3: Update Haal Centraal Controller

Wijzig `HaalCentraalBrpController` om relaties uit Open Register te halen:

```php
public function getPartners(string $burgerservicenummer): JSONResponse {
    // Haal persoon op uit Open Register
    $persoon = $this->getPersonByBsnFromDatabase($burgerservicenummer);
    
    // Haal partners uit embedded object
    $partners = $persoon['data'][0]['object']['_embedded']['partners'] ?? [];
    
    // Fallback naar PostgreSQL als niet in Open Register
    if (empty($partners)) {
        $partners = $this->getPartnersFromPostgres(...);
    }
    
    return new JSONResponse(['_embedded' => ['partners' => $partners]]);
}
```

### Stap 4: Update Mutatie Handlers

Bij elke mutatie in PostgreSQL:
1. Detecteer welke relaties zijn gewijzigd
2. Update het corresponderende Open Register object
3. Genereer event voor Open Register eventing systeem

## Voordelen van deze Aanpak

1. **Interoperabiliteit:** Relaties beschikbaar via gestandaardiseerde Open Register API
2. **Historie:** Volledige versiegeschiedenis van relaties
3. **Eventing:** Automatische notificaties bij relatie-mutaties
4. **Performance:** Relaties kunnen worden gecached in Open Register
5. **Common Ground:** Voldoet aan Common Ground principes

## Volgende Stappen

1. ✅ Beslissen welke optie te implementeren (Aanbevolen: Optie 1 voor quick win, Optie 3 voor productie)
2. ⏳ Update Personen schema met relatie-velden
3. ⏳ Update import script om relaties mee te nemen
4. ⏳ Update Haal Centraal controller om uit Open Register te halen
5. ⏳ Implementeer mutatie handlers voor relaties
6. ⏳ Test met bestaande testpersonen







