# Plan: Open Registers als Adapter tussen ZGW en StUF Zaken

**Datum:** 2025-01-27  
**Status:** Architectuurplan  
**Doel:** Open Registers inrichten als adapter/gateway tussen ZGW (REST API) en StUF Zaken (SOAP/XML)

---

## Probleemstelling

Je hebt twee applicaties die verschillende protocollen gebruiken:
- **App A:** Praat ZGW (moderne REST API, JSON)
- **App B:** Praat StUF Zaken (ouder SOAP/XML berichtenformaat)

**Vraag:** Hoe moet Open Registers worden ingericht om deze twee te laten communiceren?

---

## Architectuur Overzicht

### Huidige Situatie (Zonder Adapter)

```
┌─────────────┐                    ┌─────────────┐
│   App A     │                    │   App B     │
│  (ZGW)      │                    │ (StUF Zaken)│
│  REST/JSON  │   ❌ Kan niet      │  SOAP/XML   │
└─────────────┘   communiceren     └─────────────┘
```

**Probleem:** Verschillende protocollen, geen directe communicatie mogelijk.

### Gewenste Architectuur (Met Open Registers als Adapter)

```
┌─────────────┐                    ┌──────────────────────────┐
│   App A     │                    │   Open Registers         │
│  (ZGW)      │                    │   (Adapter/Gateway)     │
│  REST/JSON  │ ──────────────────→│                          │
└─────────────┘                    │  ┌────────────────────┐ │
                                   │  │ ZGW Controller      │ │
                                   │  │ (REST endpoints)    │ │
                                   │  └────────────────────┘ │
                                   │           │              │
                                   │           ↓              │
                                   │  ┌────────────────────┐ │
                                   │  │ Transformation      │ │
                                   │  │ Service             │ │
                                   │  │ ZGW ↔ StUF          │ │
                                   │  └────────────────────┘ │
                                   │           │              │
                                   │           ↓              │
                                   │  ┌────────────────────┐ │
                                   │  │ StUF Client         │ │
                                   │  │ (SOAP/XML)          │ │
                                   │  └────────────────────┘ │
                                   └──────────────────────────┘
                                            │
                                            ↓
                                   ┌─────────────┐
                                   │   App B     │
                                   │ (StUF Zaken)│
                                   │  SOAP/XML   │
                                   └─────────────┘
```

---

## Common Ground 5-Lagen Positionering

Volgens het Common Ground model:

| Component | Laag | Verantwoordelijkheid |
|-----------|------|---------------------|
| **App A (ZGW)** | Laag 4: Processen | Zaakgericht werken applicatie |
| **Open Registers Adapter** | Laag 2: Componenten | Protocol transformatie, coördinatie |
| **StUF Transformation Service** | Laag 3: Diensten | Data transformatie ZGW ↔ StUF |
| **App B (StUF Zaken)** | Laag 4: Processen | Legacy zaaksysteem |
| **Database (Zaken)** | Laag 1: Data | Persistente opslag |

---

## Architectuur Componenten

### 1. ZGW Controller (Bestaand ✅)

**Locatie:** `lib/Controller/ZgwZaakController.php`

**Functionaliteit:**
- Ontvangt ZGW REST API requests
- Valideert ZGW formaat
- Roept Transformation Service aan
- Retourneert ZGW-formaat responses

**Endpoints:**
- `GET /apps/openregister/zgw/zaken` - Lijst zaken
- `GET /apps/openregister/zgw/zaken/{zaakId}` - Specifieke zaak
- `POST /apps/openregister/zgw/zaken` - Nieuwe zaak
- `PUT /apps/openregister/zgw/zaken/{zaakId}` - Zaak bijwerken
- `DELETE /apps/openregister/zgw/zaken/{zaakId}` - Zaak verwijderen

### 2. StUF Transformation Service (Nieuw 🔨)

**Locatie:** `lib/Service/Transformation/StufTransformationService.php`

**Functionaliteit:**
- Transformeert ZGW JSON → StUF XML
- Transformeert StUF XML → ZGW JSON
- Mapping tussen ZGW en StUF Zaken velden
- Validatie van StUF berichten

**Methoden:**
```php
class StufTransformationService {
    /**
     * Transformeer ZGW Zaak naar StUF Zaken bericht
     */
    public function transformZgwToStufZaak(array $zgwZaak): string {
        // ZGW JSON → StUF XML
    }
    
    /**
     * Transformeer StUF Zaken bericht naar ZGW Zaak
     */
    public function transformStufToZgwZaak(string $stufXml): array {
        // StUF XML → ZGW JSON
    }
    
    /**
     * Valideer StUF bericht
     */
    public function validateStufBericht(string $stufXml): bool {
        // XSD validatie
    }
}
```

### 3. StUF Client Service (Nieuw 🔨)

**Locatie:** `lib/Service/Stuf/StufClientService.php`

**Functionaliteit:**
- SOAP client voor StUF Zaken communicatie
- Verzendt StUF berichten naar App B
- Ontvangt StUF responses
- Error handling en retry logica

**Methoden:**
```php
class StufClientService {
    /**
     * Verstuur StUF Zaken bericht
     */
    public function sendStufBericht(string $stufXml, string $berichtType): string {
        // SOAP call naar App B
    }
    
    /**
     * Haal zaak op via StUF
     */
    public function getZaak(string $zaakIdentificatie): string {
        // StUF bevragen bericht
    }
    
    /**
     * Maak zaak aan via StUF
     */
    public function createZaak(string $stufXml): string {
        // StUF muteren bericht
    }
}
```

### 4. Data Mapping Configuratie (Nieuw 🔨)

**Locatie:** `config/stuf-zgw-mapping.json`

**Functionaliteit:**
- Mapping tussen ZGW velden en StUF elementen
- Configuratie van StUF endpoint
- XSD schema locaties

**Voorbeeld mapping:**
```json
{
  "zgw_to_stuf": {
    "identificatie": "/zakLv01:zakLv01/zakLv01:object/zakLv01:identificatie",
    "bronorganisatie": "/zakLv01:zakLv01/zakLv01:object/zakLv01:bronorganisatie",
    "zaaktype": "/zakLv01:zakLv01/zakLv01:object/zakLv01:zaaktype",
    "status": "/zakLv01:zakLv01/zakLv01:object/zakLv01:status",
    "omschrijving": "/zakLv01:zakLv01/zakLv01:object/zakLv01:omschrijving"
  },
  "stuf_to_zgw": {
    "/zakLv01:zakLv01/zakLv01:object/zakLv01:identificatie": "identificatie",
    "/zakLv01:zakLv01/zakLv01:object/zakLv01:bronorganisatie": "bronorganisatie",
    "/zakLv01:zakLv01/zakLv01:object/zakLv01:zaaktype": "zaaktype",
    "/zakLv01:zakLv01/zakLv01:object/zakLv01:status": "status",
    "/zakLv01:zakLv01/zakLv01:object/zakLv01:omschrijving": "omschrijving"
  },
  "stuf_endpoint": {
    "url": "https://stuf-zaken.example.com/soap",
    "wsdl": "https://stuf-zaken.example.com/wsdl/Zaken.wsdl",
    "namespace": "http://www.egem.nl/StUF/sector/zkn/0310"
  }
}
```

---

## Berichtenroutes

### Route 1: Zaak Aanmaken (App A → App B)

```
┌─────────────┐
│   App A     │
│  (ZGW)      │
└──────┬──────┘
       │ POST /zgw/zaken
       │ {
       │   "identificatie": "ZAAK-001",
       │   "bronorganisatie": "123456789",
       │   "zaaktype": "https://...",
       │   "status": "https://...",
       │   "omschrijving": "Verhuizing"
       │ }
       ↓
┌─────────────────────────────────────────┐
│  Open Registers (Laag 2)                │
│  ┌──────────────────────────────────┐  │
│  │  ZgwZaakController               │  │
│  │  → createZaak()                  │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Validatie]                 │
│         │   - ZGW formaat validatie     │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  StufTransformationService        │  │
│  │  → transformZgwToStufZaak()      │  │
│  │  ZGW JSON → StUF XML              │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  StufClientService                │  │
│  │  → sendStufBericht()             │  │
│  │  SOAP call naar App B            │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────┐
│   App B     │
│ (StUF Zaken)│
│  SOAP/XML   │
└─────────────┘
         │
         ↓ (StUF Response)
┌─────────────────────────────────────────┐
│  Open Registers                         │
│  ┌──────────────────────────────────┐  │
│  │  StufClientService                │  │
│  │  ← StUF XML Response              │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  StufTransformationService        │  │
│  │  → transformStufToZgwZaak()     │  │
│  │  StUF XML → ZGW JSON             │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  ZgwZaakController               │  │
│  │  → ZGW JSON Response            │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────┐
│   App A     │
│  (ZGW)      │
│ 201 Created │
└─────────────┘
```

### Route 2: Zaak Ophalen (App A → App B)

```
┌─────────────┐
│   App A     │
│  (ZGW)      │
└──────┬──────┘
       │ GET /zgw/zaken/{zaakId}
       ↓
┌─────────────────────────────────────────┐
│  Open Registers                         │
│  ┌──────────────────────────────────┐  │
│  │  ZgwZaakController               │  │
│  │  → getZaak()                     │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  StufClientService                │  │
│  │  → getZaak()                     │  │
│  │  StUF bevragen bericht            │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────┐
│   App B     │
│ (StUF Zaken)│
│  SOAP/XML   │
└─────────────┘
         │
         ↓ (StUF Response)
┌─────────────────────────────────────────┐
│  Open Registers                         │
│  ┌──────────────────────────────────┐  │
│  │  StufTransformationService        │  │
│  │  → transformStufToZgwZaak()     │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  ZgwZaakController               │  │
│  │  → ZGW JSON Response            │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────┐
│   App A     │
│  (ZGW)      │
│ 200 OK      │
└─────────────┘
```

---

## Implementatie Stappen

### Fase 1: StUF Client Service (Week 1)

**Doel:** Basis SOAP client voor StUF communicatie

**Taken:**
1. ✅ Installeer SOAP client library (bijv. `guzzlehttp/guzzle` + `php-soap`)
2. ✅ Maak `StufClientService.php`
3. ✅ Implementeer SOAP client configuratie
4. ✅ Implementeer basis `sendStufBericht()` methode
5. ✅ Test met eenvoudig StUF bericht

**Resultaat:** Basis SOAP communicatie werkt

### Fase 2: StUF Transformation Service (Week 2)

**Doel:** Data transformatie tussen ZGW en StUF

**Taken:**
1. ✅ Maak `StufTransformationService.php`
2. ✅ Implementeer `transformZgwToStufZaak()` (ZGW → StUF)
3. ✅ Implementeer `transformStufToZgwZaak()` (StUF → ZGW)
4. ✅ Maak mapping configuratie (`stuf-zgw-mapping.json`)
5. ✅ Implementeer XML parsing/generatie
6. ✅ Test transformaties met voorbeelddata

**Resultaat:** Data kan worden getransformeerd tussen beide formaten

### Fase 3: Integratie met ZGW Controller (Week 3)

**Doel:** Koppel StUF services aan bestaande ZGW endpoints

**Taken:**
1. ✅ Update `ZgwZaakController.php`
2. ✅ Inject `StufTransformationService` en `StufClientService`
3. ✅ Update `createZaak()` om StUF te gebruiken
4. ✅ Update `getZaak()` om StUF te gebruiken
5. ✅ Update `updateZaak()` om StUF te gebruiken
6. ✅ Update `deleteZaak()` om StUF te gebruiken
7. ✅ Error handling voor StUF fouten
8. ✅ Test volledige flow

**Resultaat:** ZGW endpoints communiceren met StUF systeem

### Fase 4: Validatie & Error Handling (Week 4)

**Doel:** Robuuste error handling en validatie

**Taken:**
1. ✅ Implementeer StUF XSD validatie
2. ✅ Implementeer error mapping (StUF → ZGW error codes)
3. ✅ Implementeer retry logica voor StUF calls
4. ✅ Logging van alle StUF communicatie
5. ✅ Unit tests voor transformaties
6. ✅ Integration tests voor volledige flow

**Resultaat:** Productie-klaar systeem met goede error handling

---

## Technische Details

### StUF Zaken Bericht Structuur

**Voorbeeld StUF Zaken Muteren Bericht:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<zakLv01:zakLv01 xmlns:zakLv01="http://www.egem.nl/StUF/sector/zkn/0310"
                 xmlns:StUF="http://www.egem.nl/StUF/StUF0301"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <StUF:stuurgegevens>
    <StUF:berichtcode>Mv01</StUF:berichtcode>
    <StUF:zender>
      <StUF:organisatie>Open Registers</StUF:organisatie>
    </StUF:zender>
    <StUF:ontvanger>
      <StUF:organisatie>App B</StUF:organisatie>
    </StUF:ontvanger>
    <StUF:referentienummer>REF-12345</StUF:referentienummer>
    <StUF:tijdstipBericht>2025-01-27T10:00:00</StUF:tijdstipBericht>
  </StUF:stuurgegevens>
  <zakLv01:object>
    <zakLv01:identificatie>ZAAK-001</zakLv01:identificatie>
    <zakLv01:bronorganisatie>123456789</zakLv01:bronorganisatie>
    <zakLv01:zaaktype>
      <zakLv01:code>VERHUIZING</zakLv01:code>
    </zakLv01:zaaktype>
    <zakLv01:status>
      <zakLv01:code>IN_BEHANDELING</zakLv01:code>
    </zakLv01:status>
    <zakLv01:omschrijving>Verhuizing</zakLv01:omschrijving>
  </zakLv01:object>
</zakLv01:zakLv01>
```

### ZGW naar StUF Mapping

| ZGW Veld | StUF Element | Opmerking |
|----------|--------------|-----------|
| `identificatie` | `/zakLv01:object/zakLv01:identificatie` | Direct mapping |
| `bronorganisatie` | `/zakLv01:object/zakLv01:bronorganisatie` | Direct mapping |
| `zaaktype` | `/zakLv01:object/zakLv01:zaaktype/zakLv01:code` | URL → code extractie |
| `status` | `/zakLv01:object/zakLv01:status/zakLv01:code` | URL → code extractie |
| `omschrijving` | `/zakLv01:object/zakLv01:omschrijving` | Direct mapping |
| `registratiedatum` | `/zakLv01:object/zakLv01:registratiedatum` | ISO 8601 → StUF datum |
| `startdatum` | `/zakLv01:object/zakLv01:startdatum` | ISO 8601 → StUF datum |

### Error Mapping

| StUF Foutcode | ZGW HTTP Status | ZGW Error Code |
|---------------|----------------|----------------|
| `StUF001` (Syntactische fout) | `400 Bad Request` | `invalid-request` |
| `StUF002` (Semantische fout) | `422 Unprocessable Entity` | `validation-error` |
| `StUF003` (Autorisatiefout) | `403 Forbidden` | `forbidden` |
| `StUF004` (Niet gevonden) | `404 Not Found` | `not-found` |
| `StUF005` (Systeemfout) | `500 Internal Server Error` | `internal-error` |

---

## Configuratie

### Environment Variables

```bash
# StUF Zaken Endpoint
STUF_ZAKEN_ENDPOINT=https://stuf-zaken.example.com/soap
STUF_ZAKEN_WSDL=https://stuf-zaken.example.com/wsdl/Zaken.wsdl
STUF_ZAKEN_NAMESPACE=http://www.egem.nl/StUF/sector/zkn/0310

# StUF Authenticatie
STUF_ZAKEN_USERNAME=openregisters
STUF_ZAKEN_PASSWORD=secret

# Timeout & Retry
STUF_ZAKEN_TIMEOUT=30
STUF_ZAKEN_RETRY_COUNT=3
STUF_ZAKEN_RETRY_DELAY=1000
```

### Mapping Configuratie

**Bestand:** `config/stuf-zgw-mapping.json`

Zie voorbeeld hierboven in sectie "Data Mapping Configuratie".

---

## Security Overwegingen

### 1. Authenticatie & Autorisatie

**StUF Authenticatie:**
- Gebruik WS-Security voor SOAP authenticatie
- Certificaten voor client authenticatie
- Wachtwoord encryptie in configuratie

**ZGW Authenticatie:**
- Bestaande Nextcloud authenticatie
- JWT tokens voor API toegang
- Rechten check per endpoint

### 2. Data Validatie

- ✅ Valideer alle ZGW input (JSON schema)
- ✅ Valideer alle StUF output (XSD schema)
- ✅ Sanitize XML output (prevent XML injection)
- ✅ Escape speciale karakters

### 3. Logging & Audit

- ✅ Log alle StUF communicatie (request/response)
- ✅ Log transformaties (voor debugging)
- ✅ Log errors met volledige context
- ✅ Audit trail voor alle zaak mutaties

### 4. Error Handling

- ✅ Geef geen interne details door aan client
- ✅ Map StUF errors naar ZGW error codes
- ✅ Implementeer retry logica voor transient errors
- ✅ Dead letter queue voor failed messages

---

## Testing Strategie

### Unit Tests

```php
// tests/Unit/Service/Transformation/StufTransformationServiceTest.php
class StufTransformationServiceTest extends TestCase {
    public function testTransformZgwToStufZaak() {
        // Test ZGW → StUF transformatie
    }
    
    public function testTransformStufToZgwZaak() {
        // Test StUF → ZGW transformatie
    }
    
    public function testValidateStufBericht() {
        // Test XSD validatie
    }
}
```

### Integration Tests

```php
// tests/Integration/ZgwStufIntegrationTest.php
class ZgwStufIntegrationTest extends TestCase {
    public function testCreateZaakViaStuf() {
        // Test volledige flow: ZGW request → StUF call → ZGW response
    }
    
    public function testGetZaakViaStuf() {
        // Test volledige flow: ZGW GET → StUF bevragen → ZGW response
    }
}
```

### Mock StUF Server

Voor testing zonder echte StUF server:
- Mock SOAP server (bijv. met `phpunit/phpunit` mocks)
- StUF response templates
- Test data sets

---

## Monitoring & Observability

### Metrics

- Aantal StUF calls per minuut
- StUF response tijd (latency)
- StUF error rate
- Transformatie tijd
- Failed transformations

### Logging

- Alle StUF requests/responses (debug level)
- Transformatie details (info level)
- Errors met stack traces (error level)

### Alerts

- StUF endpoint niet bereikbaar
- Hoge error rate (> 5%)
- Hoge latency (> 2 seconden)
- Failed transformations

---

## Risico's & Mitigaties

### Risico 1: StUF Endpoint Beschikbaarheid

**Risico:** StUF server is niet beschikbaar  
**Impact:** Alle zaak operaties falen  
**Mitigatie:**
- Implementeer retry logica
- Cache responses waar mogelijk
- Fallback naar lokale database
- Health check monitoring

### Risico 2: Data Mapping Complexiteit

**Risico:** Complexe mapping tussen ZGW en StUF  
**Impact:** Data verlies of incorrecte transformatie  
**Mitigatie:**
- Uitgebreide unit tests voor transformaties
- Validatie van beide kanten (ZGW en StUF)
- Logging van alle transformaties
- Handmatige review van mapping configuratie

### Risico 3: Performance

**Risico:** SOAP calls zijn traag  
**Impact:** Slechte gebruikerservaring  
**Mitigatie:**
- Caching van veelgebruikte data
- Asynchrone verwerking waar mogelijk
- Connection pooling
- Timeout configuratie

### Risico 4: StUF Versie Wijzigingen

**Risico:** StUF schema wijzigt  
**Impact:** Transformatie faalt  
**Mitigatie:**
- Versie management in mapping configuratie
- XSD validatie voor elke versie
- Automatische tests voor schema compatibiliteit
- Documentatie van ondersteunde StUF versies

---

## Alternatieve Architecturen

### Optie A: Directe Database Koppeling (Niet Aanbevolen)

```
App A (ZGW) → Open Registers → Database ← App B (StUF)
```

**Nadelen:**
- ❌ App B moet database direct benaderen
- ❌ Geen protocol transformatie
- ❌ Schendt Common Ground principes
- ❌ Tight coupling

### Optie B: Message Queue tussen Systemen (Complex)

```
App A (ZGW) → Open Registers → Message Queue → App B (StUF)
```

**Voordelen:**
- ✅ Asynchrone verwerking
- ✅ Decoupling
- ✅ Retry mechanisme

**Nadelen:**
- ❌ Extra infrastructuur nodig
- ❌ Complexer te implementeren
- ❌ Eventual consistency

### Optie C: Open Registers als Adapter (Aanbevolen ✅)

```
App A (ZGW) → Open Registers (Adapter) → App B (StUF)
```

**Voordelen:**
- ✅ Eenvoudige architectuur
- ✅ Protocol transformatie op één plek
- ✅ Common Ground compliant
- ✅ Eenvoudig te onderhouden

**Nadelen:**
- ⚠️ Open Registers moet beide protocollen ondersteunen

---

## Conclusie & Aanbeveling

**Aanbevolen Architectuur:** Open Registers als adapter tussen ZGW en StUF Zaken

**Redenen:**
1. ✅ Volgt Common Ground principes (Laag 2 = Componenten)
2. ✅ Eén centrale plek voor protocol transformatie
3. ✅ Herbruikbaar voor andere StUF systemen
4. ✅ Eenvoudig te onderhouden en testen
5. ✅ Bestaande ZGW implementatie kan worden uitgebreid

**Implementatie Tijd:** 4 weken

**Prioriteit:** 🔴 Hoog (als beide systemen moeten communiceren)

---

**Status:** ✅ Plan compleet, klaar voor implementatie  
**Volgende Stap:** Beslissen over implementatie en starten met Fase 1 (StUF Client Service)

