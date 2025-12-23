# Vergelijking: Gemeente Utrecht Uitvraag vs. Open Registers Implementatie

## Overzicht

Dit document vergelijkt de **uitvraag van gemeente Utrecht** voor een "Proof of Concept Domeinregistratie Burgerzaken" (door Shift2) met de **huidige Open Registers implementatie** zoals die nu is gebouwd.

**Uitvraag:** Proof of Concept Domeinregistratie Burgerzaken  
**Opdrachtgever:** Gemeente Utrecht  
**Uitvoerder:** Shift2

**Huidige Implementatie:** Open Register bovenop vrijBRP-database met Haal Centraal BRP Bevragen API

---

## 1. Uitvraag Analyse

### 1.1 Doelstelling PoC Domeinregistratie Burgerzaken

Op basis van de uitvraag gaat het om een Proof of Concept voor **domeinregistratie binnen burgerzaken**. Dit impliceert:

**Verwachte Functionaliteiten:**
- ✅ Registratie van burgerzaken-processen (geboorte, verhuizing, partnerschap, overlijden, etc.)
- ✅ Dossier/zaak management voor burgerzaken
- ✅ Workflow-orkestratie voor processen
- ✅ Document management gekoppeld aan dossiers
- ✅ Mutatie-functionaliteit (schrijven naar BRP)
- ✅ Validatie van BRP-mutaties volgens RVIG-regels
- ✅ Relatiebeheer voor burgerzaken-processen
- ✅ Task management voor workflows

**Architectuurvereisten (vermoedelijk):**
- Common Ground-compliant architectuur
- API-first benadering
- Scheiding van data, logica en processen
- Gestandaardiseerde interfaces (Haal Centraal, ZGW)
- Audit trail en versiebeheer

---

## 2. Huidige Implementatie Status

### 2.1 Overzicht Componenten

| Component | Status | Details |
|-----------|--------|---------|
| **Laag 1: Database** | ✅ Compleet | PostgreSQL `bevax` met `probev` schema (198 tabellen, ~2M rijen) |
| **Laag 2: Open Register** | ⚠️ Gedeeltelijk | Geïnstalleerd, 14 schemas, maar nog niet volledig geconfigureerd |
| **Laag 3: Haal Centraal API** | ⚠️ Gedeeltelijk | Basis endpoints werken, maar niet compleet |
| **Laag 3: vrijBRP Logica Service** | ❌ Afwezig | Niet geïmplementeerd |
| **Laag 4: ZGW-systeem** | ❌ Afwezig | Niet geïmplementeerd |
| **Laag 5: UI/Interfaces** | ❌ Afwezig | Niet geïmplementeerd |

### 2.2 Gedetailleerde Functionaliteit

#### ✅ Wat werkt (Bevragen/Lezen)

**Haal Centraal BRP Bevragen API:**
- ✅ `GET /ingeschrevenpersonen` - Lijst ingeschreven personen
- ✅ `GET /ingeschrevenpersonen/{bsn}` - Specifieke persoon op BSN
- ✅ `GET /ingeschrevenpersonen/{bsn}/partners` - Partners ophalen
- ✅ `GET /ingeschrevenpersonen/{bsn}/kinderen` - Kinderen ophalen
- ✅ `GET /ingeschrevenpersonen/{bsn}/ouders` - Ouders ophalen
- ✅ `GET /ingeschrevenpersonen/{bsn}/nationaliteiten` - Nationaliteiten ophalen

**Data Transformatie:**
- ✅ Transformatie van OpenRegister-formaat naar Haal Centraal-formaat
- ✅ Ondersteuning voor zowel vrijBRP- als GGM-schemas
- ✅ Fallback naar PostgreSQL als data niet in Open Register staat

**Relaties:**
- ✅ Relaties worden opgeslagen als `_embedded` in Personen object
- ✅ Relaties kunnen worden opgehaald via Haal Centraal API

#### ❌ Wat ontbreekt (Mutaties/Schrijven)

**Mutatie-functionaliteit:**
- ❌ Geen POST endpoints voor aanmaken
- ❌ Geen PUT endpoints voor bijwerken
- ❌ Geen DELETE endpoints voor verwijderen
- ❌ Geen mutatie-validatie service
- ❌ Geen eventing bij mutaties

**Dossier/Zaak Systeem:**
- ❌ Geen dossier/zaak functionaliteit
- ❌ Geen status tracking
- ❌ Geen workflow engine
- ❌ Geen task systeem

**Document Management:**
- ❌ Geen document koppeling aan dossiers
- ❌ Geen document metadata

**Validatie Service:**
- ❌ Geen vrijBRP Logica Service
- ❌ Geen RVIG-validaties
- ❌ Geen datatransformatie voor mutaties

**API Authenticatie:**
- ⚠️ Alleen Nextcloud authenticatie (geen JWT/Bearer token)
- ❌ Geen API key systeem voor externe toegang

---

## 3. Vergelijking: Matches, Verschillen, Kansen en Bedreigingen

### 3.1 Matches ✅

#### Architectuur-principes
- ✅ **Common Ground-compliant:** Beide benaderingen volgen het vijf-lagenmodel
- ✅ **API-first:** Beide gebruiken gestandaardiseerde API's (Haal Centraal)
- ✅ **Data bij de bron:** Open Register fungeert als bronregistratie
- ✅ **Scheiding van lagen:** Duidelijke scheiding tussen data, logica en processen

#### Basis-infrastructuur
- ✅ **Database:** PostgreSQL-database met vrijBRP-data is aanwezig
- ✅ **Open Register:** Componentenlaag is geïnstalleerd en geconfigureerd
- ✅ **Haal Centraal API:** Basis implementatie voor bevragen is aanwezig
- ✅ **Relaties:** Relaties kunnen worden opgehaald (partners, kinderen, ouders)

#### Technische basis
- ✅ **Versiebeheer:** Open Register ondersteunt historie/versies
- ✅ **Eventing:** Open Register kan events genereren (nog niet gebruikt)
- ✅ **Schema's:** 14 schemas zijn aangemaakt (moeten worden bijgewerkt)

### 3.2 Verschillen ⚠️

#### Functionele verschillen

| Functionaliteit | Uitvraag Vereiste | Huidige Status | Verschil |
|----------------|-------------------|----------------|----------|
| **Dossier/Zaak Systeem** | ✅ Vereist | ❌ Afwezig | **Kritiek verschil** |
| **Mutatie-functionaliteit** | ✅ Vereist | ❌ Afwezig | **Kritiek verschil** |
| **Workflow Engine** | ✅ Vereist | ❌ Afwezig | **Kritiek verschil** |
| **Validatie Service** | ✅ Vereist | ❌ Afwezig | **Kritiek verschil** |
| **Document Management** | ✅ Vereist | ❌ Afwezig | **Belangrijk verschil** |
| **Task Systeem** | ✅ Vereist | ❌ Afwezig | **Belangrijk verschil** |
| **API Authenticatie** | ✅ JWT/Bearer | ⚠️ Nextcloud | **Belangrijk verschil** |
| **Relatie Metadata** | ✅ Vereist | ⚠️ Gedeeltelijk | **Middel verschil** |

#### Architectonische verschillen

**Uitvraag verwacht:**
- Volledige vijf-lagenarchitectuur met alle componenten operationeel
- ZGW-systeem voor procesorkestratie
- vrijBRP Logica Service voor validaties
- UI/Interfaces voor ambtenaren

**Huidige implementatie heeft:**
- Alleen Laag 1 en 2 volledig operationeel
- Laag 3 gedeeltelijk (alleen bevragen, geen mutaties)
- Laag 4 en 5 volledig afwezig

### 3.3 Kansen 🎯

#### Korte termijn kansen (Quick Wins)

1. **Relatie-functionaliteit uitbreiden**
   - ✅ Basis werkt al
   - 🎯 Voeg `relationshipType`, `declarationType` toe
   - 🎯 Voeg `suitableForRelocation` flag toe
   - 🎯 Voeg `obstructions` lijst toe
   - **Impact:** Verhoogt waarde van bestaande functionaliteit

2. **API Authenticatie implementeren**
   - ✅ Nextcloud authenticatie werkt al
   - 🎯 Voeg JWT/Bearer token authenticatie toe
   - 🎯 Implementeer API key systeem
   - **Impact:** Maakt externe toegang mogelijk

3. **Haal Centraal API completeren**
   - ✅ Basis endpoints werken
   - 🎯 Voeg resterende endpoints toe (verblijfplaats, etc.)
   - 🎯 Valideer tegen volledige Haal Centraal-specificatie
   - **Impact:** Volledige compliance met standaard

#### Middellange termijn kansen (Essentieel)

4. **Mutatie-endpoints implementeren**
   - ✅ Open Register ondersteunt mutaties
   - 🎯 Implementeer POST/PUT/DELETE endpoints
   - 🎯 Voeg eventing toe bij mutaties
   - **Impact:** Maakt schrijffunctionaliteit mogelijk

5. **vrijBRP Logica Service ontwikkelen**
   - ✅ Architectuur is gedefinieerd
   - 🎯 Ontwikkel validatieservice
   - 🎯 Implementeer RVIG-regels
   - 🎯 Realiseer datatransformatie
   - **Impact:** Maakt veilige mutaties mogelijk

6. **Dossier/Zaak Systeem bouwen**
   - ✅ Open Register kan registers aanmaken
   - 🎯 Maak "Dossiers" register aan
   - 🎯 Definieer schemas voor dossier types
   - 🎯 Implementeer status tracking
   - **Impact:** Basis voor burgerzaken-processen

#### Lange termijn kansen (Volledige functionaliteit)

7. **ZGW-integratie**
   - ✅ Common Ground-standaard is bekend
   - 🎯 Integreer met Open Zaak of vergelijkbaar ZGW-systeem
   - 🎯 Implementeer procesorkestratie
   - **Impact:** Volledige workflow-ondersteuning

8. **Workflow Engine**
   - ✅ Task systeem kan worden gebouwd bovenop Open Register
   - 🎯 Implementeer task tracking
   - 🎯 Realiseer workflow orchestration
   - **Impact:** Automatische procesafhandeling

9. **Document Management**
   - ✅ Open Register kan documenten opslaan als objecten
   - 🎯 Koppel documenten aan dossiers
   - 🎯 Implementeer document metadata
   - **Impact:** Volledige dossier-functionaliteit

10. **UI/Interfaces**
    - ✅ Basis infrastructuur is aanwezig
    - 🎯 Bouw ambtenaar-interfaces
    - 🎯 Implementeer PoC voor geboorteaangifte
    - **Impact:** Gebruiksvriendelijke interface

### 3.4 Bedreigingen ⚠️

#### Technische bedreigingen

1. **Schema-configuratie incompleet**
   - ⚠️ **Risico:** 14 schemas verwijzen nog naar oude structuur
   - ⚠️ **Impact:** Data mapping werkt niet correct
   - ⚠️ **Mitigatie:** Schemas bijwerken naar `probev`-structuur en Haal Centraal-specificatie

2. **Normalisatie-uitdaging**
   - ⚠️ **Risico:** Database gebruikt genormaliseerde structuur (`c_voorn`, `c_naam`, etc.)
   - ⚠️ **Impact:** Complexe mapping vereist voor gestandaardiseerde API
   - ⚠️ **Mitigatie:** Views maken voor denormalisatie of mapping verbeteren

3. **Mutatie-validatie ontbreekt**
   - ⚠️ **Risico:** Geen validatie bij mutaties kan leiden tot data-inconsistentie
   - ⚠️ **Impact:** Onveilige mutaties, mogelijk corrupte data
   - ⚠️ **Mitigatie:** vrijBRP Logica Service ontwikkelen VOOR mutaties activeren

4. **Performance-risico's**
   - ⚠️ **Risico:** API-calls introduceren latency ten opzichte van directe SQL
   - ⚠️ **Impact:** Langzamere response tijden voor complexe queries
   - ⚠️ **Mitigatie:** Caching implementeren, queries optimaliseren

#### Organisatorische bedreigingen

5. **Afhankelijkheid van vrijBRP-leverancier**
   - ⚠️ **Risico:** vrijBRP Logica Service moet worden ontwikkeld door leverancier
   - ⚠️ **Impact:** Blokkeert volledige implementatie
   - ⚠️ **Mitigatie:** Contractuele afspraken maken, alternatieve oplossing overwegen

6. **Complexiteit van implementatie**
   - ⚠️ **Risico:** Veel componenten moeten worden ontwikkeld
   - ⚠️ **Impact:** Langere ontwikkeltijd, hogere kosten
   - ⚠️ **Mitigatie:** Gefaseerde aanpak, prioriteren kritieke componenten

7. **Kennis en expertise**
   - ⚠️ **Risico:** Specifieke kennis vereist voor Open Register, RVIG-regels, ZGW
   - ⚠️ **Impact:** Leercurve, mogelijk fouten
   - ⚠️ **Mitigatie:** Training, documentatie, externe expertise

#### Functionele bedreigingen

8. **Incomplete Haal Centraal API**
   - ⚠️ **Risico:** Niet alle endpoints zijn geïmplementeerd
   - ⚠️ **Impact:** Beperkte functionaliteit voor afnemers
   - ⚠️ **Mitigatie:** Volledige API implementeren volgens specificatie

9. **Geen workflow-ondersteuning**
   - ⚠️ **Risico:** Processen kunnen niet worden georkestreerd
   - ⚠️ **Impact:** Handmatige procesafhandeling vereist
   - ⚠️ **Mitigatie:** ZGW-systeem integreren of workflow engine bouwen

10. **Geen document management**
    - ⚠️ **Risico:** Documenten kunnen niet worden gekoppeld aan dossiers
    - ⚠️ **Impact:** Onvolledige dossier-functionaliteit
    - ⚠️ **Mitigatie:** Document management implementeren bovenop Open Register

---

## 4. Gap Analyse

### 4.1 Kritieke Gaps (Blokkerend voor PoC)

| Gap | Impact | Prioriteit | Geschatte Effort |
|-----|--------|------------|------------------|
| **Dossier/Zaak Systeem** | 🔴 Kritiek | 🔴 Hoog | 4-6 weken |
| **Mutatie-functionaliteit** | 🔴 Kritiek | 🔴 Hoog | 3-4 weken |
| **vrijBRP Logica Service** | 🔴 Kritiek | 🔴 Hoog | 6-8 weken |
| **Workflow Engine** | 🔴 Kritiek | 🔴 Hoog | 4-6 weken |
| **Validatie Service** | 🔴 Kritiek | 🔴 Hoog | 6-8 weken |

### 4.2 Belangrijke Gaps (Voor volledige functionaliteit)

| Gap | Impact | Prioriteit | Geschatte Effort |
|-----|--------|------------|------------------|
| **Document Management** | 🟡 Belangrijk | 🟡 Medium | 2-3 weken |
| **Task Systeem** | 🟡 Belangrijk | 🟡 Medium | 2-3 weken |
| **API Authenticatie (JWT)** | 🟡 Belangrijk | 🟡 Medium | 1-2 weken |
| **Relatie Metadata** | 🟡 Belangrijk | 🟡 Medium | 1 week |
| **Haal Centraal API Completering** | 🟡 Belangrijk | 🟡 Medium | 2-3 weken |

### 4.3 Optionele Gaps (Nice to have)

| Gap | Impact | Prioriteit | Geschatte Effort |
|-----|--------|------------|------------------|
| **UI/Interfaces** | 🟢 Optioneel | 🟢 Laag | 4-6 weken |
| **Performance Optimalisatie** | 🟢 Optioneel | 🟢 Laag | 2-3 weken |
| **Monitoring & Logging** | 🟢 Optioneel | 🟢 Laag | 1-2 weken |

---

## 5. Roadmap naar PoC Domeinregistratie Burgerzaken

### 5.1 Fase 1: Basis Stabilisatie (4-6 weken)

**Doel:** Basis-infrastructuur stabiliseren en completeren

**Acties:**
1. ✅ Open Register-schemas bijwerken naar `probev`-structuur
2. ✅ Haal Centraal API completeren (alle endpoints)
3. ✅ Relatie-functionaliteit uitbreiden met metadata
4. ✅ API Authenticatie implementeren (JWT/Bearer token)

**Deliverables:**
- Werkende Haal Centraal BRP Bevragen API (volledig)
- Gestabiliseerde schema-configuratie
- Externe API-toegang mogelijk

### 5.2 Fase 2: Mutatie-functionaliteit (6-8 weken)

**Doel:** Schrijffunctionaliteit realiseren met validatie

**Acties:**
1. ✅ Mutatie-endpoints implementeren (POST/PUT/DELETE)
2. ✅ vrijBRP Logica Service ontwikkelen
3. ✅ RVIG-validaties implementeren
4. ✅ Eventing bij mutaties realiseren
5. ✅ Datatransformatie voor mutaties implementeren

**Deliverables:**
- Werkende mutatie-endpoints
- Validatieservice voor BRP-mutaties
- Eventing bij mutaties

### 5.3 Fase 3: Dossier/Zaak Systeem (4-6 weken)

**Doel:** Basis voor burgerzaken-processen

**Acties:**
1. ✅ "Dossiers" register aanmaken in Open Register
2. ✅ Schemas definiëren voor dossier types (geboorte, verhuizing, etc.)
3. ✅ Status tracking implementeren
4. ✅ Document management koppelen aan dossiers

**Deliverables:**
- Werkend dossier/zaak systeem
- Document koppeling aan dossiers
- Status tracking functionaliteit

### 5.4 Fase 4: Workflow & Processen (4-6 weken)

**Doel:** Procesorkestratie realiseren

**Acties:**
1. ✅ ZGW-integratie (Open Zaak of vergelijkbaar)
2. ✅ Workflow engine implementeren
3. ✅ Task systeem bouwen
4. ✅ Procesorkestratie voor burgerzaken-processen

**Deliverables:**
- Werkend ZGW-systeem
- Workflow engine
- Task management systeem

### 5.5 Fase 5: UI & PoC (4-6 weken)

**Doel:** Gebruiksvriendelijke interface en PoC-validatie

**Acties:**
1. ✅ Ambtenaar-interfaces bouwen
2. ✅ PoC voor geboorteaangifte implementeren
3. ✅ Testen en valideren van volledige flow
4. ✅ Documentatie en training

**Deliverables:**
- Werkende UI voor ambtenaren
- Validatie PoC geboorteaangifte
- Volledige documentatie

**Totaal geschatte tijd:** 22-32 weken (5.5-8 maanden)

---

## 6. Aanbevelingen

### 6.1 Strategische Aanbevelingen

1. **Gefaseerde aanpak**
   - Start met Fase 1 (Basis Stabilisatie) om fundament te leggen
   - Valideer elke fase voordat je doorgaat naar volgende fase
   - Pas roadmap aan op basis van leerervaringen

2. **Prioriteren kritieke componenten**
   - Focus eerst op mutatie-functionaliteit en validatie
   - Dossier/zaak systeem kan later worden toegevoegd
   - UI kan worden gebouwd parallel aan backend

3. **Leverancier-afhankelijkheid adresseren**
   - Maak contractuele afspraken met vrijBRP-leverancier
   - Overweeg alternatieve oplossing als leverancier niet meewerkt
   - Documenteer alle afhankelijkheden

### 6.2 Technische Aanbevelingen

4. **Schema-configuratie eerst**
   - Bijwerken van schemas is eerste kritieke stap
   - Zonder correcte schema-configuratie werkt niets
   - Valideer tegen Haal Centraal-specificatie

5. **Validatie VOOR mutaties**
   - Implementeer validatieservice VOOR mutaties activeren
   - Test validaties grondig met testdata
   - Documenteer alle RVIG-regels

6. **ZGW-integratie overwegen**
   - Gebruik bestaand ZGW-systeem (Open Zaak) i.p.v. zelf bouwen
   - Integratie is complexer maar sneller dan zelf ontwikkelen
   - Common Ground-compliant uit de box

### 6.3 Organisatorische Aanbevelingen

7. **Kennis en expertise**
   - Investeer in training voor Open Register
   - Haal externe expertise binnen voor RVIG-regels
   - Documenteer alle beslissingen en rationale

8. **Risicomanagement**
   - Identificeer alle risico's vroegtijdig
   - Maak mitigatieplannen voor kritieke risico's
   - Monitor risico's continu tijdens project

9. **Stakeholder management**
   - Betrek gemeente Utrecht vroegtijdig bij beslissingen
   - Communiceer regelmatig over voortgang
   - Valideer requirements continu

---

## 7. Conclusie

### 7.1 Samenvatting Vergelijking

**Matches:** ✅
- Architectuur-principes zijn compatibel
- Basis-infrastructuur is aanwezig
- Technische basis is gelegd

**Verschillen:** ⚠️
- Grote functionele gaps (dossier/zaak, mutaties, workflow)
- Architectonische verschillen (ontbrekende lagen)
- Technische verschillen (validatie, authenticatie)

**Kansen:** 🎯
- Veel functionaliteit kan worden gebouwd op bestaande basis
- Gefaseerde aanpak mogelijk
- Common Ground-compliance kan worden behaald

**Bedreigingen:** ⚠️
- Kritieke componenten ontbreken
- Leverancier-afhankelijkheid
- Complexiteit van implementatie

### 7.2 Haalbaarheid PoC

**Korte antwoord:** De PoC Domeinregistratie Burgerzaken is **haalbaar**, maar vereist **aanzienlijke ontwikkeling** van ontbrekende componenten.

**Wat werkt nu:**
- ✅ Basis-infrastructuur (database, Open Register)
- ✅ Bevragen-functionaliteit (Haal Centraal API)
- ✅ Relaties ophalen

**Wat moet worden gebouwd:**
- ❌ Mutatie-functionaliteit (6-8 weken)
- ❌ Validatieservice (6-8 weken)
- ❌ Dossier/zaak systeem (4-6 weken)
- ❌ Workflow engine (4-6 weken)
- ❌ UI/Interfaces (4-6 weken)

**Geschatte totale tijd:** 22-32 weken (5.5-8 maanden)

### 7.3 Aanbeveling

**Start met gefaseerde aanpak:**
1. **Fase 1:** Stabiliseer basis (4-6 weken)
2. **Fase 2:** Implementeer mutaties (6-8 weken)
3. **Fase 3:** Bouw dossier/zaak systeem (4-6 weken)
4. **Fase 4:** Realiseer workflow (4-6 weken)
5. **Fase 5:** Bouw UI en valideer PoC (4-6 weken)

**Valideer elke fase** voordat je doorgaat naar volgende fase. Pas roadmap aan op basis van leerervaringen en feedback van gemeente Utrecht.

---

## 8. Referenties

- [OPENREGISTER-BRP-ARCHITECTUUR.md](./OPENREGISTER-BRP-ARCHITECTUUR.md) - Architectuurdocument
- [OPENREGISTER-IMPLEMENTATIE-VERGELIJKING.md](./OPENREGISTER-IMPLEMENTATIE-VERGELIJKING.md) - Implementatie vergelijking
- [VRJIBRP-DOSSIERS-API-VERGELIJKING.md](./VRJIBRP-DOSSIERS-API-VERGELIJKING.md) - vrijBRP Dossiers API vergelijking
- [VRJIBRP-ALLE-VALIDATIES.md](./VRJIBRP-ALLE-VALIDATIES.md) - Alle validaties
- [HAAL-CENTRAAL-IMPLEMENTATIE.md](./HAAL-CENTRAAL-IMPLEMENTATIE.md) - Haal Centraal implementatie

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Auteur:** AI Assistant (op basis van codebase analyse)







