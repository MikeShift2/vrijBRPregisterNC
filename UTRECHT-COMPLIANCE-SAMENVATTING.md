# Compliance Samenvatting: Utrecht Uitvraag

**Datum:** 2025-01-27  
**Compliance Score:** **38%** ✅⚠️❌

---

## Quick Overview

### ✅ Wat Werkt (70-100%)

1. **Database-infrastructuur** - 90% ✅
   - PostgreSQL bevax database actief
   - 20.630 personen, 7.636 adressen beschikbaar
   - Views werken correct

2. **Bevragen (Lezen)** - 70% ✅
   - Alle GET endpoints werken
   - Personen, partners, kinderen, ouders kunnen worden opgehaald
   - Data transformatie werkt correct

3. **Relatiebeheer** - 75% ✅
   - Partners, kinderen, ouders kunnen worden opgehaald
   - Data is correct getransformeerd

### ⚠️ Wat Gedeeltelijk Werkt (30-50%)

4. **Open Register** - 60% ⚠️
   - 3 van 14 schemas geconfigureerd
   - Basis werkt, maar niet compleet

5. **Authenticatie** - 40% ⚠️
   - Nextcloud Basic Auth werkt
   - Nextcloud App Passwords beschikbaar
   - Geen JWT/Bearer token

6. **Document Management** - 35% ⚠️
   - Open Register ondersteunt files
   - Versiebeheer beschikbaar
   - Geen specifieke document-endpoints

### ❌ Wat Ontbreekt (0-15%)

7. **Mutaties (Schrijven)** - 15% ❌
   - ⚠️ **BELANGRIJK:** Open Register heeft WEL mutatie-endpoints (`/api/objects/{register}/{schema}`)
   - ❌ Niet geïntegreerd in Haal Centraal API
   - ❌ Geen validatie service

8. **Dossier/Zaak Systeem** - 0% ❌
   - Geen dossier-functionaliteit
   - Geen status tracking
   - Geen workflow engine

9. **Workflow & Processen** - 0% ❌
   - Geen workflow engine
   - Geen task systeem
   - Geen procesorkestratie

10. **Validatie Service** - 0% ❌
    - Geen RVIG-validaties
    - Geen business rules
    - Geen consistentiechecks

---

## Belangrijkste Ontdekkingen

### ✅ Positieve Ontdekkingen

1. **Open Register heeft al mutatie-endpoints!**
   - `POST /api/objects/{register}/{schema}` - Werkt ✅
   - `PUT /api/objects/{register}/{schema}/{uuid}` - Beschikbaar
   - `DELETE /api/objects/{register}/{schema}/{uuid}` - Beschikbaar
   - **Impact:** Mutatie-functionaliteit kan sneller worden geïmplementeerd

2. **Open Register ondersteunt events**
   - Eventing is beschikbaar out-of-the-box
   - Kan worden gebruikt voor mutatie-notificaties

3. **Open Register ondersteunt versiebeheer**
   - Historie/versiebeheer is beschikbaar
   - Kan worden gebruikt voor audit trail

### ⚠️ Verbeterpunten

1. **Verblijfplaats endpoint**
   - Retourneert 404 voor sommige BSN's
   - Mogelijk geen adres beschikbaar in view

2. **Schema configuratie**
   - 11 van 14 schemas hebben geen configuratie
   - Schema ID 20 (Zaken) niet geconfigureerd

---

## Kritieke Gaps voor PoC

### 🔴 Blokkerend (Moet worden opgelost)

1. **Mutatie-functionaliteit** - 15% → 100%
   - **Huidige status:** Open Register heeft endpoints, maar niet geïntegreerd
   - **Vereist:** Integratie in Haal Centraal API + Validatie service
   - **Tijd:** 4-6 weken (sneller dan gedacht!)

2. **Dossier/Zaak Systeem** - 0% → 100%
   - **Vereist:** Register aanmaken + Schema definiëren + Status tracking
   - **Tijd:** 4-6 weken

3. **Workflow Engine** - 0% → 100%
   - **Vereist:** Task systeem + Procesorkestratie
   - **Tijd:** 4-6 weken

4. **Validatie Service** - 0% → 100%
   - **Vereist:** vrijBRP Logica Service + RVIG-validaties
   - **Tijd:** 6-8 weken

5. **Authenticatie (JWT/Bearer)** - 40% → 100%
   - **Vereist:** JWT/Bearer token implementatie
   - **Tijd:** 2-3 weken

---

## Compliance Score Breakdown

| Component | Score | Status | Kritiek |
|-----------|-------|--------|---------|
| Database | 90% | ✅ | Nee |
| Open Register | 60% | ⚠️ | Ja |
| Haal Centraal API (GET) | 70% | ✅ | Nee |
| Haal Centraal API (POST/PUT/DELETE) | 15% | ⚠️ | ✅ Ja |
| vrijBRP Logica Service | 0% | ❌ | ✅ Ja |
| ZGW-systeem | 0% | ❌ | ✅ Ja |
| UI/Interfaces | 0% | ❌ | ✅ Ja |
| Authenticatie | 40% | ⚠️ | ✅ Ja |
| Validatie | 10% | ⚠️ | ✅ Ja |
| Relatiebeheer | 75% | ✅ | Nee |
| Document Management | 35% | ⚠️ | Nee |

**Gemiddelde:** **38%**

---

## Aanbevelingen

### Korte Termijn (Quick Wins)

1. **Gebruik Open Register mutatie-endpoints**
   - Integreer `/api/objects/{register}/{schema}` in Haal Centraal API
   - Voeg validatie toe
   - **Tijd besparing:** 2-3 weken

2. **Configureer overige schemas**
   - Schema ID 20 (Zaken) voor dossiers
   - Schema ID 12 (Huwelijken) voor partnerschappen
   - **Tijd:** 1-2 weken

### Middellange Termijn (Essentieel)

3. **Implementeer mutatie-functionaliteit**
   - Integreer Open Register endpoints in Haal Centraal API
   - Voeg validatie service toe
   - **Tijd:** 4-6 weken

4. **Bouw dossier/zaak systeem**
   - Maak "Dossiers" register aan
   - Implementeer status tracking
   - **Tijd:** 4-6 weken

5. **Implementeer workflow engine**
   - Task systeem
   - Procesorkestratie
   - **Tijd:** 4-6 weken

---

## Conclusie

**Huidige Compliance:** **38%** ⚠️❌

**Wat werkt:**
- ✅ Basis-infrastructuur (90%)
- ✅ Bevragen-functionaliteit (70%)
- ✅ Relatiebeheer (75%)

**Wat ontbreekt:**
- ❌ Mutatie-functionaliteit (15% - Open Register heeft endpoints!)
- ❌ Dossier/zaak systeem (0%)
- ❌ Workflow engine (0%)
- ❌ Validatie service (0%)

**Belangrijkste Ontdekking:**
- ✅ Open Register heeft al mutatie-endpoints - dit bespaart tijd!
- 🎯 Mutatie-functionaliteit kan sneller worden geïmplementeerd dan gedacht

**Geschatte tijd voor PoC:** 18-25 weken (4.5-6 maanden)

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27







