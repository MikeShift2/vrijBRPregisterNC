# Plan: Open Register Schemas Bijwerken naar Haal Centraal-specificatie

## Doelstelling

De 14 bestaande Open Register-schemas bijwerken zodat ze:
1. Exact overeenkomen met de Haal Centraal BRP Bevragen API-specificatie
2. Correct verwijzen naar de `probev`-tabellen in plaats van oude structuur
3. Genormaliseerde data correct denormaliseren via views of mapping

## Huidige Situatie

### Database Structuur
- **Database:** `bevax` PostgreSQL
- **Schema:** `probev` met 198 tabellen volgens PL-AX specificatie
- **Belangrijkste tabellen:**
  - `inw_ax` - Inwoners (cat 1)
  - `vb_ax` - Verblijven (cat 8)
  - `huw_ax` - Huwelijken (cat 5)
  - `nat_ax` - Nationaliteiten (cat 4)
  - `mdr_ax` - Ouder 1 (cat 2)
  - `vdr_ax` - Ouder 2 (cat 3)
  - `afst_ax` - Afstamming/Kind (cat 9)
  - `reisd_ax` - Reisdocumenten (cat 12)
  - `pl` - Persoonslijst kleerhanger

### Normalisatie-uitdaging
- `c_voorn` → verwijst naar tabel `voorn`
- `c_naam` → verwijst naar tabel `naam`
- `d_*` → datums in formaat `JJJJMMDD` (integer)
- `l_*` → landcodes
- `p_*` → plaatscodes
- `g_*` → gemeentecodes

### Huidige Schemas
- Schema ID 6: Personen (vrijBRP)
- Schema ID 21: GGM IngeschrevenPersoon
- Schema ID 7: Adressen
- Plus 11 andere schemas

## Stappenplan

### Stap 1: Analyse Haal Centraal-specificatie

**Doel:** Bepaal exact welke velden en datatypes vereist zijn volgens Haal Centraal BRP Bevragen API.

**Acties:**
1. Download/haal Haal Centraal OpenAPI-specificatie op
2. Analyseer `IngeschrevenPersoon` objectstructuur
3. Documenteer alle vereiste velden, datatypes en relaties
4. Identificeer welke velden optioneel zijn

**Output:** Document met Haal Centraal-velddefinities

---

### Stap 2: Mapping probev → Haal Centraal

**Doel:** Bepaal hoe probev-tabellen gemapt moeten worden naar Haal Centraal-velden.

**Acties:**
1. Analyseer `inw_ax` tabelstructuur
2. Analyseer `vb_ax` tabelstructuur (voor adresgegevens)
3. Analyseer `nat_ax`, `huw_ax`, etc. voor relaties
4. Maak mapping-tabel: probev-veld → Haal Centraal-veld
5. Documenteer transformaties (datum-formaten, code-mappings, etc.)

**Output:** Mapping-document met alle veldtransformaties

**Voorbeeld mapping:**
| probev (inw_ax) | Haal Centraal | Transformatie |
|----------------|---------------|---------------|
| `bsn` | `burgerservicenummer` | Direct |
| `c_voorn` → `voorn.voorn` | `naam.voornamen[]` | Join + split op spaties |
| `c_naam` → `naam.naam` | `naam.geslachtsnaam` | Join |
| `d_geb` | `geboorte.datum.datum` | `JJJJMMDD` → `JJJJ-MM-DD` |
| `geslacht` (V/M/O) | `geslachtsaanduiding` | V→vrouw, M→man, O→onbekend |

---

### Stap 3: SQL Views Maken voor Denormalisatie

**Doel:** Creëer views die genormaliseerde probev-data denormaliseren voor Open Register.

**Acties:**
1. Maak view `v_inw_ax_denorm` die:
   - `inw_ax` joined met `voorn`, `naam`, `plaats`, `land`, etc.
   - Datums converteert naar ISO-formaat
   - Geslacht-codes transformeert
   - Voornamen splitst naar array
2. Maak view `v_vb_ax_denorm` voor adresgegevens
3. Test views met sample queries

**Voorbeeld view:**
```sql
CREATE VIEW probev.v_inw_ax_denorm AS
SELECT 
    i.bsn,
    i.pl_id,
    -- Naamgegevens (denormaliseren)
    v.voorn as voornamen_string,
    n.naam as geslachtsnaam,
    -- Geboortegegevens
    TO_CHAR(TO_DATE(i.d_geb::text, 'YYYYMMDD'), 'YYYY-MM-DD') as geboortedatum_iso,
    p.plaats as geboorteplaats,
    l.land as geboorteland,
    -- Geslacht transformatie
    CASE i.geslacht
        WHEN 'V' THEN 'vrouw'
        WHEN 'M' THEN 'man'
        WHEN 'O' THEN 'onbekend'
        ELSE 'onbekend'
    END as geslachtsaanduiding,
    -- Alleen actuele records
    i.ax,
    i.hist
FROM probev.inw_ax i
LEFT JOIN probev.voorn v ON v.c_voorn = i.c_voorn
LEFT JOIN probev.naam n ON n.c_naam = i.c_naam
LEFT JOIN probev.plaats p ON p.c_plaats = i.p_geb
LEFT JOIN probev.land l ON l.c_land = i.l_geb
WHERE i.ax = 'A' AND i.hist = 'A';
```

**Output:** SQL-script met alle benodigde views

---

### Stap 4: Update Open Register Personen Schema (ID 6)

**Doel:** Update schema properties om exact overeen te komen met Haal Centraal-specificatie.

**Acties:**
1. Haal huidige schema properties op
2. Maak nieuwe properties-structuur volgens Haal Centraal
3. Update schema in database
4. Valideer schema-structuur

**Haal Centraal-velden (vereist):**
```json
{
  "burgerservicenummer": {"type": "string"},
  "naam": {
    "type": "object",
    "properties": {
      "voornamen": {"type": "array", "items": {"type": "string"}},
      "geslachtsnaam": {"type": "string"},
      "voorvoegsel": {"type": "string"}
    }
  },
  "geboorte": {
    "type": "object",
    "properties": {
      "datum": {
        "type": "object",
        "properties": {
          "datum": {"type": "string", "format": "date"}
        }
      },
      "plaats": {"type": "string"},
      "land": {"type": "string"}
    }
  },
  "geslachtsaanduiding": {"type": "string", "enum": ["man", "vrouw", "onbekend"]},
  "verblijfplaats": {
    "type": "object",
    "properties": {
      "straatnaam": {"type": "string"},
      "huisnummer": {"type": "integer"},
      "huisnummertoevoeging": {"type": "string"},
      "postcode": {"type": "string"},
      "woonplaats": {"type": "string"},
      "land": {"type": "string"}
    }
  }
}
```

**Output:** Bijgewerkt schema in database

---

### Stap 5: Update Database Mappings

**Doel:** Configureer Open Register om data uit probev-views te lezen.

**Acties:**
1. Update source-configuratie om views te gebruiken
2. Configureer field mappings in Open Register
3. Test data-ophaling via Open Register API

**Open Register mapping-configuratie:**
- Source: `probev.v_inw_ax_denorm` (view)
- Field mappings: Haal Centraal-veld → view-kolom
- Filters: Alleen `ax='A'` en `hist='A'`

**Output:** Werkende data-ophaling via Open Register

---

### Stap 6: Test en Valideer

**Doel:** Valideer dat bijgewerkte schemas correct werken met Haal Centraal-controller.

**Acties:**
1. Test `/ingeschrevenpersonen` endpoint
2. Test `/ingeschrevenpersonen/{bsn}` endpoint
3. Valideer dat data correct wordt getransformeerd
4. Controleer dat alle Haal Centraal-velden aanwezig zijn
5. Test edge cases (null-waarden, lege arrays, etc.)

**Output:** Testrapport met validatie-resultaten

---

## Implementatievolgorde

### Fase 1: Basis Personen Schema (Prioriteit: Hoog)
1. ✅ Stap 1: Analyse Haal Centraal-specificatie
2. ✅ Stap 2: Mapping probev → Haal Centraal
3. ✅ Stap 3: SQL Views maken
4. ✅ Stap 4: Update Personen Schema (ID 6)
5. ✅ Stap 5: Update Database Mappings
6. ✅ Stap 6: Test en Valideer

### Fase 2: Uitbreidingen (Prioriteit: Medium)
- Adressen schema (ID 7)
- Nationaliteiten schema
- Partners/Kinderen/Ouders relaties

### Fase 3: Overige Schemas (Prioriteit: Laag)
- Zaken schema
- Reisdocumenten schema
- Etc.

---

## Risico's en Mitigaties

### Risico 1: Normalisatie-complexiteit
**Probleem:** probev gebruikt genormaliseerde structuur die moeilijk te mappen is.

**Mitigatie:** 
- Gebruik SQL views voor denormalisatie
- Test views grondig met sample data
- Documenteer alle joins en transformaties

### Risico 2: Performance
**Probleem:** Views met veel joins kunnen traag zijn.

**Mitigatie:**
- Indexeer join-kolommen
- Overweeg materialized views voor veelgebruikte queries
- Monitor query-performance

### Risico 3: Data-inconsistentie
**Probleem:** Mapping kan leiden tot verkeerde data.

**Mitigatie:**
- Valideer mapping met sample records
- Test edge cases (null-waarden, lege strings)
- Vergelijk output met originele data

---

## Success Criteria

✅ Personen schema (ID 6) heeft exact Haal Centraal-compliant properties  
✅ Database mappings verwijzen naar probev-views  
✅ Haal Centraal-controller kan data correct ophalen en transformeren  
✅ Alle vereiste Haal Centraal-velden zijn aanwezig in API-responses  
✅ Test suite toont 100% compliance met Haal Centraal-specificatie

---

## Volgende Stappen

1. **Start met Stap 1:** Analyseer Haal Centraal-specificatie
2. **Maak mapping-document:** Documenteer alle veldtransformaties
3. **Creëer SQL views:** Implementeer denormalisatie-views
4. **Update schema:** Pas Open Register-schema aan
5. **Test:** Valideer met Haal Centraal-controller







