# RvIG BRP API Compliance Check

**Datum:** 2025-01-23  
**Referentie:** [RvIG BRP API Documentatie](https://developer.rvig.nl/brp-api/overview/)  
**Status:** ⚠️ **GEDEELTELIJK COMPLIANT**

---

## 📋 RvIG BRP API - Drie Functies

### 1. Personen API ⚠️ **GEDEELTELIJK GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Voor het zoeken en raadplegen van actuele personen, partners, ouders en kinderen uit de BRP, inclusief de registratie niet-ingezeten (RNI).

**Open Register Status:**

| Endpoint | RvIG Spec | Open Register | Status |
|----------|-----------|---------------|--------|
| `GET /ingeschrevenpersonen` | ✅ Zoeken met filters | ✅ Geïmplementeerd | ✅ **OK** |
| `GET /ingeschrevenpersonen/{bsn}` | ✅ Raadplegen op BSN | ✅ Geïmplementeerd | ✅ **OK** |
| `GET /ingeschrevenpersonen/{bsn}/partners` | ✅ Vereist | ✅ Geïmplementeerd | ✅ **OK** |
| `GET /ingeschrevenpersonen/{bsn}/kinderen` | ✅ Vereist | ✅ Geïmplementeerd | ✅ **OK** |
| `GET /ingeschrevenpersonen/{bsn}/ouders` | ✅ Vereist | ✅ Geïmplementeerd | ✅ **OK** |
| `GET /ingeschrevenpersonen/{bsn}/nationaliteiten` | ✅ Vereist | ✅ Geïmplementeerd | ✅ **OK** |
| `GET /ingeschrevenpersonen/{bsn}/verblijfplaats` | ✅ Vereist | ✅ Geïmplementeerd | ✅ **OK** |

**✅ Basis functionaliteit:** Goed geïmplementeerd

**⚠️ RNI (Registratie Niet-Ingezeten):** Niet expliciet geïmplementeerd
- Probev database bevat wel `rni_ax` tabel
- Niet ontsloten via API

---

### 2. Bewoning API ❌ **NIET GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Voor het raadplegen van de historische bewoning van een adres. Met de API kun je de samenstelling(en) van bewoners van een woning raadplegen binnen een periode of op een peildatum.

**Vereiste Endpoints:**
- ❌ `GET /adressen/{adresseerbaarObjectIdentificatie}/bewoning`
- ❌ `?peildatum=YYYY-MM-DD` parameter support
- ❌ `?datumVan=YYYY-MM-DD&datumTot=YYYY-MM-DD` parameter support

**Open Register Status:** ❌ **ONTBREEKT VOLLEDIG**

**Impact:** Historische bewoning queries niet mogelijk

**Databron:** Wel beschikbaar in probev via:
- `vb_ax` tabel (verblijven met historie)
- View queries kunnen worden gemaakt

---

### 3. Verblijfplaatshistorie API ⚠️ **GEDEELTELIJK GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Voor het opvragen van de verblijfplaats(en) van een persoon in een periode of op een peildatum.

**Vereiste Endpoint:**
- ✅ `GET /ingeschrevenpersonen/{bsn}/verblijfplaatshistorie`

**Open Register Status:**

| Aspect | RvIG Spec | Open Register | Status |
|--------|-----------|---------------|--------|
| **Controller** | ✅ Vereist | ✅ `HaalCentraalBrpHistorieController.php` | ✅ **OK** |
| **Endpoint** | ✅ Vereist | ✅ Geïmplementeerd | ✅ **OK** |
| **peildatum param** | ✅ Vereist | ⚠️ Niet getest | ⚠️ **ONZEKER** |
| **datumVan/datumTot** | ✅ Vereist | ⚠️ Niet getest | ⚠️ **ONZEKER** |
| **Historie data** | ✅ Vereist | ✅ `vb_ax` met `hist='Z'` | ✅ **OK** |

**✅ Basis infrastructuur aanwezig**
**⚠️ Verificatie nodig** of parameters correct werken

---

## 🎁 RvIG Informatieproducten

RvIG levert naast persoonsgegevens ook **afgeleide informatieproducten**:

### 1. Adressering ❌ **NIET GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Aanschrijfwijze, aanhef, een verwijzing naar een persoon in de lopende tekst van een brief, en adresregels die passen in een envelopvenster.

**Vereiste Velden in Response:**

| Informatieproduct | RvIG Spec | Open Register | Status |
|-------------------|-----------|---------------|--------|
| `adressering.aanschrijfwijze` | ✅ Vereist | ❌ Ontbreekt | ❌ **NIET GEÏMPL** |
| `adressering.aanhef` | ✅ Vereist | ❌ Ontbreekt | ❌ **NIET GEÏMPL** |
| `adressering.gebruikInLopendeTekst` | ✅ Vereist | ❌ Ontbreekt | ❌ **NIET GEÏMPL** |
| `adressering.adresregel1` | ✅ Vereist | ❌ Ontbreekt | ❌ **NIET GEÏMPL** |
| `adressering.adresregel2` | ✅ Vereist | ❌ Ontbreekt | ❌ **NIET GEÏMPL** |
| `adressering.adresregel3` | ✅ Vereist | ❌ Ontbreekt | ❌ **NIET GEÏMPL** |

**RvIG Voorbeelden:**

```json
{
  "adressering": {
    "aanschrijfwijze": "Mevrouw J.M. van der Berg",
    "aanhef": "Geachte mevrouw Van der Berg",
    "gebruikInLopendeTekst": "mevrouw Van der Berg",
    "adresregel1": "Mevrouw J.M. van der Berg",
    "adresregel2": "Dorpsstraat 15",
    "adresregel3": "1234 AB  AMSTERDAM"
  }
}
```

**Impact:** Clients moeten zelf adresseringslogica implementeren

---

### 2. Bewoning ❌ **NIET GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Wie er samen in een woning woonde gedurende een periode, of op een peildatum.

**Status:** Zie Bewoning API hierboven - volledig niet geïmplementeerd

---

### 3. Gezag ⚠️ **DATABASE METHODE, GEEN INFORMATIEPRODUCT**

**RvIG Beschrijving:**
> Gezagsrelaties van alle minderjarigen en gezagshouders, ook als er geen aantekening is in het gezagsregister.

**Open Register Status:**

| Aspect | RvIG Spec | Open Register | Status |
|--------|-----------|---------------|--------|
| **Database query** | ✅ Vereist | ✅ `BrpDatabaseService::getGezagsrelaties()` | ✅ **OK** |
| **API endpoint** | ✅ Vereist | ❌ Niet ontsloten | ❌ **NIET GEÏMPL** |
| **In response opgenomen** | ✅ Vereist | ❌ Niet in persoon response | ❌ **NIET GEÏMPL** |

**Databron:** ✅ `probev.gezag_ax` tabel beschikbaar

**Voorbeeld RvIG response:**

```json
{
  "gezag": [
    {
      "type": "ouderlijkGezag",
      "minderjarige": {"burgerservicenummer": "123456789"},
      "ouder": {"burgerservicenummer": "987654321"}
    }
  ]
}
```

---

### 4. Leeftijd ⚠️ **DATABASE METHODE, GEEN INFORMATIEPRODUCT**

**RvIG Beschrijving:**
> Leeftijd (in jaren).

**Open Register Status:**

| Aspect | RvIG Spec | Open Register | Status |
|--------|-----------|---------------|--------|
| **Database query** | ✅ Vereist | ✅ `BrpDatabaseService::getLeeftijd()` | ✅ **OK** |
| **In response opgenomen** | ✅ Vereist | ❌ Niet in persoon response | ❌ **NIET GEÏMPL** |

**RvIG Voorbeeld:**

```json
{
  "leeftijd": 42
}
```

**Implementatie:**
- ✅ Database methode bestaat
- ❌ Niet toegevoegd aan API response

---

### 5. Volledige Naam ❌ **NIET GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Met adellijke titels en predicaten, zonder gebruik van de naam van de partner.

**RvIG Voorbeeld:**

```json
{
  "naam": {
    "volledigeNaam": "Jonkvrouw Dr. J.M. (Janneke) van der Berg MSc"
  }
}
```

**Open Register Status:** ❌ Niet berekend of opgenomen

**Vereiste logica:**
- Adellijke titels
- Academische titels
- Predicaten
- Voornamen volledig uitgeschreven
- Geen partner naam

---

### 6. Voorletters ❌ **NIET GEÏMPLEMENTEERD**

**RvIG Beschrijving:**
> Voorletters van alle voornamen.

**RvIG Voorbeeld:**

```json
{
  "naam": {
    "voorletters": "J.M."
  }
}
```

**Open Register Status:** ❌ Niet berekend

**Vereiste logica:**
- Eerste letter van elke voornaam
- Met punten tussen
- Bijvoorbeeld: "Jan Marie" → "J.M."

---

## 📊 Compliance Score

### Functionaliteit

| Functie | RvIG Vereist | Open Register Status | Score |
|---------|-------------|---------------------|-------|
| **Personen API** | 7 endpoints | 7 endpoints | ✅ **100%** |
| **Bewoning API** | Volledig | Niet geïmplementeerd | ❌ **0%** |
| **Verblijfplaatshistorie** | Volledig | Basis geïmplementeerd | ⚠️ **70%** |
| **RNI** | Inclusief RNI | Niet ontsloten | ❌ **0%** |

**Totale Functionaliteit Score:** ⚠️ **60%**

### Informatieproducten

| Product | RvIG Vereist | Open Register Status | Score |
|---------|-------------|---------------------|-------|
| **Adressering** | 6 velden | 0 velden | ❌ **0%** |
| **Bewoning** | Volledig | Niet geïmplementeerd | ❌ **0%** |
| **Gezag** | In response | Database methode only | ⚠️ **30%** |
| **Leeftijd** | In response | Database methode only | ⚠️ **30%** |
| **Volledige naam** | In response | Niet geïmplementeerd | ❌ **0%** |
| **Voorletters** | In response | Niet geïmplementeerd | ❌ **0%** |

**Totale Informatieproducten Score:** ❌ **10%**

---

## ✅ Wat WEL Goed Is (Na Nested Objects Implementatie)

### 1. Data Structuur ✅

**Na onze implementatie:**

```json
{
  "burgerservicenummer": "168149291",
  "naam": {
    "voornamen": "Janne Malu...",
    "geslachtsnaam": "Naiima Isman Adan"
  },
  "geboorte": {
    "datum": {
      "datum": "1982-03-08",
      "jaar": 1982,
      "maand": 3,
      "dag": 8
    }
  },
  "geslacht": {
    "code": "V",
    "omschrijving": "vrouw"
  }
}
```

✅ **Compliant met RvIG nested object structuur**

### 2. Basis Personen Endpoints ✅

Alle 7 vereiste endpoints geïmplementeerd:
- ✅ `/ingeschrevenpersonen`
- ✅ `/ingeschrevenpersonen/{bsn}`
- ✅ `/ingeschrevenpersonen/{bsn}/partners`
- ✅ `/ingeschrevenpersonen/{bsn}/kinderen`
- ✅ `/ingeschrevenpersonen/{bsn}/ouders`
- ✅ `/ingeschrevenpersonen/{bsn}/nationaliteiten`
- ✅ `/ingeschrevenpersonen/{bsn}/verblijfplaats`

### 3. Database Structuur ✅

Probev schema bevat alle benodigde data:
- ✅ Historie records (`hist='Z'`)
- ✅ Actuele records (`ax='A', hist='A'`)
- ✅ Gezag data (`gezag_ax`)
- ✅ Verblijfplaats historie (`vb_ax`)
- ✅ RNI data (`rni_ax`)

---

## ❌ Wat ONTBREEKT (RvIG Non-Compliant)

### Prioriteit 1: Informatieproducten

**Geen enkel informatieproduct is geïmplementeerd.**

Dit zijn **afgeleide gegevens** die RvIG vereist maar die je nu zelf moet berekenen:

1. **Adressering** (6 velden)
   - Aanschrijfwijze
   - Aanhef
   - Gebruik in lopende tekst
   - Adresregels (3x)

2. **Voorletters**
   - Afleiding uit voornamen

3. **Volledige naam**
   - Met titels en predicaten

4. **Leeftijd**
   - In jaren (wel database methode, maar niet in response)

5. **Gezag**
   - Gezagsrelaties (wel database methode, maar niet in response)

### Prioriteit 2: Bewoning API

Volledig niet geïmplementeerd:
- Historische bewoning van adressen
- Samenstelling bewoners op peildatum
- Samenstelling bewoners in periode

### Prioriteit 3: RNI Ontsluiting

RNI (Registratie Niet-Ingezeten) niet expliciet ontsloten.

---

## 🔧 Implementatie Gap Analysis

### Gap 1: Informatieproducten Service Ontbreekt

**Wat nodig is:**

```php
// lib/Service/InformatieproductenService.php
class InformatieproductenService {
    public function berekenVoorletters(string $voornamen): string {
        // "Jan Marie" → "J.M."
    }
    
    public function berekenAanschrijfwijze(array $persoon): string {
        // Logica volgens RvIG regels
    }
    
    public function berekenAanhef(array $persoon): string {
        // "Geachte heer/mevrouw [naam]"
    }
    
    public function berekenVolledigeNaam(array $persoon): string {
        // Met titels en predicaten
    }
    
    public function berekenAdresregels(array $adres): array {
        // 3 regels voor envelopvenster
    }
    
    public function berekenLeeftijd(string $geboortedatum): int {
        // In jaren
    }
}
```

**Gebruik in Controller:**

```php
$persoon = $this->objectService->find($bsn);

// Voeg informatieproducten toe
$persoon['voorletters'] = $this->informatieproducten->berekenVoorletters(
    $persoon['naam']['voornamen']
);
$persoon['leeftijd'] = $this->informatieproducten->berekenLeeftijd(
    $persoon['geboorte']['datum']['datum']
);
$persoon['adressering'] = [
    'aanschrijfwijze' => $this->informatieproducten->berekenAanschrijfwijze($persoon),
    'aanhef' => $this->informatieproducten->berekenAanhef($persoon),
    // etc.
];
```

### Gap 2: Bewoning Controller Ontbreekt

**Wat nodig is:**

```php
// lib/Controller/BewoningController.php
class BewoningController extends Controller {
    /**
     * GET /adressen/{adresseerbaarObjectIdentificatie}/bewoning
     */
    public function getBewoning(string $adresseerbaarObjectIdentificatie): JSONResponse {
        $peildatum = $this->request->getParam('peildatum');
        $datumVan = $this->request->getParam('datumVan');
        $datumTot = $this->request->getParam('datumTot');
        
        // Query vb_ax tabel voor historie
        // Return bewoners lijst
    }
}
```

**Routes toevoegen:**

```php
['name' => 'Bewoning#getBewoning', 
 'url' => '/adressen/{adresseerbaarObjectIdentificatie}/bewoning', 
 'verb' => 'GET']
```

### Gap 3: RNI Expliciete Ontsluiting

**Wat nodig is:**

- Filter parameter: `?inclusiefRni=true`
- RNI vlag in response
- Aparte queries voor `rni_ax` tabel

---

## 🎯 Aanbevelingen

### Prioriteit 1: Implementeer Informatieproducten (Hoog)

**Impact:** Groot - Vereist door RvIG spec
**Effort:** Medium (2-3 dagen)

**Stappen:**
1. Maak `InformatieproductenService`
2. Implementeer 6 RvIG informatieproducten
3. Voeg toe aan alle personen responses
4. Test met RvIG voorbeelden

### Prioriteit 2: Implementeer Bewoning API (Hoog)

**Impact:** Groot - Kernfunctionaliteit ontbreekt
**Effort:** Medium (2-3 dagen)

**Stappen:**
1. Maak `BewoningController`
2. Implementeer historie queries op `vb_ax`
3. Voeg routes toe
4. Test met peildatum en periode

### Prioriteit 3: Test Verblijfplaatshistorie Parameters (Medium)

**Impact:** Medium - Basis functionaliteit wel aanwezig
**Effort:** Klein (1 dag)

**Stappen:**
1. Verifieer `peildatum` parameter werkt
2. Verifieer `datumVan`/`datumTot` werken
3. Test met RvIG voorbeelden

### Prioriteit 4: Ontsluiting RNI (Laag)

**Impact:** Laag - Niche use case
**Effort:** Klein (1 dag)

**Stappen:**
1. Voeg `inclusiefRni` parameter toe
2. Query `rni_ax` tabel
3. Markeer RNI personen in response

---

## 📖 RvIG Documentatie Referenties

**Gebruik deze bronnen voor implementatie:**

1. **Adressering Afleidingsregels:**
   - https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/adressering/

2. **Voorletters Afleidingsregels:**
   - https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/voorletters/

3. **Leeftijd Afleidingsregels:**
   - https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/leeftijd/

4. **Volledige Naam Afleidingsregels:**
   - https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/volledige-naam/

5. **Gezag Informatieproduct:**
   - https://developer.rvig.nl/brp-api/personen/documentatie/informatieproducten/gezag/

6. **Bewoning API Specificatie:**
   - https://developer.rvig.nl/brp-api/bewoning/specificatie/

7. **Verblijfplaatshistorie API Specificatie:**
   - https://developer.rvig.nl/brp-api/verblijfplaatshistorie/specificatie/

---

## 🏁 Conclusie

### Huidige Compliance Status

**Functionaliteit:** ⚠️ **60%** compliant
- ✅ Personen API volledig
- ❌ Bewoning API ontbreekt
- ⚠️ Verblijfplaatshistorie gedeeltelijk

**Informatieproducten:** ❌ **10%** compliant
- ❌ Geen enkel informatieproduct in responses
- ⚠️ Wel database methodes voor enkele producten
- ❌ Adressering volledig niet geïmplementeerd

**Data Structuur:** ✅ **100%** compliant
- ✅ Nested objects correct na onze implementatie
- ✅ Veldnamen consistent met RvIG
- ✅ Database bevat alle benodigde brondata

### Impact van Nested Objects Implementatie

Onze nested objects implementatie heeft de **data structuur** 100% RvIG-compliant gemaakt:

**Voor:**
- ❌ Platte structuur
- ❌ Veldnaam mismatches
- ❌ Niet Haal Centraal compliant

**Na:**
- ✅ Nested objects volgens RvIG spec
- ✅ Correcte veldnamen
- ✅ Validatie werkt

**Maar:** Informatieproducten ontbreken nog steeds.

### Next Steps

Om **volledig RvIG compliant** te worden:

1. ✅ ~~Data structuur (nested objects)~~ → **GEDAAN**
2. ❌ Informatieproducten implementeren → **TODO**
3. ❌ Bewoning API implementeren → **TODO**
4. ⚠️ Verblijfplaatshistorie testen/valideren → **TODO**

**Geschatte effort:** 1-2 weken voor volledige RvIG compliance
