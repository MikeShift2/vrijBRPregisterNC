# Relaties via Open Register - Implementatie Samenvatting

## ✅ Voltooide Stappen

### 1. Schema Update
- ✅ Personen schema (ID 6) bijgewerkt met `_embedded` veld
- ✅ `_embedded` bevat: `partners`, `kinderen`, `ouders`, `nationaliteiten`
- ✅ Schema ondersteunt nu Haal Centraal BRP Bevragen API-specificatie voor relaties

**SQL Update:**
```sql
UPDATE oc_openregister_schemas 
SET properties = JSON_MERGE_PRESERVE(properties, '{..._embedded...}')
WHERE id = 6;
```

### 2. Haal Centraal Controller Update
- ✅ `getPartners()` - Haalt eerst uit Open Register, fallback naar PostgreSQL
- ✅ `getKinderen()` - Haalt eerst uit Open Register, fallback naar PostgreSQL
- ✅ `getOuders()` - Haalt eerst uit Open Register, fallback naar PostgreSQL
- ✅ `getNationaliteiten()` - Haalt eerst uit Open Register, fallback naar PostgreSQL

**Logica:**
```php
// Probeer eerst uit Open Register (_embedded)
$embeddedPartners = $persoonData['_embedded']['partners'] ?? null;
if (!empty($embeddedPartners)) {
    $partners = $embeddedPartners;
} else {
    // Fallback naar PostgreSQL
    $partners = $this->getPartnersFromPostgres($plId, $bsn);
}
```

### 3. Import Script
- ✅ Nieuw script: `import-personen-met-relaties.php`
- ✅ Haalt relaties op uit PostgreSQL voor elke persoon
- ✅ Voegt relaties toe als `_embedded` object
- ✅ Update bestaande personen met relaties
- ✅ Importeert nieuwe personen met relaties

**Functionaliteit:**
- Haalt personen op uit `v_personen_compleet_haal_centraal` view
- Voor elke persoon:
  - Haalt `pl_id` op basis van BSN
  - Haalt partners, kinderen, ouders, nationaliteiten op
  - Voegt toe als `_embedded` object
  - Slaat op in Open Register

## 📋 Volgende Stappen

### Stap 1: Test Import Script
```bash
# Test met kleine batch (10 personen)
php import-personen-met-relaties.php
```

### Stap 2: Verifieer Relaties in Open Register
```sql
-- Check of relaties zijn opgeslagen
SELECT 
    JSON_EXTRACT(object, '$.bsn') as bsn,
    JSON_EXTRACT(object, '$._embedded.partners') as partners,
    JSON_EXTRACT(object, '$._embedded.kinderen') as kinderen,
    JSON_EXTRACT(object, '$._embedded.ouders') as ouders,
    JSON_EXTRACT(object, '$._embedded.nationaliteiten') as nationaliteiten
FROM oc_openregister_objects 
WHERE schema = 6 
AND JSON_EXTRACT(object, '$.bsn') = '168149291';
```

### Stap 3: Test Haal Centraal Endpoints
```bash
# Test partners endpoint
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners?ggm=false"

# Test kinderen endpoint
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen?ggm=false"

# Test ouders endpoint
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders?ggm=false"

# Test nationaliteiten endpoint
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten?ggm=false"
```

### Stap 4: Test via Test Scherm
- Open: `http://localhost:8080/apps/openregister/haal-centraal-test`
- Zoek op BSN: `168149291`
- Controleer of relaties worden getoond

## 🔧 Technische Details

### Database Structuur
- **Open Register:** `oc_openregister_objects.object` (JSON)
- **PostgreSQL:** `probev` schema met tabellen:
  - `huw_ax` - Huwelijken/Partners
  - `afst_ax` - Afstamming/Kinderen
  - `mdr_ax` - Ouder 1
  - `vdr_ax` - Ouder 2
  - `nat_ax` - Nationaliteiten

### Data Flow
1. **Import:** PostgreSQL → Open Register (met `_embedded` relaties)
2. **API Request:** Haal Centraal endpoint → Open Register (eerste keuze) → PostgreSQL (fallback)
3. **Response:** Haal Centraal-formaat JSON

### Voordelen
- ✅ Relaties beschikbaar via Open Register API
- ✅ Historie/versiebeheer voor relaties
- ✅ Eventing mogelijk voor relatie-mutaties
- ✅ Performance: relaties kunnen worden gecached
- ✅ Common Ground-compliant
- ✅ Fallback naar PostgreSQL als relaties niet in Open Register staan

## ⚠️ Opmerkingen

1. **Performance:** Import script gebruikt kleine batches (10 personen) omdat relaties worden opgehaald
2. **Data Duplicatie:** Relaties worden opgeslagen in zowel PostgreSQL als Open Register
3. **Mutaties:** Bij mutaties in PostgreSQL moeten Open Register objecten worden bijgewerkt
4. **Verblijfplaats:** Wordt al opgehaald uit persoon data, geen aparte `_embedded` nodig

## 📝 Bestanden

- `update-schema-met-relaties-sql.sql` - SQL script voor schema update
- `import-personen-met-relaties.php` - Import script met relaties
- `lib/Controller/HaalCentraalBrpController.php` - Updated controller met Open Register fallback







