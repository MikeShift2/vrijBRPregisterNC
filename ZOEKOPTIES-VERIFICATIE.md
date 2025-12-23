# Verificatie Zoekopties - vrijBRP vs GGM

## Status: ✅ BEVESTIGD

### Zoekopties Configuratie

**HTML Radio Buttons:**
- `id="schema-vrijbrp"` → "Zoek in vrijBRP" (standaard geselecteerd)
- `id="schema-ggm"` → "Zoek in GGM"

### JavaScript Logica

**`getSchemaType()` functie:**
- Retourneert `'vrijbrp'` wanneer "Zoek in vrijBRP" is geselecteerd
- Retourneert `'ggm'` wanneer "Zoek in GGM" is geselecteerd
- Standaard: `'vrijbrp'`

**Parameter Mapping:**
- `schemaType === 'vrijbrp'` → `ggmParam = '?ggm=false'` of `'&ggm=false'`
- `schemaType === 'ggm'` → `ggmParam = '?ggm=true'` of `'&ggm=true'`

### PHP Controller Logica

**`getSchemaId()` functie:**
- `ggm=true` → gebruikt `SCHEMA_ID_GGM = 21` (GGM IngeschrevenPersoon)
- `ggm=false` of geen parameter → gebruikt `SCHEMA_ID_VRIJBRP = 6` (Personen, probev data)

**Schema Constants:**
```php
private const SCHEMA_ID_VRIJBRP = 6;   // Personen (niet-GGM) - probev data
private const SCHEMA_ID_GGM = 21;      // GGM IngeschrevenPersoon
```

### Testresultaten

#### Test 1: Zoek in vrijBRP (ggm=false)
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners?ggm=false"
```
**Resultaat:** ✅ Werkt - retourneert partners uit vrijBRP register (probev data)

#### Test 2: Zoek in GGM (ggm=true)
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners?ggm=true"
```
**Resultaat:** ✅ Correct - retourneert lege array (geen GGM data voor deze BSN, register niet actief)

### Hint Tekst Dynamisch Aanpassen

**JavaScript code (regel 1747-1755):**
- Bij "Zoek in vrijBRP": "Zoekt via Haal Centraal API in schema's die niet GGM zijn (schema ID 6 - Personen)"
- Bij "Zoek in GGM": "Zoekt via Haal Centraal API in GGM schema (schema ID 21 - GGM IngeschrevenPersoon)"

### Conclusie

✅ **BEVESTIGD:**
1. "Zoek in vrijBRP" → zoekt correct in het vrijBRP register (schema ID 6, probev data)
2. "Zoek in GGM" → zoekt correct in het GGM register (schema ID 21, momenteel niet actief)
3. Alle endpoints (partners, kinderen, ouders, verblijfplaats, nationaliteiten) gebruiken dezelfde logica
4. De hint tekst wordt dynamisch aangepast op basis van de geselecteerde optie

### Endpoints die deze logica gebruiken:
- `GET /ingeschrevenpersonen` (lijst)
- `GET /ingeschrevenpersonen/{bsn}` (specifieke persoon)
- `GET /ingeschrevenpersonen/{bsn}/partners`
- `GET /ingeschrevenpersonen/{bsn}/kinderen`
- `GET /ingeschrevenpersonen/{bsn}/ouders`
- `GET /ingeschrevenpersonen/{bsn}/verblijfplaats`
- `GET /ingeschrevenpersonen/{bsn}/nationaliteiten`







