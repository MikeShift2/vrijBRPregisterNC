# Actieplan: Schema's Bijwerken voor PoC Domeinregistratie Burgerzaken

## Huidige Status

### ✅ Al Voltooid

1. **Schema ID 6: Personen (vrijBRP)** ✅
   - Bijgewerkt naar Haal Centraal-specificatie
   - Verwijst naar `v_personen_compleet_haal_centraal` view
   - 17 Haal Centraal-compliant velden
   - Werkt correct met Haal Centraal API

2. **Schema ID 21: GGM IngeschrevenPersoon** ✅
   - Aangemaakt volgens GGM-specificatie
   - Gebruikt voor GGM-zoekopdrachten

3. **SQL Views** ✅
   - `v_inw_ax_haal_centraal` - Persoongegevens
   - `v_vb_ax_haal_centraal` - Adresgegevens
   - `v_personen_compleet_haal_centraal` - Gecombineerd

### ⚠️ Moet Worden Bijgewerkt

**14 schemas totaal, waarvan 3 al compleet zijn:**

| Schema ID | Titel | Status | Prioriteit | Actie Vereist |
|-----------|-------|--------|------------|--------------|
| 6 | Personen (vrijBRP) | ✅ Compleet | - | Geen actie |
| 21 | GGM IngeschrevenPersoon | ✅ Compleet | - | Geen actie |
| 7 | Adressen | ⚠️ Gedeeltelijk | 🔴 Hoog | Bijwerken naar `v_vb_ax_haal_centraal` |
| ? | Zaken | ❌ Niet bijgewerkt | 🟡 Medium | Voor dossier/zaak systeem |
| ? | Erkenningen | ❌ Niet bijgewerkt | 🟡 Medium | Voor burgerzaken-processen |
| ? | Gezagsverhoudingen | ❌ Niet bijgewerkt | 🟡 Medium | Voor burgerzaken-processen |
| ? | Huwelijken | ❌ Niet bijgewerkt | 🟡 Medium | Voor burgerzaken-processen |
| ? | Mutaties | ❌ Niet bijgewerkt | 🟢 Laag | Voor audit trail |
| ? | Nationaliteiten | ❌ Niet bijgewerkt | 🟡 Medium | Voor relaties |
| ? | PersoonFavoriet | ❌ Niet bijgewerkt | 🟢 Laag | UI-functionaliteit |
| ? | Reisdocumenten | ❌ Niet bijgewerkt | 🟡 Medium | Voor burgerzaken-processen |
| ? | RniPersonen | ❌ Niet bijgewerkt | 🟢 Laag | Voor niet-ingezetenen |
| ? | ZaakFavoriet | ❌ Niet bijgewerkt | 🟢 Laag | UI-functionaliteit |
| ? | BrpApiLogs | ❌ Niet bijgewerkt | 🟢 Laag | Monitoring |
| ? | Config | ❌ Niet bijgewerkt | 🟢 Laag | Configuratie |

---

## Actieplan per Prioriteit

### 🔴 Prioriteit Hoog (Kritiek voor PoC)

#### 1. Schema ID 7: Adressen Bijwerken

**Doel:** Adressen schema bijwerken om te verwijzen naar `v_vb_ax_haal_centraal` view

**Acties:**
1. ✅ Check huidige configuratie van schema ID 7
2. ✅ Update `table_name` in schema configuratie naar `v_vb_ax_haal_centraal`
3. ✅ Update schema properties naar Haal Centraal Adres-specificatie
4. ✅ Test adres-ophaling via Haal Centraal API

**SQL Update:**
```sql
-- Update schema configuratie
UPDATE oc_openregister_schemas 
SET configuration = JSON_SET(
    COALESCE(configuration, '{}'),
    '$.table_name', 'v_vb_ax_haal_centraal',
    '$.source_id', 1
)
WHERE id = 7;

-- Update properties naar Haal Centraal Adres-specificatie
UPDATE oc_openregister_schemas 
SET properties = '{
  "straatnaam": {"type": "string"},
  "huisnummer": {"type": "integer"},
  "huisnummertoevoeging": {"type": "string"},
  "postcode": {"type": "string"},
  "woonplaats": {"type": "string"},
  "land": {"type": "string"},
  "adresregel1": {"type": "string"},
  "adresregel2": {"type": "string"}
}'
WHERE id = 7;
```

**Test:**
```bash
# Test adres ophalen via Haal Centraal API
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/verblijfplaats"
```

**Geschatte tijd:** 1-2 uur

---

### 🟡 Prioriteit Medium (Belangrijk voor volledige functionaliteit)

#### 2. Nationaliteiten Schema Bijwerken

**Doel:** Nationaliteiten schema bijwerken voor relatie-functionaliteit

**Acties:**
1. ✅ Maak view `v_nat_ax_haal_centraal` voor denormalisatie
2. ✅ Update schema properties naar Haal Centraal-specificatie
3. ✅ Update database mapping
4. ✅ Test nationaliteiten-ophaling

**SQL View:**
```sql
CREATE VIEW probev.v_nat_ax_haal_centraal AS
SELECT 
    n.pl_id,
    n.bsn,
    n.c_nat,
    l.land as nationaliteit_omschrijving,
    l.c_land as nationaliteit_code,
    n.d_ingang as datum_ingang,
    n.d_einde as datum_einde,
    n.ax,
    n.hist
FROM probev.nat_ax n
LEFT JOIN probev.land l ON l.c_land = n.c_nat
WHERE n.ax = 'A' AND n.hist = 'A';
```

**Geschatte tijd:** 2-3 uur

#### 3. Huwelijken Schema Bijwerken

**Doel:** Huwelijken schema bijwerken voor partnerschap-functionaliteit

**Acties:**
1. ✅ Maak view `v_huw_ax_haal_centraal` voor denormalisatie
2. ✅ Update schema properties naar Haal Centraal-specificatie
3. ✅ Update database mapping
4. ✅ Test huwelijken-ophaling

**Geschatte tijd:** 2-3 uur

#### 4. Reisdocumenten Schema Bijwerken

**Doel:** Reisdocumenten schema bijwerken voor burgerzaken-processen

**Acties:**
1. ✅ Maak view `v_reisd_ax_haal_centraal` voor denormalisatie
2. ✅ Update schema properties naar Haal Centraal-specificatie
3. ✅ Update database mapping

**Geschatte tijd:** 2-3 uur

#### 5. Zaken Schema Bijwerken (voor Dossier/Zaak Systeem)

**Doel:** Zaken schema bijwerken voor dossier-functionaliteit

**Acties:**
1. ✅ Bepaal welke probev-tabellen nodig zijn voor zaken
2. ✅ Maak view of gebruik bestaande structuur
3. ✅ Update schema properties naar ZGW-specificatie
4. ✅ Update database mapping

**Geschatte tijd:** 4-6 uur

#### 6. Erkenningen & Gezagsverhoudingen Schema's Bijwerken

**Doel:** Schema's bijwerken voor burgerzaken-processen

**Acties:**
1. ✅ Bepaal welke probev-tabellen nodig zijn
2. ✅ Maak views voor denormalisatie
3. ✅ Update schema properties
4. ✅ Update database mapping

**Geschatte tijd:** 3-4 uur per schema

---

### 🟢 Prioriteit Laag (Nice to have)

#### 7. Overige Schema's Bijwerken

**Schema's:**
- Mutaties (voor audit trail)
- PersoonFavoriet (UI-functionaliteit)
- RniPersonen (voor niet-ingezetenen)
- ZaakFavoriet (UI-functionaliteit)
- BrpApiLogs (monitoring)
- Config (configuratie)

**Acties:**
- Bepaal of deze schema's nodig zijn voor PoC
- Bijwerken indien nodig
- Maak views indien nodig
- Update schema properties
- Update database mapping

**Geschatte tijd:** 1-2 uur per schema (indien nodig)

---

## Stappenplan Implementatie

### Fase 1: Kritieke Schema's (Week 1)

**Doel:** Basis-functionaliteit werkend krijgen

1. ✅ **Schema ID 7: Adressen** bijwerken (1-2 uur)
2. ✅ Testen adres-functionaliteit (1 uur)
3. ✅ Documentatie bijwerken (0.5 uur)

**Deliverables:**
- Werkend Adressen schema
- Testrapport

---

### Fase 2: Belangrijke Schema's (Week 2-3)

**Doel:** Relatie-functionaliteit completeren

1. ✅ **Nationaliteiten schema** bijwerken (2-3 uur)
2. ✅ **Huwelijken schema** bijwerken (2-3 uur)
3. ✅ **Reisdocumenten schema** bijwerken (2-3 uur)
4. ✅ Testen relatie-functionaliteit (2 uur)
5. ✅ Documentatie bijwerken (1 uur)

**Deliverables:**
- Werkende relatie-schema's
- Testrapport

---

### Fase 3: Dossier/Zaak Schema's (Week 4-5)

**Doel:** Basis voor dossier/zaak systeem

1. ✅ **Zaken schema** bijwerken (4-6 uur)
2. ✅ **Erkenningen schema** bijwerken (3-4 uur)
3. ✅ **Gezagsverhoudingen schema** bijwerken (3-4 uur)
4. ✅ Testen dossier-functionaliteit (2 uur)
5. ✅ Documentatie bijwerken (1 uur)

**Deliverables:**
- Werkende dossier-schema's
- Testrapport

---

### Fase 4: Overige Schema's (Week 6, optioneel)

**Doel:** Volledige functionaliteit

1. ✅ Bepaal welke overige schema's nodig zijn
2. ✅ Bijwerken indien nodig (1-2 uur per schema)
3. ✅ Testen (1 uur)
4. ✅ Documentatie bijwerken (0.5 uur)

**Deliverables:**
- Werkende overige schema's
- Testrapport

---

## Concreet Actieplan voor Nu

### Directe Acties (Vandaag/Deze Week)

1. **Schema ID 7: Adressen bijwerken** 🔴
   - **Waarom:** Wordt gebruikt door Haal Centraal API voor verblijfplaats
   - **Hoe:** Update `table_name` en `properties` in database
   - **Tijd:** 1-2 uur
   - **Impact:** Hoog - maakt verblijfplaats-functionaliteit werkend

2. **Nationaliteiten schema bijwerken** 🟡
   - **Waarom:** Wordt gebruikt voor relatie-functionaliteit
   - **Hoe:** Maak view + update schema
   - **Tijd:** 2-3 uur
   - **Impact:** Medium - maakt nationaliteiten-endpoint compleet

3. **Huwelijken schema bijwerken** 🟡
   - **Waarom:** Wordt gebruikt voor partnerschap-functionaliteit
   - **Hoe:** Maak view + update schema
   - **Tijd:** 2-3 uur
   - **Impact:** Medium - maakt partners-endpoint compleet

### Scripts die Kunnen Worden Gebruikt

**Bestaande scripts:**
- `update-schemas-haal-centraal.py` - Voor schema-updates
- `create-haal-centraal-views.sql` - Voor view-creatie
- `SCHEMA-UPDATE-PLAN.md` - Voor referentie

**Nieuwe scripts nodig:**
- Script voor adressen schema update
- Script voor nationaliteiten view + schema update
- Script voor huwelijken view + schema update

---

## Success Criteria

### Per Schema

✅ Schema verwijst naar correcte `probev` view of tabel  
✅ Schema properties zijn Haal Centraal-compliant (indien van toepassing)  
✅ Database mapping is correct geconfigureerd  
✅ Data kan worden opgehaald via Open Register API  
✅ Test suite toont correcte werking

### Algemeen

✅ Alle kritieke schema's zijn bijgewerkt  
✅ Haal Centraal API werkt volledig  
✅ Relatie-functionaliteit werkt volledig  
✅ Dossier-functionaliteit werkt (indien nodig)

---

## Risico's en Mitigaties

### Risico 1: View-performance

**Probleem:** Views met veel joins kunnen traag zijn

**Mitigatie:**
- Indexeer join-kolommen
- Monitor query-performance
- Overweeg materialized views indien nodig

### Risico 2: Data-inconsistentie

**Probleem:** Mapping kan leiden tot verkeerde data

**Mitigatie:**
- Test views grondig met sample data
- Valideer mapping met originele data
- Test edge cases (null-waarden, lege strings)

### Risico 3: Schema-complexiteit

**Probleem:** Veel schema's moeten worden bijgewerkt

**Mitigatie:**
- Werk gefaseerd (prioriteit eerst)
- Documenteer alle wijzigingen
- Test elke wijziging direct

---

## Volgende Stappen

### Direct (Vandaag)

1. ✅ **Start met Schema ID 7: Adressen**
   - Check huidige configuratie
   - Update naar `v_vb_ax_haal_centraal`
   - Test functionaliteit

2. ✅ **Documenteer huidige schema-status**
   - Maak lijst van alle schema ID's
   - Documenteer welke schema's al bijgewerkt zijn
   - Bepaal welke schema's nodig zijn voor PoC

### Deze Week

3. ✅ **Bijwerken kritieke schema's**
   - Adressen (ID 7)
   - Nationaliteiten
   - Huwelijken

4. ✅ **Testen**
   - Test alle bijgewerkte schema's
   - Valideer Haal Centraal API-functionaliteit
   - Documenteer testresultaten

### Volgende Weken

5. ✅ **Bijwerken overige schema's**
   - Zaken (voor dossier/zaak systeem)
   - Erkenningen
   - Gezagsverhoudingen
   - Reisdocumenten

6. ✅ **Volledige validatie**
   - Test alle schema's
   - Valideer volledige functionaliteit
   - Documenteer alles

---

## Conclusie

**Wat moet er nog worden gedaan:**

1. **Schema ID 7: Adressen** - Bijwerken naar `v_vb_ax_haal_centraal` (🔴 Hoog)
2. **Nationaliteiten schema** - View maken + schema bijwerken (🟡 Medium)
3. **Huwelijken schema** - View maken + schema bijwerken (🟡 Medium)
4. **Reisdocumenten schema** - View maken + schema bijwerken (🟡 Medium)
5. **Zaken schema** - Voor dossier/zaak systeem (🟡 Medium)
6. **Erkenningen & Gezagsverhoudingen** - Voor burgerzaken-processen (🟡 Medium)
7. **Overige schema's** - Indien nodig voor PoC (🟢 Laag)

**Geschatte totale tijd:** 20-30 uur (2.5-4 weken)

**Start met:** Schema ID 7: Adressen (kritiek voor verblijfplaats-functionaliteit)

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Actieplan klaar voor uitvoering







