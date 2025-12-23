# OpenRegister Bevax Database Configuratie

## Database Configuratie

**Source ID:** 1  
**Titel:** Bevax Register (bevax database)  
**Beschrijving:** BRP database in AX formaat met probev schema. Bevat 198 tabellen met genormaliseerde BRP data volgens PL-AX specificatie.

**Database URL:**
```
pgsql://postgres:@host.docker.internal:5432/bevax?search_path=probev
```

## Database Structuur

### Schema: `probev`

Het `probev` schema bevat alle BRP-data volgens het PL-AX formaat:

- **198 tabellen** totaal
- **~2 miljoen rijen** data
- **20.630 actuele personen** (inw_ax met ax='A' en hist='A')
- **102.863 verblijven** (vb_ax met ax='A')
- **25.523 huwelijken** (huw_ax met ax='A')
- **27.553 nationaliteiten** (nat_ax met ax='A')

### Belangrijkste Tabellen (_ax format)

Volgens PL-AX specificatie:

1. **inw_ax** - Inwoner (cat 1)
2. **mdr_ax** - Ouder 1 (cat 2)
3. **vdr_ax** - Ouder 2 (cat 3)
4. **nat_ax** - Nationaliteit (cat 4)
5. **huw_ax** - Huwelijk / GPS (cat 5)
6. **overl_ax** - Overlijden (cat 6)
7. **inschr_ax** - Inschrijving (cat 7)
8. **vb_ax** - Verblijf (cat 8)
9. **afst_ax** - Afstamming / Kind (cat 9)
10. **vbt_ax** - Verblijfstitel (cat 10)
11. **gezag_ax** - Gezag (cat 11)
12. **reisd_ax** - Reisdocumenten (cat 12)
13. **kiesr_ax** - Kiesrecht (cat 13)
14. **verw_ax** - Verwijzing (cat 21)

### Kleerhanger Tabel

- **pl** - Persoonslijst kleerhanger met pl_id, bsn, overlijdensdatum, uitschrijvingsdatum, burgerlijke staat

## Belangrijke Velden

### Actuele vs Archief Records

- **Actueel:** `ax = 'A'` en `hist = 'A'`
- **Archief:** `ax = 'X'` (verwijderd/vertrokken)

### Normalisatie

- Velden met `c_` prefix zijn codes die verwijzen naar basis-tabellen
  - Bijv. `c_voorn` → tabel `voorn`
  - Bijv. `c_naam` → tabel `naam`
- Velden met `d_` prefix zijn datums in formaat `JJJJMMDD` (integer)
- Velden met `l_` prefix zijn landcodes
- Velden met `p_` prefix zijn plaatscodes
- Velden met `g_` prefix zijn gemeentecodes

### Voorbeeld Query

```sql
-- Actuele inwoners met voornaam en achternaam
SELECT 
    i.bsn,
    v.voorn as voornaam,
    n.naam as achternaam,
    i.d_geb as geboortedatum
FROM probev.inw_ax i
LEFT JOIN probev.voorn v ON v.c_voorn = i.c_voorn
LEFT JOIN probev.naam n ON n.c_naam = i.c_naam
WHERE i.ax = 'A' 
  AND i.hist = 'A';
```

## OpenRegister Schemas

De volgende schemas zijn aangemaakt in OpenRegister (maar verwijzen naar oude bevax structuur):

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

**Let op:** Deze schemas verwijzen naar de oude `bevax` tabellen die niet meer bestaan. Ze moeten worden bijgewerkt om te verwijzen naar de `probev` tabellen.

## Aanbevelingen

1. **Update OpenRegister Schemas:** De bestaande schemas moeten worden bijgewerkt om te verwijzen naar de `probev` tabellen
2. **Gebruik Views:** Overweeg views te maken die de genormaliseerde data denormaliseren voor OpenRegister
3. **Filter op Actuele Records:** Zorg dat queries altijd filteren op `ax = 'A'` en `hist = 'A'` voor actuele data

## Database Grootte

- **Database grootte:** ~515 MB
- **Aantal tabellen:** 198
- **Totaal aantal rijen:** ~1.970.551








