# Basis Infrastructuur naar 100% - Actieplan

**Huidige Status:** 90% ✅  
**Doel:** 100% ✅

---

## Huidige Status (90%)

### ✅ Wat Werkt

1. **PostgreSQL Database** ✅
   - Database `bevax` actief
   - Schema `probev` met 235 tabellen
   - ~2 miljoen rijen data
   - 20.630 actuele personen
   - 7.636 adressen

2. **SQL Views** ✅
   - `v_personen_compleet_haal_centraal` - Werkt
   - `v_vb_ax_haal_centraal` - Werkt
   - `v_inw_ax_haal_centraal` - Werkt

3. **Open Register** ✅
   - Geïnstalleerd en geconfigureerd
   - Source ID 1 gekoppeld aan bevax database
   - Register ID 1 en 2 aangemaakt

4. **Schema Configuratie** ✅
   - Schema ID 6 (Personen): Geconfigureerd
   - Schema ID 7 (Adressen): Geconfigureerd
   - Schema ID 21 (GGM): Geconfigureerd

---

## Wat Ontbreekt (10%)

### Gap 1: Dossier/Zaak Tabellen ❌

**Probleem:**
- Geen specifieke tabellen voor dossiers/zaken in `probev` schema
- Geen structuur voor dossier management
- Geen status tracking tabellen

**Impact:** 🔴 **Kritiek** - Kan geen dossiers beheren

**Vereist:**
- Tabellen of views voor dossiers/zaken
- Status tracking structuur
- Relatie tussen dossiers en personen

**Opties:**

#### Optie A: Open Register als Dossier Systeem (Aanbevolen)
- ✅ Geen extra database tabellen nodig
- ✅ Gebruik Open Register "Dossiers" register
- ✅ Schema ID 20 (Zaken) configureren
- ✅ Status als veld in object

**Voordelen:**
- ✅ Geen database wijzigingen nodig
- ✅ Historie/versiebeheer out-of-the-box
- ✅ Eventing beschikbaar
- ✅ Common Ground-compliant

**Acties:**
1. Configureer Schema ID 20 (Zaken)
2. Maak "Dossiers" register aan (of gebruik bestaand)
3. Definieer schema voor dossier types

**Tijd:** 2-4 uur

---

#### Optie B: Database Tabellen Aanmaken
- ⚠️ Extra database tabellen nodig
- ⚠️ Moet worden gesynchroniseerd met Open Register

**Nadelen:**
- ⚠️ Extra complexiteit
- ⚠️ Synchronisatie vereist
- ⚠️ Niet Common Ground-compliant

**Niet aanbevolen** - Open Register is beter geschikt

---

### Gap 2: Task Tabellen ❌

**Probleem:**
- Geen specifieke tabellen voor tasks in `probev` schema
- Geen structuur voor task management
- Geen workflow tracking

**Impact:** 🔴 **Kritiek** - Kan geen workflows orkestreren

**Vereist:**
- Tabellen of views voor tasks
- Status tracking voor tasks
- Relatie tussen tasks en dossiers

**Opties:**

#### Optie A: Open Register als Task Systeem (Aanbevolen)
- ✅ Geen extra database tabellen nodig
- ✅ Gebruik Open Register "Tasks" register
- ✅ Maak nieuw schema voor tasks
- ✅ Status als veld in object

**Voordelen:**
- ✅ Geen database wijzigingen nodig
- ✅ Historie/versiebeheer out-of-the-box
- ✅ Eventing beschikbaar
- ✅ Common Ground-compliant

**Acties:**
1. Maak nieuw schema voor Tasks aan
2. Maak "Tasks" register aan
3. Definieer schema voor task types

**Tijd:** 2-4 uur

---

#### Optie B: Database Tabellen Aanmaken
- ⚠️ Extra database tabellen nodig
- ⚠️ Moet worden gesynchroniseerd met Open Register

**Niet aanbevolen** - Open Register is beter geschikt

---

### Gap 3: Overige Schema Configuratie ⚠️

**Probleem:**
- 11 van 14 schemas hebben geen configuratie
- Schema ID 20 (Zaken) niet geconfigureerd
- Overige schemas niet geconfigureerd

**Impact:** 🟡 **Belangrijk** - Beperkte functionaliteit

**Vereist:**
- Schema ID 20 (Zaken) configureren voor dossiers
- Overige schemas configureren indien nodig

**Acties:**
1. Configureer Schema ID 20 (Zaken)
2. Bepaal welke andere schemas nodig zijn
3. Configureer indien nodig

**Tijd:** 2-4 uur per schema

---

## Actieplan naar 100%

### Stap 1: Schema ID 20 (Zaken) Configureren 🔴

**Doel:** Dossier/zaak systeem mogelijk maken

**Acties:**
1. ✅ Bepaal welke velden nodig zijn voor dossiers
2. ✅ Maak schema properties definitie
3. ✅ Configureer schema in Open Register
4. ✅ Test dossier-functionaliteit

**Schema Properties Voorbeeld:**
```json
{
  "dossier_id": {"type": "string"},
  "reference_id": {"type": "string"},
  "dossier_type": {"type": "string"},
  "status": {"type": "string"},
  "bsn": {"type": "string"},
  "data": {"type": "object"},
  "created_at": {"type": "string", "format": "date-time"},
  "updated_at": {"type": "string", "format": "date-time"}
}
```

**Tijd:** 2-4 uur

---

### Stap 2: Tasks Schema Aanmaken 🔴

**Doel:** Task management mogelijk maken

**Acties:**
1. ✅ Maak nieuw schema voor Tasks aan
2. ✅ Definieer schema properties
3. ✅ Maak "Tasks" register aan
4. ✅ Koppel schema aan register
5. ✅ Test task-functionaliteit

**Schema Properties Voorbeeld:**
```json
{
  "task_id": {"type": "string"},
  "dossier_id": {"type": "string"},
  "task_type": {"type": "string"},
  "status": {"type": "string"},
  "bsn": {"type": "string"},
  "description": {"type": "string"},
  "created_at": {"type": "string", "format": "date-time"},
  "due_date": {"type": "string", "format": "date-time"},
  "completed_at": {"type": "string", "format": "date-time"}
}
```

**Tijd:** 2-4 uur

---

### Stap 3: Overige Schemas Evalueren 🟡

**Doel:** Bepalen welke schemas nodig zijn

**Acties:**
1. ✅ Evalueer welke schemas nodig zijn voor PoC
2. ✅ Configureer kritieke schemas
3. ✅ Documenteer welke schemas optioneel zijn

**Kritieke Schemas voor PoC:**
- Schema ID 20 (Zaken) - 🔴 Kritiek
- Schema ID 12 (Huwelijken) - 🟡 Belangrijk
- Schema ID 14 (Nationaliteiten) - 🟡 Belangrijk

**Optionele Schemas:**
- Schema ID 10 (Erkenningen) - 🟢 Optioneel
- Schema ID 11 (Gezagsverhoudingen) - 🟢 Optioneel
- Schema ID 17 (Reisdocumenten) - 🟢 Optioneel

**Tijd:** 2-4 uur per schema (indien nodig)

---

## Concreet Actieplan

### Directe Acties (Vandaag/Deze Week)

1. **Schema ID 20 (Zaken) configureren** 🔴
   - **Waarom:** Vereist voor dossier-functionaliteit
   - **Hoe:** Update schema properties en configuratie
   - **Tijd:** 2-4 uur
   - **Impact:** Maakt dossier-functionaliteit mogelijk

2. **Tasks Schema aanmaken** 🔴
   - **Waarom:** Vereist voor workflow-functionaliteit
   - **Hoe:** Maak nieuw schema en register aan
   - **Tijd:** 2-4 uur
   - **Impact:** Maakt task management mogelijk

### Optionele Acties (Komende Weken)

3. **Overige schemas configureren** 🟡
   - Schema ID 12 (Huwelijken)
   - Schema ID 14 (Nationaliteiten)
   - Schema ID 17 (Reisdocumenten)

**Tijd:** 2-4 uur per schema

---

## Success Criteria voor 100%

### Database Infrastructuur

✅ PostgreSQL database actief  
✅ probev schema met alle benodigde tabellen  
✅ Views werken correct  
✅ Data beschikbaar  

### Open Register

✅ Open Register geïnstalleerd  
✅ Alle kritieke schemas geconfigureerd  
✅ Schema ID 20 (Zaken) geconfigureerd  
✅ Tasks schema aangemaakt  
✅ Registers gekoppeld aan schemas  

### Functionaliteit

✅ Dossiers kunnen worden aangemaakt  
✅ Tasks kunnen worden aangemaakt  
✅ Status tracking werkt  
✅ Historie/versiebeheer werkt  

---

## Geschatte Tijd

| Actie | Tijd | Prioriteit |
|-------|------|------------|
| Schema ID 20 configureren | 2-4 uur | 🔴 Kritiek |
| Tasks schema aanmaken | 2-4 uur | 🔴 Kritiek |
| Overige schemas configureren | 2-4 uur per schema | 🟡 Belangrijk |

**Totaal voor 100%:** 4-8 uur (0.5-1 dag)

---

## Conclusie

**Wat moet er nog gebeuren:**

1. **Schema ID 20 (Zaken) configureren** 🔴 (2-4 uur)
   - Maakt dossier-functionaliteit mogelijk
   - Geen database wijzigingen nodig
   - Gebruik Open Register als dossier systeem

2. **Tasks Schema aanmaken** 🔴 (2-4 uur)
   - Maakt task management mogelijk
   - Geen database wijzigingen nodig
   - Gebruik Open Register als task systeem

3. **Overige schemas configureren** 🟡 (optioneel, 2-4 uur per schema)
   - Schema ID 12 (Huwelijken)
   - Schema ID 14 (Nationaliteiten)
   - Schema ID 17 (Reisdocumenten)

**Belangrijkste Inzicht:**
- ✅ **Geen extra database tabellen nodig!**
- ✅ Open Register kan worden gebruikt als dossier- en task-systeem
- ✅ Dit bespaart tijd en complexiteit

**Van 90% naar 100%:** 4-8 uur werk (0.5-1 dag)

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Actieplan klaar voor uitvoering







