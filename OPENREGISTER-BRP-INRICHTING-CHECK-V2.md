# Open Register BRP Inrichting - Herziening Analyse

**Datum:** 2025-01-27  
**Conclusie:** De architectuur is **grotendeels correct geïmplementeerd**, maar **schemas zijn niet volledig gekoppeld**

---

## 🎯 Kernbevinding

Open Register genereert **automatisch** REST API endpoints in het formaat:
```
/{registernaam}/{schemanaam}
```

**Voorbeeld:**
- Register: `vrijBRPpersonen` (ID 2)
- Schema: `Personen` (ID 6)
- Automatisch endpoint: `/vrijbrppersonen/personen`

Dit komt **overeen** met de documentatie architectuur!

---

## 📊 Huidige Situatie vs Documentatie

### Documentatie Vereisten

```
│    Open Registers (API)   │
│ - /registers/inwoners     │
│ - /registers/huwelijken   │
│ - /registers/reisdoc      │
│ - /registers/kiesrecht    │
```

### Huidige Implementatie

**Register 2: vrijBRPpersonen**
- ✅ Gekoppelde schemas: `[6]` (alleen Personen)
- ✅ Automatisch endpoint: `/vrijbrppersonen/personen`

**Niet-gekoppelde schemas:**
- ❌ Schema 12: Huwelijken → **GEEN register gekoppeld**
- ❌ Schema 14: Nationaliteiten → **GEEN register gekoppeld**
- ❌ Schema 17: Reisdocumenten → **GEEN register gekoppeld**
- ❌ Schema ?: Kiesrecht → **GEEN register gekoppeld**

---

## 🔍 Wat Ontbreekt

### Probleem: Schemas niet gekoppeld aan register

De BRP-schemas bestaan wel, maar zijn niet gekoppeld aan een register. Daarom hebben ze **geen automatische API endpoints**.

**Huidige toestand:**
```sql
SELECT id, title, schemas FROM oc_openregister_registers WHERE id = 2;
-- Result: id=2, title='vrijBRPpersonen', schemas='[6]'
```

**Gewenst:**
```sql
-- Register 2 moet alle BRP-schemas bevatten:
schemas = '[6, 12, 14, 17, ...]'
```

---

## ✅ Oplossing: Koppel Alle BRP-Schemas aan Register

### Stap 1: Identificeer Alle BRP-Schemas

```sql
SELECT id, title FROM oc_openregister_schemas 
WHERE title IN (
  'Personen',           -- cat 1 (Inwoners)
  'Huwelijken',         -- cat 5  
  'Nationaliteiten',    -- cat 4
  'Reisdocumenten',     -- cat 12
  'Kiesrecht',          -- cat 13
  'Verblijfplaats',     -- cat 8
  'Ouders',             -- cat 2+3
  'Kinderen',           -- cat 9
  'Overlijden',         -- cat 6
  'Inschrijving',       -- cat 7
  'Verblijfstitel',     -- cat 10
  'Gezag'               -- cat 11
)
ORDER BY id;
```

### Stap 2: Koppel Schemas aan Register 2

```sql
-- Voorbeeld: voeg schema IDs toe aan register 2
UPDATE oc_openregister_registers 
SET schemas = JSON_ARRAY(6, 12, 14, 17) 
WHERE id = 2;
```

### Stap 3: Automatische Endpoints

Na het koppelen genereert Open Register automatisch:
- ✅ `/vrijbrppersonen/personen` (Inwoners)
- ✅ `/vrijbrppersonen/huwelijken` 
- ✅ `/vrijbrppersonen/nationaliteiten`
- ✅ `/vrijbrppersonen/reisdocumenten`
- ✅ `/vrijbrppersonen/kiesrecht`
- etc.

---

## 🏗️ Architectuur Mapping

### Documentatie → Open Register

| Documentatie | Open Register Equivalent | Status |
|-------------|--------------------------|--------|
| `/registers/inwoners` | `/vrijbrppersonen/personen` | ✅ **WERKT** |
| `/registers/huwelijken` | `/vrijbrppersonen/huwelijken` | ❌ **TE IMPLEMENTEREN** |
| `/registers/reisdoc` | `/vrijbrppersonen/reisdocumenten` | ❌ **TE IMPLEMENTEREN** |
| `/registers/kiesrecht` | `/vrijbrppersonen/kiesrecht` | ❌ **TE IMPLEMENTEREN** |

### Interpretatie

De documentatie gebruikt `/registers/X` als **conceptueel voorbeeld**.  
Open Register implementeert dit als `/{registernaam}/{schemanaam}`.

**Dit is correct!** De functionaliteit komt overeen, alleen de URL-structuur is iets anders.

---

## 📋 Volledige Checklist

### ✅ Wat WEL Goed Is

1. **Architectuur:** ✅ Open Register ondersteunt exact wat de documentatie beschrijft
2. **Automatische endpoints:** ✅ `/{register}/{schema}` patroon werkt
3. **Haal Centraal API:** ✅ Volledig geïmplementeerd (`/ingeschrevenpersonen`)
4. **Database structuur:** ✅ Probev schema volgens PL-AX specificatie
5. **CRUD functionaliteit:** ✅ Via `/api/objects/{register}/{schema}/{id}`
6. **Audit trails:** ✅ Logging aanwezig
7. **Relaties:** ✅ Via `_embedded` objecten

### ⚠️ Wat Moet Worden Aangepast

1. **Schema-koppeling:** ❌ Koppel alle BRP-schemas aan register 2
2. **Schema definitie:** ⚠️ Controleer of schemas correct verwijzen naar probev-tabellen
3. **Data import:** ⚠️ Importeer data voor alle schemas (niet alleen Personen)
4. **Eventpublicatie:** ❌ Nog niet geïmplementeerd (vereist volgens documentatie)

---

## 🔧 Implementatieplan

### Fase 1: Schema-Koppeling (1 dag)

**Actie 1: Identificeer alle BRP-schema IDs**
```sql
SELECT id, title FROM oc_openregister_schemas 
WHERE title LIKE '%BRP%' OR title IN ('Personen', 'Huwelijken', 'Nationaliteiten', 'Reisdocumenten', 'Kiesrecht')
ORDER BY id;
```

**Actie 2: Koppel schemas aan register 2**
```sql
UPDATE oc_openregister_registers 
SET schemas = '[6, 12, 14, 17, ...]'  -- alle gevonden schema IDs
WHERE id = 2;
```

**Actie 3: Verifieer OpenAPI spec**
```bash
curl http://localhost:8080/apps/openregister/api/registers/2/oas | jq '.paths | keys'
```

**Verwacht resultaat:**
```json
[
  "/vrijbrppersonen/personen",
  "/vrijbrppersonen/huwelijken",
  "/vrijbrppersonen/nationaliteiten",
  "/vrijbrppersonen/reisdocumenten"
]
```

### Fase 2: Schema Definitie (2 dagen)

**Voor elk schema:**
1. Controleer dat `properties` correct verwijzen naar probev-tabellen
2. Voeg `source = '1'` toe (Bevax database)
3. Voeg mapping toe voor genormaliseerde velden (`c_voorn` → `voorn` tabel)

### Fase 3: Data Import (1 dag)

**Voor elk schema:**
1. Maak import script (vergelijkbaar met `import-personen-to-openregister.php`)
2. Importeer minimaal 100 testrecords per schema
3. Verifieer via API endpoints

### Fase 4: Eventpublicatie (3 dagen)

**Implementeer:**
1. `EventPublisher` service
2. `oc_openregister_events` database tabel
3. Events in controllers (`object.created`, `object.updated`, etc.)
4. Webhook endpoints

---

## 📝 Conclusie & Aanbeveling

### Conclusie

De architectuur is **fundamenteel correct geïmplementeerd**:
- ✅ Open Register ondersteunt register/schema endpoints
- ✅ Automatische OpenAPI spec generatie werkt
- ✅ Database structuur is correct (probev schema)
- ✅ Haal Centraal API is geïmplementeerd

**Maar:**
- ❌ Niet alle BRP-schemas zijn gekoppeld aan het register
- ❌ Eventpublicatie ontbreekt

### Aanbeveling

**Prioriteit 1: Koppel Alle BRP-Schemas (1 dag)**
- Dit activeert direct de automatische endpoints
- Minimale code-wijzigingen nodig
- Grote impact op functionaliteit

**Prioriteit 2: Implementeer Eventpublicatie (3 dagen)**
- Vereist volgens documentatie (POM logging, webhooks)
- Basis voor notificaties en workflows

**Prioriteit 3: Data Import (1 dag)**
- Importeer testdata voor alle schemas
- Verifieer dat endpoints werken

---

## 🎯 Herziening Eerdere Analyse

### Wat Ik Eerder Verkeerd Interpreteerde

❌ **Onjuist:** "Er zijn geen `/registers/inwoners` endpoints"  
✅ **Correct:** "Er is wel `/vrijbrppersonen/personen`, maar andere schemas zijn niet gekoppeld"

❌ **Onjuist:** "API structuur is anders dan documentatie"  
✅ **Correct:** "API structuur komt exact overeen, alleen URL-pad is iets anders"

### Wat Correct Was

✅ Eventpublicatie ontbreekt (nog steeds waar)  
✅ Haal Centraal API is volledig geïmplementeerd  
✅ Database structuur is correct

---

## 📖 Referenties

- **OpenAPI Spec:** `http://localhost:8080/apps/openregister/api/registers/2/oas`
- **Live Endpoint:** `http://localhost:8080/apps/openregister/vrijbrppersonen/personen`
- **Register Configuratie:** MariaDB `oc_openregister_registers` tabel
- **Schema Configuratie:** MariaDB `oc_openregister_schemas` tabel
