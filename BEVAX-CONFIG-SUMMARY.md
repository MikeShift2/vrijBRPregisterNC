# Bevax Database & OpenRegister Configuratie - Samenvatting

## ✅ Uitgevoerde Aanpassingen

### 1. Database Configuratie
- **Database:** `bevax`
- **Schema:** `probev` 
- **Database URL bijgewerkt:** `pgsql://postgres:@host.docker.internal:5432/bevax?search_path=probev`
- **Beschrijving toegevoegd:** "BRP database in AX formaat met probev schema. Bevat 198 tabellen met genormaliseerde BRP data volgens PL-AX specificatie."

### 2. OpenRegister Schemas
- **14 schemas gekoppeld** aan externe source (source = '1')
- Alle schemas verwijzen nu naar de bevax database

### 3. Database Status
- ✅ Backup succesvol geïmporteerd
- ✅ 198 tabellen aanwezig
- ✅ ~2 miljoen rijen data
- ✅ 20.630 actuele personen
- ✅ Database grootte: 515 MB

## 📊 Database Structuur (volgens PL-AX)

### Belangrijkste Tabellen
- `pl` - Persoonslijst kleerhanger (45.017 records)
- `inw_ax` - Inwoners (59.027 records, 20.630 actueel)
- `vb_ax` - Verblijven (199.530 records, 102.863 actueel)
- `huw_ax` - Huwelijken (38.184 records, 25.523 actueel)
- `nat_ax` - Nationaliteiten (47.506 records, 27.553 actueel)
- `reisd_ax` - Reisdocumenten (53.535 records)
- `afst_ax` - Afstamming (50.157 records)
- `gezag_ax` - Gezag
- `mdr_ax` - Ouder 1 (48.500 records)
- `vdr_ax` - Ouder 2 (48.725 records)

### Views Beschikbaar
- `inw_axv`, `vb_axv`, `huw_axv`, `nat_axv`, etc. (17 views met `_axv` suffix)

## ⚠️ Belangrijke Opmerkingen

### Normalisatie
De database is **genormaliseerd**:
- `c_voorn` → verwijst naar tabel `voorn`
- `c_naam` → verwijst naar tabel `naam`
- `d_*` → datums in formaat `JJJJMMDD` (integer)
- `l_*` → landcodes
- `p_*` → plaatscodes
- `g_*` → gemeentecodes

### Actuele Records
- Filter altijd op: `ax = 'A'` EN `hist = 'A'` voor actuele data
- Archief records hebben: `ax = 'X'`

### OpenRegister Schemas
De bestaande schemas (Personen, Adressen, Zaken, etc.) verwijzen nog naar de **oude structuur**. Deze moeten mogelijk worden bijgewerkt om te werken met de `probev` tabellen.

## 🔗 OpenRegister Configuratie

**Source ID:** 1  
**Titel:** Bevax Register (bevax database)  
**Database URL:** `pgsql://postgres:@host.docker.internal:5432/bevax?search_path=probev`  
**Type:** postgresql

**Register ID:** 1  
**Titel:** Bevax Register  
**Source:** 1 (gekoppeld aan bevax database)

**Schemas:** 14 schemas gekoppeld aan source 1

## 📝 Volgende Stappen (Optioneel)

1. **Views maken** voor denormalisatie van genormaliseerde data
2. **OpenRegister schemas updaten** om te verwijzen naar `probev` tabellen
3. **Test queries** uitvoeren om te controleren of OpenRegister correct werkt met de nieuwe structuur








