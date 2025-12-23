# Vergelijking: Architectuurdocument vs. Huidige Implementatie

Dit document vergelijkt de architectuur zoals beschreven in `OPENREGISTER-BRP-ARCHITECTUUR.md` met de huidige implementatie van Open Register.

## Overzicht Status

| Component | Architectuur (Doel) | Huidige Status | Match |
|-----------|-------------------|----------------|-------|
| **Laag 1: Database** | vrijBRP PostgreSQL-database | bevax PostgreSQL met probev schema | ✅ |
| **Laag 2: Open Register** | Componentenlaag met API's | Geïnstalleerd en geconfigureerd | ✅ |
| **Laag 3: Haal Centraal API** | Gestandaardiseerde BRP Bevragen API | Basis implementatie aanwezig | ⚠️ Gedeeltelijk |
| **Laag 3: vrijBRP Logica Service** | Mutatievalidatie service | ❌ Niet geïmplementeerd | ❌ |
| **Laag 4: ZGW-systeem** | Procesorkestratie | ❌ Niet geïmplementeerd | ❌ |
| **Laag 5: UI/Interfaces** | Ambtenaar-interfaces | ❌ Niet geïmplementeerd | ❌ |

## Gedetailleerde Vergelijking

### 1. Laag 1: Gegevens (Data)

#### Architectuurvereiste
- PostgreSQL-database met vrijBRP-tabellen
- Data moet toegankelijk zijn voor Open Register

#### Huidige Implementatie
- ✅ **Database:** `bevax` PostgreSQL-database
- ✅ **Schema:** `probev` met 198 tabellen volgens PL-AX specificatie
- ✅ **Data:** ~2 miljoen rijen, 20.630 actuele personen
- ✅ **Open Register Source:** Gekoppeld via `pgsql://postgres:@host.docker.internal:5432/bevax?search_path=probev`

**Status:** ✅ **Volledig geïmplementeerd**

---

### 2. Laag 2: Componenten (Open Register)

#### Architectuurvereiste
- Open Register-applicatie geïnstalleerd
- Schemas geconfigureerd volgens Haal Centraal-specificatie
- Database mapping tussen Open Register-model en vrijBRP-database

#### Huidige Implementatie

**Geïnstalleerd:**
- ✅ Open Register-app geïnstalleerd in Nextcloud
- ✅ Source ID 1 gekoppeld aan bevax-database
- ✅ Register ID 1 aangemaakt

**Schemas:**
- ⚠️ **14 schemas aangemaakt** maar verwijzen nog naar **oude structuur**
- ⚠️ Schemas moeten worden bijgewerkt om exact overeen te komen met Haal Centraal-specificatie
- Schemas die bestaan:
  - Personen
  - Adressen
  - Zaken
  - Erkenningen
  - Gezagsverhoudingen
  - Huwelijken
  - Mutaties
  - Nationaliteiten
  - PersoonFavoriet
  - Reisdocumenten
  - RniPersonen
  - ZaakFavoriet
  - BrpApiLogs
  - Config

**Database Mapping:**
- ⚠️ Mapping bestaat maar moet worden gecontroleerd/verbeterd
- ⚠️ Schemas verwijzen nog naar oude `bevax` tabellen i.p.v. `probev` tabellen
- ⚠️ Normalisatie-uitdaging: database gebruikt genormaliseerde structuur (`c_voorn`, `c_naam`, etc.)

**Status:** ⚠️ **Gedeeltelijk geïmplementeerd - Schemas moeten worden bijgewerkt**

---

### 3. Laag 3: Diensten (Haal Centraal BRP Bevragen API)

#### Architectuurvereiste (Stap 1-3 uit document)

**Stap 1: Open Register-datamodel definiëren**
- Schemas moeten exact overeenkomen met Haal Centraal-specificatie
- Veldnamen, datatypes en relaties moeten strikt de standaard volgen

**Stap 2: Database mapping implementeren**
- Mapping tussen Open Register-velden en probev-tabellen
- Vertaling van genormaliseerde data naar gestandaardiseerd formaat

**Stap 3: Endpoints configureren**
- `/ingeschrevenpersonen` endpoint
- Queryparameters (filters, sortering) volgens Haal Centraal-standaard

#### Huidige Implementatie

**Controller:**
- ✅ `HaalCentraalBrpController.php` geïmplementeerd
- ✅ Routes toegevoegd aan `appinfo/routes.php`

**Endpoints:**
- ✅ `GET /ingeschrevenpersonen` - Lijst ingeschreven personen
- ✅ `GET /ingeschrevenpersonen/{burgerservicenummer}` - Specifieke persoon op BSN
- ❌ `GET /ingeschrevenpersonen/{bsn}/partners` - **Nog niet geïmplementeerd**
- ❌ `GET /ingeschrevenpersonen/{bsn}/kinderen` - **Nog niet geïmplementeerd**
- ❌ `GET /ingeschrevenpersonen/{bsn}/ouders` - **Nog niet geïmplementeerd**
- ❌ `GET /ingeschrevenpersonen/{bsn}/verblijfplaats` - **Nog niet geïmplementeerd**
- ❌ `GET /ingeschrevenpersonen/{bsn}/nationaliteiten` - **Nog niet geïmplementeerd**

**Data Transformatie:**
- ✅ Transformatie van OpenRegister-formaat naar Haal Centraal-formaat geïmplementeerd
- ✅ Ondersteuning voor zowel vrijBRP- als GGM-schemas
- ⚠️ Transformatie werkt maar moet worden gevalideerd tegen volledige Haal Centraal-specificatie

**Status:** ⚠️ **Gedeeltelijk geïmplementeerd - Basis endpoints werken, maar niet compleet**

---

### 4. Laag 3: Diensten (vrijBRP Logica Service)

#### Architectuurvereiste
- Gespecialiseerde service voor BRP-mutatielogica
- RVIG-validatieregels implementeren
- Datatransformatie van API-formaat naar persistente BRP-structuur
- Consistentiechecks en historie-afhandeling

#### Huidige Implementatie
- ❌ **Niet geïmplementeerd**
- ❌ Geen mutatie-endpoints
- ❌ Geen validatieservice
- ❌ Geen RVIG-logica

**Status:** ❌ **Niet geïmplementeerd**

---

### 5. Laag 4: Processen (ZGW-systeem)

#### Architectuurvereiste
- Zaakgericht Werken-systeem voor procesorkestratie
- Processtappen bepalen wanneer mutaties mogen plaatsvinden
- Bevoegdheidscontrole

#### Huidige Implementatie
- ❌ **Niet geïmplementeerd**
- ❌ Geen ZGW-integratie
- ❌ Geen procesorkestratie

**Status:** ❌ **Niet geïmplementeerd**

---

### 6. Laag 5: Interactie (UI/Interfaces)

#### Architectuurvereiste
- Interfaces voor ambtenaren van burgerzaken
- Geboorteaangifte-proces als PoC

#### Huidige Implementatie
- ❌ **Niet geïmplementeerd**
- ❌ Geen UI voor burgerzaken-processen
- ❌ Geen PoC voor geboorteaangifte

**Status:** ❌ **Niet geïmplementeerd**

---

## Implementatiegaps

### Kritieke Gaps (Blokkerend voor volledige architectuur)

1. **Open Register-schemas bijwerken**
   - Schemas moeten exact overeenkomen met Haal Centraal-specificatie
   - Mapping naar `probev`-tabellen moet worden gecorrigeerd
   - Normalisatie-uitdaging moet worden opgelost (views of denormalisatie)

2. **vrijBRP Logica Service ontwikkelen**
   - Mutatievalidatie-service moet worden gebouwd
   - RVIG-regels moeten worden geïmplementeerd
   - Datatransformatie voor mutaties moet worden gerealiseerd

3. **Mutatie-endpoints implementeren**
   - Schrijfkant van de API ontbreekt volledig
   - Eventing bij mutaties moet worden geïmplementeerd
   - Versiebeheer moet worden gevalideerd

### Belangrijke Gaps (Voor volledige functionaliteit)

4. **Haal Centraal API uitbreiden**
   - Partners, kinderen, ouders, verblijfplaats, nationaliteiten endpoints
   - Volledige compliance met Haal Centraal-specificatie

5. **ZGW-integratie**
   - Procesorkestratie voor burgerzaken-processen
   - Bevoegdheidscontrole

6. **UI/Interfaces**
   - Ambtenaar-interfaces voor burgerzaken
   - PoC voor geboorteaangifte

---

## Aanbevelingen per Laag

### Laag 1: Gegevens ✅
**Status:** Compleet, geen actie nodig

### Laag 2: Open Register ⚠️
**Acties:**
1. Open Register-schemas bijwerken om te verwijzen naar `probev`-tabellen
2. Schemas valideren tegen Haal Centraal-specificatie
3. Overwegen views te maken voor denormalisatie van genormaliseerde data
4. Test queries uitvoeren om correcte werking te verifiëren

**Prioriteit:** 🔴 **Hoog** (blokkeert verdere ontwikkeling)

### Laag 3: Haal Centraal API ⚠️
**Acties:**
1. Resterende endpoints implementeren (partners, kinderen, ouders, etc.)
2. Volledige Haal Centraal-specificatie downloaden en valideren
3. Test suite uitvoeren tegen Haal Centraal Cucumber-tests
4. Authenticatie toevoegen (API keys)

**Prioriteit:** 🟡 **Medium** (basis werkt, maar niet compleet)

### Laag 3: vrijBRP Logica Service ❌
**Acties:**
1. Service-architectuur ontwerpen
2. RVIG-regels inventariseren en documenteren
3. Validatieservice ontwikkelen
4. Datatransformatie implementeren
5. Testen met mutatie-scenario's

**Prioriteit:** 🔴 **Hoog** (vereist voor mutaties)

### Laag 4: ZGW-systeem ❌
**Acties:**
1. ZGW-systeem selecteren of ontwikkelen
2. Integratie met Open Register ontwerpen
3. Procesorkestratie implementeren
4. Bevoegdheidscontrole toevoegen

**Prioriteit:** 🟢 **Laag** (kan later worden toegevoegd)

### Laag 5: UI/Interfaces ❌
**Acties:**
1. UI-architectuur ontwerpen
2. PoC voor geboorteaangifte ontwikkelen
3. Ambtenaar-interfaces bouwen

**Prioriteit:** 🟢 **Laag** (kan later worden toegevoegd)

---

## Conclusie

### Wat werkt goed ✅
- Database-infrastructuur is compleet en operationeel
- Open Register is geïnstalleerd en basisconfiguratie is aanwezig
- Basis Haal Centraal API-endpoints zijn geïmplementeerd en werken
- Data-transformatie tussen OpenRegister en Haal Centraal-formaat werkt

### Wat moet worden aangepakt 🔴
- **Open Register-schemas bijwerken** - Dit is de eerste kritieke stap
- **vrijBRP Logica Service ontwikkelen** - Vereist voor mutaties
- **Mutatie-endpoints implementeren** - Volledige API-functionaliteit

### Volgende Stappen (Prioriteit)

1. **🔴 Hoog:** Open Register-schemas bijwerken naar `probev`-structuur en Haal Centraal-specificatie
2. **🔴 Hoog:** vrijBRP Logica Service ontwerpen en ontwikkelen
3. **🟡 Medium:** Haal Centraal API uitbreiden met resterende endpoints
4. **🟢 Laag:** ZGW-integratie en UI-ontwikkeling

---

## Referenties

- [OPENREGISTER-BRP-ARCHITECTUUR.md](./OPENREGISTER-BRP-ARCHITECTUUR.md) - Architectuurdocument
- [OPENREGISTER-BEVAX-CONFIG.md](./OPENREGISTER-BEVAX-CONFIG.md) - Databaseconfiguratie
- [HAAL-CENTRAAL-IMPLEMENTATIE.md](./HAAL-CENTRAAL-IMPLEMENTATIE.md) - Haal Centraal-implementatie
- [BEVAX-CONFIG-SUMMARY.md](./BEVAX-CONFIG-SUMMARY.md) - Configuratiesamenvatting







