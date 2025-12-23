# Relaties via Open Register - Test Resultaten

## ✅ Test Uitgevoerd: BSN 168149291

### Stap 1: Relaties Toegevoegd aan Open Register

**Resultaat:**
- ✅ Partners: 1 (BSN: 164287061)
- ✅ Kinderen: 1 (BSN: 382651765)
- ✅ Ouders: 2 (BSN: 73218832, 73218327)
- ✅ Nationaliteiten: 1 (Code: 1, Omschrijving: Nederlandse)

**Verificatie in Open Register:**
```sql
SELECT 
    JSON_EXTRACT(object, '$.bsn') as bsn,
    JSON_LENGTH(JSON_EXTRACT(object, '$._embedded.partners')) as partners_count,
    JSON_LENGTH(JSON_EXTRACT(object, '$._embedded.kinderen')) as kinderen_count,
    JSON_LENGTH(JSON_EXTRACT(object, '$._embedded.ouders')) as ouders_count,
    JSON_LENGTH(JSON_EXTRACT(object, '$._embedded.nationaliteiten')) as nat_count
FROM oc_openregister_objects 
WHERE schema = 6 
AND JSON_EXTRACT(object, '$.bsn') = '168149291';
```

**Resultaat:**
- partners_count: 1
- kinderen_count: 1
- ouders_count: 2
- nat_count: 1

### Stap 2: Haal Centraal API Endpoints Testen

#### Partners Endpoint
```bash
GET /ingeschrevenpersonen/168149291/partners?ggm=false
```

**Verwachting:** Relaties worden opgehaald uit Open Register `_embedded` object

#### Kinderen Endpoint
```bash
GET /ingeschrevenpersonen/168149291/kinderen?ggm=false
```

**Verwachting:** Relaties worden opgehaald uit Open Register `_embedded` object

#### Ouders Endpoint
```bash
GET /ingeschrevenpersonen/168149291/ouders?ggm=false
```

**Verwachting:** Relaties worden opgehaald uit Open Register `_embedded` object

#### Nationaliteiten Endpoint
```bash
GET /ingeschrevenpersonen/168149291/nationaliteiten?ggm=false
```

**Verwachting:** Relaties worden opgehaald uit Open Register `_embedded` object

## 📋 Implementatie Status

### ✅ Voltooid
1. Schema bijgewerkt met `_embedded` veld
2. Haal Centraal controller aangepast om eerst uit Open Register te halen
3. Test script uitgevoerd voor BSN 168149291
4. Relaties opgeslagen in Open Register

### ⏳ Volgende Stappen
1. Test volledige import script voor alle personen
2. Test via test scherm (`/haal-centraal-test`)
3. Verifieer dat endpoints correct werken met relaties uit Open Register
4. Implementeer mutatie handlers voor relaties

## 🔧 Technische Details

### Data Flow
1. **Import:** PostgreSQL → Open Register (met `_embedded` relaties)
2. **API Request:** Haal Centraal endpoint → Open Register (eerste keuze) → PostgreSQL (fallback)
3. **Response:** Haal Centraal-formaat JSON

### Voordelen
- ✅ Relaties beschikbaar via Open Register API
- ✅ Historie/versiebeheer voor relaties mogelijk
- ✅ Eventing mogelijk voor relatie-mutaties
- ✅ Performance: relaties kunnen worden gecached
- ✅ Common Ground-compliant
- ✅ Fallback naar PostgreSQL als relaties niet in Open Register staan

## 📝 Bestanden

- `test-relaties-bsn-168149291.php` - Test script voor één persoon
- `import-personen-met-relaties.php` - Volledig import script (nog te testen)
- `lib/Controller/HaalCentraalBrpController.php` - Updated controller met Open Register fallback







