# Bevragen (Lezen) Implementatie Status

**Datum:** 2025-01-27  
**Status:** ✅ Gedeeltelijk geïmplementeerd

---

## ✅ Geïmplementeerd

### 1. Field Selection (`fields` parameter) ✅

**Service:** `lib/Service/HaalCentraal/FieldSelectionService.php`

**Functionaliteit:**
- ✅ Ondersteuning voor `fields` parameter
- ✅ Geneste velden (bijv. `naam.voornamen`)
- ✅ Comma-separated lijst van velden
- ✅ Behoudt altijd `_links` en `_embedded`

**Voorbeelden:**
- `?fields=burgerservicenummer,naam` - Alleen BSN en naam
- `?fields=naam.voornamen,naam.geslachtsnaam` - Alleen voornamen en geslachtsnaam
- `?fields=geboorte.datum.datum` - Alleen geboortedatum

**Geïntegreerd in:**
- ✅ `getIngeschrevenPersonen()` endpoint
- ✅ `getIngeschrevenPersoon()` endpoint

---

### 2. Expand Functionaliteit (`expand` parameter) ✅

**Service:** `lib/Service/HaalCentraal/ExpandService.php`

**Functionaliteit:**
- ✅ Ondersteuning voor `expand` parameter
- ✅ Automatisch ophalen van relaties
- ✅ Ondersteuning voor wildcard (`*`) om alle relaties op te halen
- ✅ Comma-separated lijst van relaties

**Ondersteunde relaties:**
- ✅ `partners` - Partners ophalen
- ✅ `kinderen` - Kinderen ophalen
- ✅ `ouders` - Ouders ophalen
- ✅ `verblijfplaats` - Verblijfplaats ophalen
- ✅ `nationaliteiten` - Nationaliteiten ophalen

**Voorbeelden:**
- `?expand=partners` - Partners automatisch meenemen
- `?expand=partners,kinderen` - Partners en kinderen automatisch meenemen
- `?expand=*` - Alle relaties automatisch meenemen

**Geïntegreerd in:**
- ✅ `getIngeschrevenPersonen()` endpoint
- ✅ `getIngeschrevenPersoon()` endpoint

---

### 3. Geavanceerde Filters ✅

**Functionaliteit:**
- ✅ `geboortedatumVan` - Filter vanaf datum
- ✅ `geboortedatumTot` - Filter tot datum
- ✅ Werkt voor zowel GGM als vrijBRP schemas

**Voorbeelden:**
- `?geboortedatumVan=2000-01-01&geboortedatumTot=2010-12-31` - Personen geboren tussen 2000 en 2010

**Geïntegreerd in:**
- ✅ `getIngeschrevenPersonen()` endpoint
- ✅ Count query voor paginatie

---

### 4. Sortering (`sort` parameter) ✅

**Functionaliteit:**
- ✅ Ondersteuning voor `sort` parameter
- ✅ Meerdere sorteervelden (comma-separated)
- ✅ Ascending (`+` of default) en descending (`-`)
- ✅ Ondersteuning voor geneste velden

**Ondersteunde velden:**
- ✅ `naam.geslachtsnaam` - Sorteer op achternaam
- ✅ `geboorte.datum.datum` - Sorteer op geboortedatum
- ✅ `burgerservicenummer` - Sorteer op BSN
- ✅ `naam.voornamen` - Sorteer op voornamen

**Voorbeelden:**
- `?sort=naam.geslachtsnaam` - Sorteer op achternaam (ascending)
- `?sort=-geboorte.datum.datum` - Sorteer op geboortedatum (descending)
- `?sort=naam.geslachtsnaam,geboorte.datum.datum` - Sorteer eerst op achternaam, dan op geboortedatum

**Geïntegreerd in:**
- ✅ `getIngeschrevenPersonen()` endpoint

---

## ⚠️ Nog Te Implementeren

### 1. OpenAPI Specificatie (5%)

**Status:** ❌ Niet geïmplementeerd

**Vereist:**
- OpenAPI 3.0 specificatie genereren
- Swagger UI beschikbaar maken
- Alle endpoints documenteren
- Alle query parameters documenteren

**Geschatte tijd:** 1-2 dagen

---

### 2. Response Validatie (5%)

**Status:** ❌ Niet geïmplementeerd

**Vereist:**
- Validatie tegen Haal Centraal OpenAPI specificatie
- Test suite tegen Haal Centraal Cucumber tests
- Zorgen dat alle vereiste velden aanwezig zijn

**Geschatte tijd:** 2-3 dagen

---

### 3. Error Handling (3%)

**Status:** ⚠️ Gedeeltelijk geïmplementeerd

**Wat werkt:**
- ✅ Basis error handling (400, 404, 500)
- ✅ Gestructureerde error responses

**Wat ontbreekt:**
- ⚠️ Niet alle Haal Centraal error codes
- ⚠️ Error responses mogelijk niet volledig Haal Centraal-compliant

**Geschatte tijd:** 1 dag

---

### 4. Performance & Caching (2%)

**Status:** ❌ Niet geïmplementeerd

**Vereist:**
- Caching voor veelgebruikte queries
- Cache invalidation bij mutaties
- Query optimalisatie
- Database indexes

**Geschatte tijd:** 2-3 dagen

---

## Test Voorbeelden

### Field Selection
```bash
# Alleen BSN en naam
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen?fields=burgerservicenummer,naam"

# Alleen geboortedatum
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen?fields=geboorte.datum.datum"
```

### Expand
```bash
# Partners automatisch meenemen
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/123456789?expand=partners"

# Alle relaties automatisch meenemen
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/123456789?expand=*"
```

### Geavanceerde Filters
```bash
# Personen geboren tussen 2000 en 2010
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen?geboortedatumVan=2000-01-01&geboortedatumTot=2010-12-31"
```

### Sortering
```bash
# Sorteer op achternaam (descending)
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen?sort=-naam.geslachtsnaam"

# Sorteer eerst op achternaam, dan op geboortedatum
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen?sort=naam.geslachtsnaam,geboorte.datum.datum"
```

### Combinaties
```bash
# Field selection + expand + sort
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen?fields=burgerservicenummer,naam&expand=partners&sort=-naam.geslachtsnaam"
```

---

## Compliance Verbetering

**Huidige Score:** 75%  
**Na deze implementatie:** ~85% (+10%)

**Wat is verbeterd:**
- ✅ Field selection (`fields` parameter) - +3%
- ✅ Expand functionaliteit (`expand` parameter) - +3%
- ✅ Geavanceerde filters (datum ranges) - +2%
- ✅ Sortering (`sort` parameter) - +2%

**Wat nog nodig is voor 100%:**
- ⚠️ OpenAPI specificatie - +5%
- ⚠️ Response validatie - +5%
- ⚠️ Error handling verbetering - +3%
- ⚠️ Performance & caching - +2%

---

## Volgende Stappen

1. ✅ **Query Parameters & Filters** - Voltooid
2. ⏭️ **OpenAPI Specificatie** - Volgende stap
3. ⏭️ **Response Validatie** - Daarna
4. ⏭️ **Error Handling** - Verbeteren
5. ⏭️ **Performance & Caching** - Implementeren

---

**Status:** ✅ Query parameters geïmplementeerd, klaar voor testen







