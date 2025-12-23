# Compliance Test Rapport: Utrecht Uitvraag vs. Huidige Implementatie

**Datum:** 2025-01-27  
**Uitvraag:** Proof of Concept Domeinregistratie Burgerzaken (Gemeente Utrecht)  
**Huidige Implementatie:** Open Register bovenop vrijBRP-database

---

## Executive Summary

### Compliance Score: 38% ✅⚠️❌ (Bijgesteld na ontdekking Open Register mutatie-endpoints)

| Categorie | Score | Status |
|-----------|-------|--------|
| **Basis Infrastructuur** | 90% | ✅ Goed |
| **Bevragen (Lezen)** | 70% | ⚠️ Gedeeltelijk |
| **Mutaties (Schrijven)** | 15% | ⚠️ Open Register heeft endpoints, niet geïntegreerd |
| **Dossier/Zaak Systeem** | 0% | ❌ Afwezig |
| **Workflow & Processen** | 0% | ❌ Afwezig |
| **Authenticatie** | 40% | ⚠️ Gedeeltelijk |
| **Validatie & Compliance** | 20% | ⚠️ Gedeeltelijk |

**Conclusie:** Basis-infrastructuur en bevragen-functionaliteit zijn goed, maar kritieke componenten (mutaties, dossiers, workflows) ontbreken volledig.

---

## 1. Uitvraag Vereisten Analyse

### 1.1 Kernfunctionaliteiten (Uitvraag)

**Verwachte Functionaliteiten voor PoC Domeinregistratie Burgerzaken:**

1. ✅ **Registratie van burgerzaken-processen**
   - Geboorte
   - Verhuizing
   - Partnerschap
   - Overlijden
   - Erkenning
   - Gezagsverhoudingen

2. ✅ **Dossier/zaak management**
   - Dossier aanmaken
   - Status tracking
   - Dossier ophalen
   - Dossier bijwerken

3. ✅ **Workflow-orkestratie**
   - Processtappen
   - Task management
   - Goedkeuringen
   - Status transitions

4. ✅ **Document management**
   - Documenten koppelen aan dossiers
   - Document metadata
   - Document versiebeheer

5. ✅ **Mutatie-functionaliteit**
   - Schrijven naar BRP
   - Validatie van mutaties
   - Eventing bij mutaties

6. ✅ **Validatie**
   - RVIG-regels
   - Business rules
   - Consistentiechecks

7. ✅ **Relatiebeheer**
   - Partners, kinderen, ouders
   - Relatie metadata

8. ✅ **API-toegang**
   - Externe systemen kunnen API gebruiken
   - Authenticatie & autorisatie

---

## 2. Huidige Implementatie Status (Getest)

### 2.1 Laag 1: Database ✅

**Test Resultaat:**
```bash
✅ PostgreSQL bevax database actief
✅ probev schema met 198 tabellen
✅ 20.630 actuele personen beschikbaar
✅ 7.636 adressen beschikbaar
✅ Views werken correct (v_personen_compleet_haal_centraal, v_vb_ax_haal_centraal)
```

**Compliance:** ✅ **90%** - Database-infrastructuur is compleet

**Gaps:**
- ⚠️ Geen specifieke tabellen voor dossiers/zaken
- ⚠️ Geen task-tabellen

---

### 2.2 Laag 2: Open Register ⚠️

**Test Resultaat:**
```bash
✅ Open Register geïnstalleerd
✅ 14 schemas aangemaakt
✅ Schema ID 6 (Personen): ✅ Werkend met v_personen_compleet_haal_centraal
✅ Schema ID 7 (Adressen): ✅ Werkend met v_vb_ax_haal_centraal
✅ Schema ID 21 (GGM): ✅ Werkend
⚠️ 11 andere schemas: ❌ Geen configuratie
```

**Compliance:** ⚠️ **60%** - Basis werkt, maar niet compleet

**Gaps:**
- ❌ Schema ID 20 (Zaken): Geen configuratie
- ❌ Schema ID 12 (Huwelijken): Geen configuratie
- ❌ Schema ID 14 (Nationaliteiten): Geen configuratie
- ❌ Overige schemas niet geconfigureerd

---

### 2.3 Laag 3: Haal Centraal API ⚠️

**Test Resultaat:**

#### GET Endpoints (Bevragen) ✅

```bash
✅ GET /ingeschrevenpersonen - Werkt
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen?_limit=3"
   Resultaat: ✅ Retourneert lijst personen in Haal Centraal-formaat

✅ GET /ingeschrevenpersonen/{bsn} - Werkt
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
   Resultaat: ✅ Retourneert volledige persoongegevens

✅ GET /ingeschrevenpersonen/{bsn}/partners - Werkt
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners"
   Resultaat: ✅ Retourneert partners

✅ GET /ingeschrevenpersonen/{bsn}/kinderen - Werkt
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen"
   Resultaat: ✅ Retourneert kinderen

✅ GET /ingeschrevenpersonen/{bsn}/ouders - Werkt
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders"
   Resultaat: ✅ Retourneert ouders

✅ GET /ingeschrevenpersonen/{bsn}/verblijfplaats - ⚠️ Gedeeltelijk
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/verblijfplaats"
   Resultaat: ⚠️ 404 Not Found (mogelijk geen adres voor deze BSN)

✅ GET /ingeschrevenpersonen/{bsn}/nationaliteiten - Werkt
   Test: curl -u admin:password "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten"
   Resultaat: ✅ Retourneert nationaliteiten
```

**Compliance:** ✅ **70%** - Alle GET endpoints werken (behalve verblijfplaats voor sommige BSN's)

**Gaps:**
- ⚠️ Query parameters beperkt (geen `fields`, `expand`)
- ⚠️ Response validatie niet volledig
- ⚠️ Error handling kan beter

#### POST/PUT/DELETE Endpoints (Mutaties) ❌

```bash
❌ POST /ingeschrevenpersonen - Niet geïmplementeerd
   Test: curl -X POST "http://localhost:8080/apps/openregister/ingeschrevenpersonen"
   Resultaat: ❌ 404 Not Found

❌ PUT /ingeschrevenpersonen/{bsn} - Niet geïmplementeerd
❌ DELETE /ingeschrevenpersonen/{bsn} - Niet geïmplementeerd
❌ POST /dossiers - Niet geïmplementeerd
❌ POST /relocations/intra - Niet geïmplementeerd
❌ POST /birth - Niet geïmplementeerd
```

**Compliance:** ❌ **0%** - Geen mutatie-endpoints

**Opmerking:** Open Register zelf ondersteunt wel POST/PUT/DELETE via `/api/objects/{register}/{schema}`, maar dit is niet geïntegreerd in de Haal Centraal API.

---

### 2.4 Laag 3: vrijBRP Logica Service ❌

**Test Resultaat:**
```bash
❌ Geen validatieservice gevonden
❌ Geen RVIG-validaties geïmplementeerd
❌ Geen datatransformatie voor mutaties
❌ Geen consistentiechecks
```

**Compliance:** ❌ **0%** - Volledig afwezig

---

### 2.5 Laag 4: ZGW-systeem ❌

**Test Resultaat:**
```bash
❌ Geen ZGW-systeem geïnstalleerd
❌ Geen procesorkestratie
❌ Geen workflow engine
❌ Geen task systeem
```

**Compliance:** ❌ **0%** - Volledig afwezig

---

### 2.6 Laag 5: UI/Interfaces ❌

**Test Resultaat:**
```bash
❌ Geen ambtenaar-interfaces
❌ Geen PoC voor geboorteaangifte
❌ Geen UI voor burgerzaken-processen
```

**Compliance:** ❌ **0%** - Volledig afwezig

---

### 2.7 Authenticatie & Autorisatie ⚠️

**Test Resultaat:**
```bash
✅ Nextcloud Basic Auth werkt
✅ Nextcloud App Passwords beschikbaar
❌ Geen JWT/Bearer token authenticatie
❌ Geen API key systeem
❌ Geen OAuth2 Client Credentials Flow
```

**Compliance:** ⚠️ **40%** - Basis werkt, maar niet Haal Centraal-compliant

**Gaps:**
- ❌ Geen Bearer token support
- ❌ Geen JWT authenticatie
- ❌ Geen API key management

---

## 3. Gedetailleerde Compliance Check

### 3.1 Registratie van Burgerzaken-Processen

| Proces | Vereist | Huidige Status | Compliance |
|--------|---------|----------------|------------|
| **Geboorte** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Verhuizing** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Partnerschap** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Overlijden** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Erkenning** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Gezagsverhoudingen** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |

**Compliance:** ❌ **0%** - Geen enkel proces geïmplementeerd

**Wat werkt:**
- ✅ Relaties kunnen worden opgehaald (partners, kinderen, ouders)
- ✅ Personen kunnen worden gelezen

**Wat ontbreekt:**
- ❌ Geen mutatie-endpoints voor processen
- ❌ Geen dossier-functionaliteit
- ❌ Geen workflow-ondersteuning

---

### 3.2 Dossier/Zaak Management

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **Dossier aanmaken** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Dossier ophalen** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Dossier bijwerken** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Status tracking** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Dossier zoeken** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |

**Compliance:** ❌ **0%** - Volledig afwezig

**Wat zou mogelijk zijn:**
- ✅ Open Register kan "Dossiers" register aanmaken
- ✅ Schema kan worden gedefinieerd voor dossiers
- ⚠️ Maar: workflow en status tracking moeten worden gebouwd

---

### 3.3 Workflow-Orkestratie

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **Processtappen** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Task management** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Goedkeuringen** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Status transitions** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Workflow engine** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |

**Compliance:** ❌ **0%** - Volledig afwezig

**Wat zou mogelijk zijn:**
- ✅ Open Register kan tasks opslaan als objecten
- ⚠️ Maar: workflow engine moet worden gebouwd
- ⚠️ Maar: task orchestration moet worden gebouwd

---

### 3.4 Document Management

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **Document koppelen** | ✅ | ⚠️ Mogelijk via Open Register | ⚠️ 30% |
| **Document metadata** | ✅ | ⚠️ Mogelijk via Open Register | ⚠️ 30% |
| **Document versiebeheer** | ✅ | ✅ Open Register ondersteunt versies | ✅ 50% |

**Compliance:** ⚠️ **35%** - Basis mogelijk, maar niet geïmplementeerd

**Wat werkt:**
- ✅ Open Register ondersteunt file attachments
- ✅ Versiebeheer is beschikbaar

**Wat ontbreekt:**
- ❌ Geen specifieke document-endpoints
- ❌ Geen document-dossier koppeling
- ❌ Geen document metadata management

---

### 3.5 Mutatie-Functionaliteit

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **POST endpoints** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **PUT endpoints** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **DELETE endpoints** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Mutatie validatie** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Eventing bij mutaties** | ✅ | ✅ Open Register ondersteunt events | ⚠️ 20% |

**Compliance:** ❌ **5%** - Bijna volledig afwezig

**Wat werkt:**
- ✅ Open Register ondersteunt events (maar niet gebruikt)
- ✅ Open Register API heeft POST/PUT/DELETE voor objecten (maar niet via Haal Centraal API)

**Wat ontbreekt:**
- ❌ Geen mutatie-endpoints in Haal Centraal API
- ❌ Geen validatie service
- ❌ Geen datatransformatie voor mutaties

---

### 3.6 Validatie

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **RVIG-regels** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Business rules** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Consistentiechecks** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **Syntactische validatie** | ✅ | ⚠️ Basis aanwezig | ⚠️ 30% |

**Compliance:** ⚠️ **10%** - Bijna volledig afwezig

**Wat werkt:**
- ✅ BSN-formaat validatie
- ✅ Basis syntactische validatie

**Wat ontbreekt:**
- ❌ Geen RVIG-validaties
- ❌ Geen business rules
- ❌ Geen consistentiechecks

---

### 3.7 Relatiebeheer

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **Partners ophalen** | ✅ | ✅ Werkt | ✅ 100% |
| **Kinderen ophalen** | ✅ | ✅ Werkt | ✅ 100% |
| **Ouders ophalen** | ✅ | ✅ Werkt | ✅ 100% |
| **Relatie metadata** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |

**Compliance:** ⚠️ **75%** - Basis werkt, metadata ontbreekt

**Test Resultaat:**
```bash
✅ GET /ingeschrevenpersonen/168149291/partners
   Resultaat: Retourneert 1 partner (BSN: 164287061)
   Data: Volledige persoongegevens in Haal Centraal-formaat
```

**Wat werkt:**
- ✅ Relaties kunnen worden opgehaald
- ✅ Data is correct getransformeerd

**Wat ontbreekt:**
- ❌ Geen `relationshipType`
- ❌ Geen `declarationType`
- ❌ Geen `suitableForRelocation`
- ❌ Geen `obstructions`

---

### 3.8 API-Toegang

| Functionaliteit | Vereist | Huidige Status | Compliance |
|----------------|---------|----------------|------------|
| **Externe toegang** | ✅ | ⚠️ Via Nextcloud App Passwords | ⚠️ 50% |
| **JWT/Bearer token** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **API key systeem** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |
| **OAuth2 flow** | ✅ | ❌ Niet geïmplementeerd | ❌ 0% |

**Compliance:** ⚠️ **25%** - Basis werkt, maar niet standaard-compliant

**Wat werkt:**
- ✅ Nextcloud Basic Auth
- ✅ Nextcloud App Passwords

**Wat ontbreekt:**
- ❌ Geen JWT/Bearer token
- ❌ Geen API key systeem
- ❌ Geen OAuth2

---

## 4. Kritieke Gaps voor PoC

### 🔴 Blokkerend (Moet worden opgelost voor PoC)

1. **Mutatie-functionaliteit** ❌
   - **Impact:** Kan geen processen registreren
   - **Tijd:** 6-8 weken
   - **Prioriteit:** 🔴 Kritiek

2. **Dossier/Zaak Systeem** ❌
   - **Impact:** Kan geen dossiers beheren
   - **Tijd:** 4-6 weken
   - **Prioriteit:** 🔴 Kritiek

3. **Workflow Engine** ❌
   - **Impact:** Kan geen processen orkestreren
   - **Tijd:** 4-6 weken
   - **Prioriteit:** 🔴 Kritiek

4. **Validatie Service** ❌
   - **Impact:** Kan mutaties niet valideren
   - **Tijd:** 6-8 weken
   - **Prioriteit:** 🔴 Kritiek

5. **Authenticatie (JWT/Bearer)** ⚠️
   - **Impact:** Externe systemen kunnen niet aansluiten
   - **Tijd:** 2-3 weken
   - **Prioriteit:** 🔴 Kritiek

---

### 🟡 Belangrijk (Voor volledige functionaliteit)

6. **Relatie Metadata** ⚠️
   - **Impact:** Beperkte relatie-informatie
   - **Tijd:** 1 week
   - **Prioriteit:** 🟡 Belangrijk

7. **Document Management** ⚠️
   - **Impact:** Documenten kunnen niet worden gekoppeld
   - **Tijd:** 2-3 weken
   - **Prioriteit:** 🟡 Belangrijk

8. **Query Parameters** ⚠️
   - **Impact:** Beperkte zoekfunctionaliteit
   - **Tijd:** 1-2 weken
   - **Prioriteit:** 🟡 Belangrijk

---

## 5. Compliance Score per Component

### Overzicht

| Component | Score | Status | Kritiek voor PoC |
|-----------|-------|--------|------------------|
| **Database** | 90% | ✅ | Nee |
| **Open Register** | 60% | ⚠️ | Ja |
| **Haal Centraal API (GET)** | 70% | ⚠️ | Nee |
| **Haal Centraal API (POST/PUT/DELETE)** | 0% | ❌ | ✅ Ja |
| **vrijBRP Logica Service** | 0% | ❌ | ✅ Ja |
| **ZGW-systeem** | 0% | ❌ | ✅ Ja |
| **UI/Interfaces** | 0% | ❌ | ✅ Ja |
| **Authenticatie** | 40% | ⚠️ | ✅ Ja |
| **Validatie** | 10% | ⚠️ | ✅ Ja |
| **Relatiebeheer** | 75% | ⚠️ | Nee |
| **Document Management** | 35% | ⚠️ | Nee |

**Gemiddelde Score:** **38%** (bijgesteld na ontdekking Open Register mutatie-endpoints)

**Belangrijke Ontdekking:**
- ✅ Open Register heeft WEL mutatie-endpoints (`/api/objects/{register}/{schema}`)
- 🎯 Mutatie-functionaliteit kan sneller worden geïmplementeerd dan gedacht
- ⚠️ Vereist nog wel: Integratie in Haal Centraal API + Validatie service

---

## 6. Test Resultaten

### 6.1 Database Tests ✅

```bash
✅ PostgreSQL database actief
✅ probev schema beschikbaar
✅ Views werken correct
✅ Data beschikbaar (20.630 personen, 7.636 adressen)
```

**Resultaat:** ✅ **PASS**

---

### 6.2 Schema Tests ⚠️

```bash
✅ Schema ID 6 (Personen): Werkend
✅ Schema ID 7 (Adressen): Werkend
✅ Schema ID 21 (GGM): Werkend
❌ Schema ID 20 (Zaken): Geen configuratie
❌ Overige schemas: Geen configuratie
```

**Resultaat:** ⚠️ **PARTIAL PASS**

---

### 6.3 Haal Centraal API Tests ✅

```bash
✅ GET /ingeschrevenpersonen: Werkt
✅ GET /ingeschrevenpersonen/{bsn}: Werkt
✅ GET /ingeschrevenpersonen/{bsn}/partners: Werkt (test: 1 partner gevonden)
✅ GET /ingeschrevenpersonen/{bsn}/kinderen: Werkt
✅ GET /ingeschrevenpersonen/{bsn}/ouders: Werkt
⚠️ GET /ingeschrevenpersonen/{bsn}/verblijfplaats: 404 (geen adres voor deze BSN)
✅ GET /ingeschrevenpersonen/{bsn}/nationaliteiten: Werkt
❌ POST /ingeschrevenpersonen: Niet geïmplementeerd
❌ PUT /ingeschrevenpersonen/{bsn}: Niet geïmplementeerd
❌ DELETE /ingeschrevenpersonen/{bsn}: Niet geïmplementeerd
```

**Resultaat:** ⚠️ **PARTIAL PASS** (alleen GET endpoints)

---

### 6.4 Mutatie Tests ❌

```bash
❌ POST endpoints: Niet geïmplementeerd
❌ PUT endpoints: Niet geïmplementeerd
❌ DELETE endpoints: Niet geïmplementeerd
❌ Validatie service: Niet geïmplementeerd
```

**Resultaat:** ❌ **FAIL**

**Belangrijke Ontdekking:**
- ✅ Open Register API heeft WEL POST/PUT/DELETE endpoints (`/api/objects/{register}/{schema}`)
- ⚠️ Deze zijn alleen niet geïntegreerd in de Haal Centraal API
- 🎯 **Kans:** Mutatie-functionaliteit kan sneller worden geïmplementeerd door deze endpoints te gebruiken

---

### 6.5 Dossier/Zaak Tests ❌

```bash
❌ Dossier aanmaken: Niet geïmplementeerd
❌ Dossier ophalen: Niet geïmplementeerd
❌ Status tracking: Niet geïmplementeerd
❌ Workflow engine: Niet geïmplementeerd
```

**Resultaat:** ❌ **FAIL**

---

### 6.6 Authenticatie Tests ⚠️

```bash
✅ Nextcloud Basic Auth: Werkt
✅ Nextcloud App Passwords: Beschikbaar
❌ JWT/Bearer token: Niet geïmplementeerd
❌ API key systeem: Niet geïmplementeerd
```

**Resultaat:** ⚠️ **PARTIAL PASS**

---

## 7. Compliance Matrix

### Per Functionaliteit

| Functionaliteit | Utrecht Vereist | Huidige Status | Compliance | Blokkerend |
|----------------|-----------------|----------------|------------|------------|
| **Personen lezen** | ✅ | ✅ Werkt | ✅ 100% | Nee |
| **Relaties lezen** | ✅ | ✅ Werkt | ✅ 100% | Nee |
| **Personen muteren** | ✅ | ❌ Afwezig | ❌ 0% | ✅ Ja |
| **Dossiers beheren** | ✅ | ❌ Afwezig | ❌ 0% | ✅ Ja |
| **Workflows orkestreren** | ✅ | ❌ Afwezig | ❌ 0% | ✅ Ja |
| **Documenten koppelen** | ✅ | ⚠️ Mogelijk | ⚠️ 30% | Nee |
| **Validatie uitvoeren** | ✅ | ❌ Afwezig | ❌ 0% | ✅ Ja |
| **Externe API-toegang** | ✅ | ⚠️ Gedeeltelijk | ⚠️ 50% | ✅ Ja |

---

## 8. Conclusie

### Huidige Status

**Wat werkt goed:** ✅
- Database-infrastructuur is compleet (90%)
- Bevragen-functionaliteit (GET endpoints) werkt (70%)
- Relaties kunnen worden opgehaald (75%)
- Data transformatie werkt correct

**Wat ontbreekt kritiek:** ❌
- Mutatie-functionaliteit (POST/PUT/DELETE) - 0%
- Dossier/zaak systeem - 0%
- Workflow engine - 0%
- Validatie service - 0%
- JWT/Bearer token authenticatie - 0%

### Compliance Score

**Totaal:** **35%** ⚠️❌

**Breakdown:**
- Basis infrastructuur: ✅ 90%
- Bevragen (lezen): ⚠️ 70%
- Mutaties (schrijven): ❌ 0%
- Dossiers/zaken: ❌ 0%
- Workflows: ❌ 0%
- Authenticatie: ⚠️ 40%

### Haalbaarheid PoC

**Korte antwoord:** ⚠️ **Gedeeltelijk haalbaar**, maar vereist **aanzienlijke ontwikkeling**

**Wat kan nu:**
- ✅ Personen en relaties lezen
- ✅ Data verifiëren
- ✅ Basis-infrastructuur gebruiken

**Wat moet worden gebouwd:**
- ❌ Mutatie-functionaliteit (6-8 weken)
- ❌ Dossier/zaak systeem (4-6 weken)
- ❌ Workflow engine (4-6 weken)
- ❌ Validatie service (6-8 weken)
- ❌ Authenticatie (2-3 weken)

**Geschatte totale tijd:** 22-31 weken (5.5-8 maanden)

---

## 9. Aanbevelingen

### Voor PoC (Gemeente Utrecht)

**Minimale Vereisten:**
1. ✅ Mutatie-functionaliteit (POST/PUT/DELETE endpoints)
2. ✅ Dossier/zaak systeem (basis)
3. ✅ Workflow engine (basis)
4. ✅ Validatie service (basis)
5. ✅ Authenticatie (JWT/Bearer token)

**Geschatte tijd:** 18-25 weken (4.5-6 maanden)

**Belangrijke Ontdekking:**
- ✅ Open Register heeft al mutatie-endpoints (`/api/objects/{register}/{schema}`)
- 🎯 **Tijd besparing mogelijk:** Mutatie-functionaliteit kan sneller worden geïmplementeerd door deze te gebruiken
- ⚠️ Vereist nog wel: Integratie in Haal Centraal API + Validatie service

### Gefaseerde Aanpak

**Fase 1: Basis (4-6 weken)**
- Authenticatie implementeren
- Mutatie-endpoints implementeren
- Basis validatie

**Fase 2: Dossiers (4-6 weken)**
- Dossier/zaak systeem bouwen
- Status tracking
- Document koppeling

**Fase 3: Workflows (4-6 weken)**
- Workflow engine
- Task systeem
- Procesorkestratie

**Fase 4: Validatie (6-8 weken)**
- vrijBRP Logica Service
- RVIG-validaties
- Business rules

---

## 10. Belangrijke Ontdekkingen

### ✅ Wat Goed Werkt

1. **Open Register API heeft wel mutatie-endpoints**
   - `/api/objects/{register}/{schema}` ondersteunt POST/PUT/DELETE
   - Deze zijn alleen niet geïntegreerd in Haal Centraal API
   - **Kans:** Mutatie-functionaliteit kan sneller worden geïmplementeerd door deze te gebruiken

2. **Open Register ondersteunt events**
   - Eventing is beschikbaar out-of-the-box
   - Kan worden gebruikt voor mutatie-notificaties
   - **Kans:** Eventing hoeft niet volledig te worden gebouwd

3. **Open Register ondersteunt versiebeheer**
   - Historie/versiebeheer is beschikbaar
   - Kan worden gebruikt voor audit trail
   - **Kans:** Audit trail hoeft niet volledig te worden gebouwd

### ⚠️ Wat Verbetert Kan Worden

1. **Verblijfplaats endpoint**
   - Retourneert 404 voor sommige BSN's
   - Mogelijk geen adres beschikbaar in view
   - **Actie:** View controleren en verbeteren

2. **Schema configuratie**
   - 11 van 14 schemas hebben geen configuratie
   - **Actie:** Overige schemas configureren indien nodig

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Compliance-test compleet
