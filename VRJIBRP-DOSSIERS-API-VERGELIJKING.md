# Vergelijking: vrijBRP Dossiers API vs. Open Registers Implementatie

## Overzicht

Dit document vergelijkt de functionaliteit van de **vrijBRP Dossiers API** (zoals gebruikt in de huidige applicatie) met wat mogelijk is via de **Open Registers implementatie** zoals die nu is gebouwd.

**Bron API:** https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation

---

## 1. Relaties (Relatives)

### vrijBRP Dossiers API

**Endpoint:** `GET /api/v1/relatives/{bsn}`

**Functionaliteit:**
- Haalt alle relaties op van een persoon (partners, kinderen, ouders, ex-partners)
- Retourneert relatie-type, declaration-type, suitableForRelocation, obstructions
- Gebruikt voor verhuizingen om te bepalen wie mee verhuist

**Response structuur:**
```json
{
  "relatives": [
    {
      "person": {
        "bsn": "000000048",
        "age": 40
      },
      "relationshipType": "REGISTERED",
      "declarationType": "REGISTERED",
      "suitableForRelocation": false,
      "suitableFor": ["GENERAL_USE_CASE"],
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

### Open Registers Implementatie

**Status:** ✅ **Gedeeltelijk geïmplementeerd**

**Endpoints:**
- `GET /ingeschrevenpersonen/{bsn}/partners` ✅
- `GET /ingeschrevenpersonen/{bsn}/kinderen` ✅
- `GET /ingeschrevenpersonen/{bsn}/ouders` ✅
- `GET /ingeschrevenpersonen/{bsn}/nationaliteiten` ✅

**Wat werkt:**
- ✅ Relaties kunnen worden opgehaald via Haal Centraal API
- ✅ Relaties worden opgeslagen als `_embedded` in Personen object
- ✅ Fallback naar PostgreSQL als relaties niet in Open Register staan

**Wat ontbreekt:**
- ❌ Geen `relationshipType` of `declarationType` informatie
- ❌ Geen `suitableForRelocation` flag
- ❌ Geen `obstructions` lijst
- ❌ Geen `suitableFor` array
- ❌ Geen leeftijd (`age`) in response
- ❌ Geen ex-partners functionaliteit

**Conclusie:** Basis functionaliteit werkt, maar mist specifieke vrijBRP metadata die nodig is voor verhuizingen.

---

## 2. Verhuizingen (Relocations)

### vrijBRP Dossiers API

#### 2.1 Intra-relocation (binnen gemeente)

**Endpoints:**
- `POST /api/v1/relocations/intra` - Nieuwe verhuizing aanmaken
- `GET /api/v1/relocations/intra/{dossierId}` - Verhuizing ophalen
- `POST /api/v1/relocations/intra/{dossierId}/lodging-consent` - Toestemming hoofdhuurder toevoegen

**Functionaliteit:**
- Aanmaken van verhuizing met relocators (wie verhuist mee)
- Live-in situatie (inwoning)
- Toestemming hoofdhuurder
- Dossier status tracking (incomplete, complete, etc.)

**Flow:**
1. Haal relaties op (`GET /api/v1/relatives/{bsn}`)
2. Maak verhuizing aan (`POST /api/v1/relocations/intra`)
3. Check tasks voor toestemming (`GET /api/v1/tasks?bsn={bsn}&taskType=relocation_consent`)
4. Voeg toestemming toe (`POST /api/v1/relocations/intra/{dossierId}/lodging-consent`)

#### 2.2 Inter-relocation (tussen gemeenten)

**Endpoints:**
- `POST /api/v1/relocations/inter` - Nieuwe inter-gemeentelijke verhuizing
- `GET /api/v1/relocations/inter/{dossierId}` - Verhuizing ophalen

### Open Registers Implementatie

**Status:** ❌ **Niet geïmplementeerd**

**Wat ontbreekt:**
- ❌ Geen dossier/zaak functionaliteit
- ❌ Geen verhuizing endpoints
- ❌ Geen relocators tracking
- ❌ Geen live-in situatie tracking
- ❌ Geen toestemming workflow
- ❌ Geen tasks systeem

**Wat zou mogelijk zijn:**
- ✅ Open Register kan een "Verhuizingen" register aanmaken
- ✅ Schema kan worden gedefinieerd voor verhuizing objecten
- ✅ Relaties kunnen worden gebruikt om relocators te bepalen
- ⚠️ Maar: workflow, tasks en toestemming vereisen extra implementatie

**Conclusie:** Volledige verhuizing functionaliteit vereist implementatie van dossier/zaak systeem bovenop Open Register.

---

## 3. Geboorte (Birth)

### vrijBRP Dossiers API

**Endpoints:**
- `POST /api/v1/birth` - Nieuwe geboorte aanmaken
- `GET /api/v1/birth/{dossierId}` - Geboorte ophalen

**Functionaliteit:**
- Aanmaken van geboorteaangifte
- Familie situatie (gezinssamenstelling)
- Naamkeuze
- Erkenning

**Sub-endpoints:**
- `GET /api/v1/birth/{dossierId}/family-situation` - Familie situatie
- `GET /api/v1/birth/{dossierId}/name-selection` - Naamkeuze
- `GET /api/v1/birth/{dossierId}/acknowledgement` - Erkenning

### Open Registers Implementatie

**Status:** ❌ **Niet geïmplementeerd**

**Wat ontbreekt:**
- ❌ Geen geboorte endpoints
- ❌ Geen dossier functionaliteit
- ❌ Geen naamkeuze workflow
- ❌ Geen erkenning workflow

**Wat zou mogelijk zijn:**
- ✅ Open Register kan een "Geboorten" register aanmaken
- ✅ Schema kan worden gedefinieerd voor geboorte objecten
- ✅ Relaties kunnen worden gebruikt om ouders te bepalen
- ⚠️ Maar: workflow en validaties vereisen extra implementatie

**Conclusie:** Geboorte functionaliteit vereist implementatie van dossier systeem en workflow bovenop Open Register.

---

## 4. Partnerschap (Commitment)

### vrijBRP Dossiers API

**Endpoints:**
- `POST /api/v1/commitment` - Nieuw partnerschap aanmaken
- `PUT /api/v1/commitment/{dossierId}` - Partnerschap bijwerken
- `GET /api/v1/commitment/{dossierId}` - Partnerschap ophalen
- `DELETE /api/v1/commitment/{dossierId}` - Partnerschap annuleren

**Functionaliteit:**
- Aanmaken van partnerschap (geregistreerd partnerschap)
- Bijwerken van partnerschap
- Annuleren van partnerschap

### Open Registers Implementatie

**Status:** ❌ **Niet geïmplementeerd**

**Wat ontbreekt:**
- ❌ Geen partnerschap endpoints
- ❌ Geen mutatie-functionaliteit (schrijven)
- ❌ Geen dossier functionaliteit

**Wat zou mogelijk zijn:**
- ✅ Open Register kan een "Partnerschappen" register aanmaken
- ✅ Schema kan worden gedefinieerd voor partnerschap objecten
- ⚠️ Maar: mutaties vereisen validatie service (vrijBRP Logica Service)

**Conclusie:** Partnerschap functionaliteit vereist mutatie-endpoints en validatie service.

---

## 5. Overlijden (Deaths)

### vrijBRP Dossiers API

#### 5.1 Death in municipality

**Endpoints:**
- `POST /api/v1/deaths/in-municipality` - Overlijden in gemeente aanmaken
- `GET /api/v1/deaths/in-municipality/{dossierId}` - Overlijden ophalen

#### 5.2 Discovered body

**Endpoints:**
- `POST /api/v1/deaths/discovered-body` - Lijk aangetroffen aanmaken
- `GET /api/v1/deaths/discovered-body/{dossierId}` - Lijk aangetroffen ophalen

### Open Registers Implementatie

**Status:** ❌ **Niet geïmplementeerd**

**Wat ontbreekt:**
- ❌ Geen overlijden endpoints
- ❌ Geen mutatie-functionaliteit
- ❌ Geen dossier functionaliteit

**Conclusie:** Overlijden functionaliteit vereist mutatie-endpoints en validatie service.

---

## 6. Dossiers (General)

### vrijBRP Dossiers API

#### 6.1 Dossiers

**Endpoints:**
- `GET /api/v1/dossiers` - Zoek dossiers (met filters)
- `GET /api/v1/dossiers/{dossierId}` - Specifiek dossier ophalen

**Functionaliteit:**
- Zoeken op dossier type, status, BSN, etc.
- Dossier metadata (type, status, referentie ID)

#### 6.2 Dossier Documents

**Endpoints:**
- `POST /api/v1/dossiers/{dossierId}/documents` - Document toevoegen
- `GET /api/v1/dossiers/{dossierId}/documents` - Documenten ophalen
- `GET /api/v1/dossiers/{dossierId}/documents/{documentId}` - Specifiek document

**Functionaliteit:**
- Documenten koppelen aan dossiers
- Document metadata

#### 6.3 Tasks

**Endpoints:**
- `GET /api/v1/tasks` - Zoek planned tasks
- Filters: BSN, taskType, status (planned, in_progress, done)

**Functionaliteit:**
- Task tracking voor workflows
- Bijv. `relocation_consent` task voor verhuizingen

#### 6.4 Data Import

**Endpoints:**
- `POST /api/v1/data-import` - Nieuwe import

**Functionaliteit:**
- Bulk import van data

### Open Registers Implementatie

**Status:** ❌ **Niet geïmplementeerd**

**Wat ontbreekt:**
- ❌ Geen dossier/zaak systeem
- ❌ Geen document management
- ❌ Geen task systeem
- ❌ Geen workflow engine

**Wat zou mogelijk zijn:**
- ✅ Open Register kan registers aanmaken voor verschillende dossier types
- ✅ Documenten kunnen worden opgeslagen als objecten in Open Register
- ⚠️ Maar: workflow, tasks en status tracking vereisen extra implementatie

**Conclusie:** Dossier functionaliteit vereist volledige implementatie van zaakgericht werken systeem.

---

## 7. Authenticatie & Autorisatie

### vrijBRP Dossiers API

**Methode:** Bearer Token (JWT)
- `client_id`: "sim"
- `secret`: "VZV970qmdVY86g@"

### Open Registers Implementatie

**Status:** ⚠️ **Gedeeltelijk geïmplementeerd**

**Wat werkt:**
- ✅ Nextcloud authenticatie (gebruiker moet ingelogd zijn)
- ✅ `@NoAdminRequired` decorators voor endpoints

**Wat ontbreekt:**
- ❌ Geen JWT/Bearer token authenticatie
- ❌ Geen API key systeem
- ❌ Geen client credentials flow

**Conclusie:** API-authenticatie moet worden geïmplementeerd voor externe toegang.

---

## Samenvatting: Functionaliteit Matrix

| Functionaliteit | vrijBRP Dossiers API | Open Registers | Status |
|----------------|---------------------|----------------|--------|
| **Relaties ophalen** | ✅ | ✅ | ✅ Werkt (basis) |
| **Relatie metadata** | ✅ | ❌ | ❌ Ontbreekt |
| **Intra-relocation** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Inter-relocation** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Geboorte** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Partnerschap** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Overlijden** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Dossiers** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Documenten** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Tasks** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **Mutaties (schrijven)** | ✅ | ❌ | ❌ Niet geïmplementeerd |
| **API Authenticatie** | ✅ JWT | ⚠️ Nextcloud | ⚠️ Gedeeltelijk |

---

## Conclusies & Aanbevelingen

### Wat werkt goed ✅

1. **Relaties ophalen** - Basis functionaliteit werkt via Haal Centraal API
2. **Data lezen** - Personen kunnen worden opgehaald en doorzocht
3. **Open Register infrastructuur** - Basis is gelegd voor uitbreiding

### Wat ontbreekt ❌

1. **Dossier/Zaak systeem** - Geen functionaliteit voor dossiers, zaken of workflows
2. **Mutatie-functionaliteit** - Geen mogelijkheid om data te schrijven/muteren
3. **Workflow engine** - Geen task systeem of procesorkestratie
4. **Validatie service** - Geen vrijBRP Logica Service voor RVIG-validaties
5. **Document management** - Geen document koppeling aan dossiers
6. **API authenticatie** - Geen JWT/Bearer token voor externe toegang

### Wat is mogelijk via Open Registers? 🤔

**Ja, maar met extra implementatie:**

1. **Registers aanmaken** - Open Register kan registers aanmaken voor:
   - Verhuizingen
   - Geboorten
   - Partnerschappen
   - Overlijden
   - Dossiers
   - Documenten

2. **Schemas definiëren** - Voor elk dossier type kan een schema worden gedefinieerd

3. **Relaties gebruiken** - Bestaande relatie-functionaliteit kan worden gebruikt

**Maar vereist:**

1. **Mutatie-endpoints** - Schrijf-functionaliteit moet worden geïmplementeerd
2. **Validatie service** - vrijBRP Logica Service voor RVIG-validaties
3. **Workflow engine** - Task systeem en procesorkestratie
4. **Dossier management** - Zaakgericht werken systeem bovenop Open Register
5. **API authenticatie** - JWT/Bearer token implementatie

### Architectuur Vergelijking

#### vrijBRP Dossiers API Architectuur

```
[UI] → [vrijBRP Dossiers API] → [vrijBRP Database]
         ↓
    [vrijBRP Logica]
```

#### Open Registers Architectuur (Huidig)

```
[UI] → [Haal Centraal API] → [Open Register] → [PostgreSQL]
                                    ↓
                            [Alleen lezen]
```

#### Open Registers Architectuur (Volledig - Vereist)

```
[UI] → [vrijBRP Dossiers API] → [Open Register] → [PostgreSQL]
         ↓                              ↓
    [vrijBRP Logica Service]    [Mutatie-endpoints]
         ↓                              ↓
    [Validatie]                  [Eventing]
```

### Aanbevelingen

#### Korte termijn (Quick Wins)

1. **Relatie metadata uitbreiden**
   - Voeg `relationshipType`, `declarationType` toe
   - Voeg `suitableForRelocation` flag toe
   - Voeg `obstructions` lijst toe

2. **API authenticatie implementeren**
   - JWT/Bearer token authenticatie
   - API key systeem voor externe toegang

#### Middellange termijn (Essentieel)

3. **Mutatie-endpoints implementeren**
   - POST endpoints voor aanmaken
   - PUT endpoints voor bijwerken
   - DELETE endpoints voor verwijderen

4. **vrijBRP Logica Service ontwikkelen**
   - RVIG-validaties
   - Datatransformatie
   - Consistentiechecks

#### Lange termijn (Volledige functionaliteit)

5. **Dossier/Zaak systeem**
   - Register voor dossiers
   - Status tracking
   - Workflow engine

6. **Task systeem**
   - Task tracking
   - Workflow orchestration

7. **Document management**
   - Document koppeling aan dossiers
   - Document metadata

---

## Technische Implementatie Overwegingen

### 1. Dossier/Zaak Systeem

**Optie A: Open Register als basis**
- Maak "Dossiers" register aan
- Schema voor elk dossier type
- Status als veld in object

**Voordelen:**
- ✅ Historie/versiebeheer out-of-the-box
- ✅ Eventing mogelijk
- ✅ Common Ground-compliant

**Nadelen:**
- ⚠️ Workflow engine moet apart worden gebouwd
- ⚠️ Task systeem moet apart worden gebouwd

**Optie B: ZGW-systeem bovenop Open Register**
- Gebruik bestaand ZGW-systeem (bijv. Open Zaak)
- ZGW gebruikt Open Register voor data
- ZGW beheert workflows en tasks

**Voordelen:**
- ✅ Volledige workflow engine
- ✅ Task systeem out-of-the-box
- ✅ Common Ground-compliant

**Nadelen:**
- ⚠️ Extra systeem om te beheren
- ⚠️ Integratie complexiteit

### 2. Mutatie-functionaliteit

**Vereisten:**
1. Mutatie-endpoints in Open Register
2. vrijBRP Logica Service voor validatie
3. Eventing bij mutaties
4. Versiebeheer

**Implementatie:**
- Open Register ondersteunt mutaties via API
- Validatie service moet worden gebouwd
- Eventing is beschikbaar in Open Register

### 3. Relatie Metadata

**Implementatie:**
- Voeg velden toe aan Personen schema
- Bereken `suitableForRelocation` op basis van obstructions
- Haal obstructions op uit PostgreSQL queries

---

## Conclusie

**Korte antwoord:** De functionaliteit van de vrijBRP Dossiers API kan **gedeeltelijk** worden gerealiseerd via Open Registers, maar vereist **aanzienlijke extra implementatie**.

**Wat werkt nu:**
- ✅ Relaties ophalen (basis)
- ✅ Personen lezen

**Wat moet worden gebouwd:**
- ❌ Dossier/Zaak systeem
- ❌ Mutatie-functionaliteit
- ❌ Validatie service
- ❌ Workflow engine
- ❌ Task systeem
- ❌ Document management
- ❌ API authenticatie

**Aanbeveling:** Start met het uitbreiden van relatie-functionaliteit en het implementeren van mutatie-endpoints. Daarna dossier/zaak systeem en workflow engine.

---

## Referenties

- [vrijBRP Dossiers API Documentatie](https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation)
- [OPENREGISTER-BRP-ARCHITECTUUR.md](./OPENREGISTER-BRP-ARCHITECTUUR.md)
- [OPENREGISTER-IMPLEMENTATIE-VERGELIJKING.md](./OPENREGISTER-IMPLEMENTATIE-VERGELIJKING.md)
- [RELATIES-VIA-OPENREGISTER-IMPLEMENTATIE.md](./RELATIES-VIA-OPENREGISTER-IMPLEMENTATIE.md)







