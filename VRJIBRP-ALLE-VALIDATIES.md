# vrijBRP Dossiers API - Alle Validaties

## Overzicht

Dit document bevat een complete lijst van alle validatieregels die worden toegepast in de vrijBRP Dossiers API, georganiseerd per validatie laag en mutatie type.

**Bron:** https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation

---

## 1. Syntactische Validaties (400 Bad Request)

### 1.1 Algemene Syntactische Validaties

#### JSON Structuur
- ✅ Request body moet geldig JSON zijn
- ✅ JSON mag geen syntax errors bevatten
- ✅ JSON moet object zijn (niet array of primitief)

#### Content-Type
- ✅ `Content-Type: application/json` header moet aanwezig zijn
- ✅ Request body moet JSON formaat zijn

#### Authenticatie
- ✅ `Authorization: Bearer {token}` header moet aanwezig zijn
- ✅ Token moet geldig JWT formaat hebben
- ✅ Token mag niet verlopen zijn

---

### 1.2 BSN Validatie

**Veld:** `declarant.bsn`, `relocators[].bsn`, `partner1.bsn`, `partner2.bsn`, `mother.bsn`, `father.bsn`, etc.

**Regels:**
- ✅ Moet string zijn (niet number)
- ✅ Moet exact 9 cijfers bevatten
- ✅ Mag geen letters bevatten
- ✅ Mag geen speciale tekens bevatten
- ✅ Mag geen spaties bevatten
- ✅ Mag niet leeg zijn (null of empty string)

**Voorbeeld fout:**
```json
{
  "field": "declarant.bsn",
  "message": "BSN must be 9 digits"
}
```

---

### 1.3 Postcode Validatie

**Veld:** `newAddress.postalCode`, `oldAddress.postalCode`

**Regels:**
- ✅ Moet string zijn
- ✅ Moet formaat `1234AB` hebben (4 cijfers + 2 letters)
- ✅ Cijfers moeten 1000-9999 zijn
- ✅ Letters moeten hoofdletters zijn (A-Z)
- ✅ Mag geen spaties bevatten
- ✅ Mag niet leeg zijn

**Voorbeeld fout:**
```json
{
  "field": "newAddress.postalCode",
  "message": "Postal code format is invalid (expected: 1234AB)"
}
```

---

### 1.4 Datum Validatie

**Veld:** `relocationDate`, `commitmentDate`, `birthDate`, `deathDate`, etc.

**Regels:**
- ✅ Moet string zijn
- ✅ Moet ISO 8601 formaat zijn (`YYYY-MM-DD` of `YYYY-MM-DDTHH:MM:SSZ`)
- ✅ Moet geldige datum zijn (geen 32 januari)
- ✅ Moet geldige maand zijn (1-12)
- ✅ Moet geldige dag zijn voor die maand
- ✅ Schrikkeljaren moeten correct zijn

**Voorbeeld fout:**
```json
{
  "field": "relocationDate",
  "message": "Date format is invalid (expected: YYYY-MM-DD)"
}
```

---

### 1.5 Huisnummer Validatie

**Veld:** `newAddress.houseNumber`, `oldAddress.houseNumber`

**Regels:**
- ✅ Moet string of number zijn
- ✅ Mag niet negatief zijn
- ✅ Mag niet leeg zijn
- ✅ Mag geen speciale tekens bevatten (behalve cijfers)

**Voorbeeld fout:**
```json
{
  "field": "newAddress.houseNumber",
  "message": "House number must be a positive number"
}
```

---

### 1.6 Straatnaam Validatie

**Veld:** `newAddress.street`, `oldAddress.street`

**Regels:**
- ✅ Moet string zijn
- ✅ Mag niet leeg zijn (null of empty string)
- ✅ Mag niet alleen whitespace bevatten

**Voorbeeld fout:**
```json
{
  "field": "newAddress.street",
  "message": "Street name is required"
}
```

---

### 1.7 Woonplaats Validatie

**Veld:** `newAddress.city`, `oldAddress.city`

**Regels:**
- ✅ Moet string zijn
- ✅ Mag niet leeg zijn (null of empty string)
- ✅ Mag niet alleen whitespace bevatten

**Voorbeeld fout:**
```json
{
  "field": "newAddress.city",
  "message": "City name is required"
}
```

---

### 1.8 Email Validatie (indien vereist)

**Veld:** `email`, `contactEmail`, etc.

**Regels:**
- ✅ Moet string zijn
- ✅ Moet geldig email formaat hebben (`user@domain.com`)
- ✅ Moet @ bevatten
- ✅ Moet geldige domain hebben

**Voorbeeld fout:**
```json
{
  "field": "email",
  "message": "Email format is invalid"
}
```

---

### 1.9 Verplichte Velden

**Regels:**
- ✅ Verplichte velden moeten aanwezig zijn
- ✅ Verplichte velden mogen niet null zijn
- ✅ Verplichte velden mogen niet leeg zijn (voor strings)

**Voorbeeld fout:**
```json
{
  "field": "declarant",
  "message": "Declarant is required"
}
```

---

## 2. Semantische Validaties (422 Unprocessable Entity)

### 2.1 BSN Semantische Validaties

**Veld:** `declarant.bsn`, `relocators[].bsn`, etc.

**Regels:**
- ✅ BSN moet bestaan in BRP database
- ✅ BSN mag niet geblokkeerd zijn (`PERSON_RECORD_IS_BLOCKED`)
- ✅ Persoon mag niet overleden zijn (`PERSON_IS_DECEASED`)
- ✅ Persoon record mag niet geschorst zijn (`PERSONLIST_SUSPENDED`)
- ✅ Persoon mag niet verwijderd zijn (`NO_PERSON_RECORD_FOUND`)

**Database Query:**
```sql
SELECT 
    p.bsn,
    p.status,
    p.overlijdensdatum,
    p.geschorst
FROM personen p
WHERE p.bsn = :bsn
AND p.actueel = true;
```

**Voorbeeld fout:**
```json
{
  "field": "declarant.bsn",
  "message": "Person record is blocked",
  "obstructions": ["PERSON_RECORD_IS_BLOCKED"]
}
```

**Obstruction Types:**
- `PERSON_RECORD_IS_BLOCKED` - Persoon record is geblokkeerd
- `PERSON_IS_DECEASED` - Persoon is overleden
- `PERSONLIST_SUSPENDED` - Persoon lijst is geschorst
- `NO_PERSON_RECORD_FOUND` - Geen persoon record gevonden

---

### 2.2 Relocator Validaties

**Veld:** `relocators[]`

**Regels:**
- ✅ Relocator BSN moet bestaan in BRP
- ✅ Relocator moet `suitableForRelocation: true` zijn
- ✅ Relocator mag geen obstructions hebben:
  - `EXISTING_RELOCATION_CASE` - Er is al een lopende verhuizing
  - `DIFFERENT_ADDRESS` - Persoon woont op ander adres
  - `PERSON_IS_DECEASED` - Persoon is overleden
  - `PERSON_RECORD_IS_BLOCKED` - Persoon record is geblokkeerd
  - `RELATIONSHIP_HAS_ENDED` - Relatie is beëindigd (ex-partner)
- ✅ Relocator moet correcte `relationshipType` hebben
- ✅ Relocator moet geschikt zijn voor verhuizing (`suitableFor: ["NEW_RELOCATION_CASE"]`)

**Database Query:**
```sql
-- Check relaties
SELECT 
    r.bsn,
    r.relationship_type,
    r.suitable_for_relocation,
    r.obstructions
FROM relaties r
WHERE r.declarant_bsn = :declarant_bsn;

-- Check obstructions
SELECT * FROM verhuizingen 
WHERE bsn = :relocator_bsn 
AND status IN ('INCOMPLETE', 'PROCESSING');

-- Check adres
SELECT * FROM adressen 
WHERE bsn = :relocator_bsn 
AND adres != :declarant_adres;
```

**Voorbeeld fout:**
```json
{
  "field": "relocators[0]",
  "message": "Person is not suitable for relocation",
  "obstructions": ["EXISTING_RELOCATION_CASE", "DIFFERENT_ADDRESS"]
}
```

**Obstruction Types:**
- `EXISTING_RELOCATION_CASE` - Er is al een lopende verhuizing
- `DIFFERENT_ADDRESS` - Persoon woont op ander adres
- `PERSON_IS_DECEASED` - Persoon is overleden
- `PERSON_RECORD_IS_BLOCKED` - Persoon record is geblokkeerd
- `RELATIONSHIP_HAS_ENDED` - Relatie is beëindigd
- `NO_PERSON_RECORD_FOUND` - Geen persoon record gevonden

---

### 2.3 Adres Validaties

**Veld:** `newAddress`, `oldAddress`

**Regels:**
- ✅ Postcode moet bestaan in adresregister
- ✅ Straat moet bestaan in adresregister
- ✅ Woonplaats moet bestaan in adresregister
- ✅ Combinatie moet geldig zijn (straat + huisnummer + postcode + woonplaats)
- ✅ Adres mag niet hetzelfde zijn als huidig adres (voor verhuizing)
- ✅ Adres moet binnen gemeente zijn (voor intra-relocation)

**Database Query:**
```sql
-- Check postcode
SELECT * FROM postcodes 
WHERE postcode = :postcode;

-- Check straat
SELECT * FROM straten 
WHERE straatnaam = :straatnaam 
AND postcode = :postcode;

-- Check woonplaats
SELECT * FROM woonplaatsen 
WHERE woonplaats = :woonplaats 
AND postcode = :postcode;

-- Check combinatie
SELECT * FROM adressen 
WHERE straatnaam = :straatnaam
AND huisnummer = :huisnummer
AND postcode = :postcode
AND woonplaats = :woonplaats;
```

**Voorbeeld fout:**
```json
{
  "field": "newAddress.postalCode",
  "message": "Postal code does not exist in address register"
}
```

---

### 2.4 Datum Semantische Validaties

**Veld:** `relocationDate`, `commitmentDate`, `birthDate`, `deathDate`

**Regels:**
- ✅ Verhuisdatum mag niet in het verleden zijn (voor nieuwe verhuizingen)
- ✅ Geboortedatum mag niet in de toekomst zijn
- ✅ Overlijdensdatum mag niet in de toekomst zijn
- ✅ Partnerschapsdatum mag niet in het verleden zijn (voor nieuwe partnerschappen)
- ✅ Datum moet logisch zijn binnen context

**Voorbeeld fout:**
```json
{
  "field": "relocationDate",
  "message": "Relocation date cannot be in the past"
}
```

---

### 2.5 Relatie Validaties

**Veld:** `relocators[]`, `mother`, `father`, `partner1`, `partner2`

**Regels:**
- ✅ Relatie moet bestaan (bijv. ouder-kind relatie)
- ✅ Relatie type moet correct zijn
- ✅ Relatie mag niet beëindigd zijn
- ✅ Relatie moet actueel zijn

**Database Query:**
```sql
-- Check relatie
SELECT * FROM relaties 
WHERE persoon1_bsn = :bsn1 
AND persoon2_bsn = :bsn2 
AND relatie_type = :relatie_type
AND actueel = true;
```

**Voorbeeld fout:**
```json
{
  "field": "mother.bsn",
  "message": "Relationship does not exist or has ended"
}
```

---

## 3. Mutatie-Specifieke Validaties

### 3.1 Verhuizing (Intra-relocation)

#### Verplichte Velden
- ✅ `declarant.bsn` - Verplicht
- ✅ `newAddress.street` - Verplicht
- ✅ `newAddress.houseNumber` - Verplicht
- ✅ `newAddress.postalCode` - Verplicht
- ✅ `newAddress.city` - Verplicht

#### Optionele Velden
- ⚪ `referenceId` - Optioneel
- ⚪ `oldAddress` - Optioneel
- ⚪ `relocators` - Optioneel (maar aanbevolen)
- ⚪ `relocationDate` - Optioneel
- ⚪ `newAddress.liveIn` - Optioneel

#### Business Rules
- ✅ Declarant moet bestaan in BRP
- ✅ Declarant mag niet geblokkeerd zijn
- ✅ Alle relocators moeten geschikt zijn voor verhuizing
- ✅ Nieuw adres moet geldig zijn
- ✅ Nieuw adres moet binnen gemeente zijn (voor intra-relocation)
- ✅ Als `liveInApplicable: true`, moet hoofdhuurder bestaan
- ✅ Als `liveInApplicable: true`, moet hoofdhuurder niet dezelfde zijn als declarant
- ✅ Verhuisdatum mag niet in het verleden zijn

**Voorbeeld fout:**
```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    {
      "field": "relocators[0]",
      "message": "Person is not suitable for relocation",
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    },
    {
      "field": "newAddress.liveIn.mainOccupant.bsn",
      "message": "Main occupant does not exist"
    }
  ]
}
```

---

### 3.2 Geboorte (Birth)

#### Verplichte Velden
- ✅ `child.firstName` - Verplicht
- ✅ `child.lastName` - Verplicht
- ✅ `child.birthDate` - Verplicht
- ✅ `mother.bsn` - Verplicht

#### Optionele Velden
- ⚪ `referenceId` - Optioneel
- ⚪ `father.bsn` - Optioneel
- ⚪ `child.birthPlace` - Optioneel
- ⚪ `child.gender` - Optioneel
- ⚪ `acknowledgement` - Optioneel
- ⚪ `nameSelection` - Optioneel

#### Business Rules
- ✅ Moeder moet bestaan in BRP
- ✅ Moeder mag niet geblokkeerd zijn
- ✅ Vader (als opgegeven) moet bestaan in BRP
- ✅ Vader (als opgegeven) mag niet geblokkeerd zijn
- ✅ Geboortedatum mag niet in de toekomst zijn
- ✅ Geboorteplaats moet geldig zijn (als opgegeven)
- ✅ Erkenning moet geldig zijn (als opgegeven)
- ✅ Naamkeuze moet geldig zijn (als opgegeven)

**Voorbeeld fout:**
```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    {
      "field": "mother.bsn",
      "message": "Person record is blocked"
    },
    {
      "field": "child.birthDate",
      "message": "Birth date cannot be in the future"
    }
  ]
}
```

---

### 3.3 Partnerschap (Commitment)

#### Verplichte Velden
- ✅ `partner1.bsn` - Verplicht
- ✅ `partner2.bsn` - Verplicht
- ✅ `commitmentDate` - Verplicht

#### Optionele Velden
- ⚪ `referenceId` - Optioneel
- ⚪ `commitmentPlace` - Optioneel

#### Business Rules
- ✅ Partner 1 moet bestaan in BRP
- ✅ Partner 2 moet bestaan in BRP
- ✅ Partner 1 mag niet geblokkeerd zijn
- ✅ Partner 2 mag niet geblokkeerd zijn
- ✅ Partners mogen niet al getrouwd zijn
- ✅ Partners mogen niet dezelfde persoon zijn
- ✅ Partnerschapsdatum mag niet in het verleden zijn
- ✅ Partnerschapsplaats moet geldig zijn (als opgegeven)

**Voorbeeld fout:**
```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    {
      "field": "partner1.bsn",
      "message": "Person is already married"
    },
    {
      "field": "partner2.bsn",
      "message": "Person is already married"
    }
  ]
}
```

---

### 3.4 Overlijden (Death)

#### Verplichte Velden
- ✅ `person.bsn` - Verplicht
- ✅ `deathDate` - Verplicht

#### Optionele Velden
- ⚪ `referenceId` - Optioneel
- ⚪ `deathPlace` - Optioneel
- ⚪ `causeOfDeath` - Optioneel

#### Business Rules
- ✅ Persoon moet bestaan in BRP
- ✅ Persoon mag niet al overleden zijn
- ✅ Overlijdensdatum mag niet in de toekomst zijn
- ✅ Overlijdensplaats moet geldig zijn (als opgegeven)

**Voorbeeld fout:**
```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    {
      "field": "person.bsn",
      "message": "Person is already deceased"
    },
    {
      "field": "deathDate",
      "message": "Death date cannot be in the future"
    }
  ]
}
```

---

## 4. Autorisation Validaties (403 Forbidden)

### 4.1 Client Rechten

**Regels:**
- ✅ Client moet geauthenticeerd zijn (geldig JWT token)
- ✅ Client moet rechten hebben voor deze operatie
- ✅ Client moet bevoegd zijn voor dit dossier type
- ✅ Client moet bevoegd zijn voor deze gemeente

**Database Query:**
```sql
-- Check client rechten
SELECT * FROM client_permissions 
WHERE client_id = :client_id 
AND permission = :permission;

-- Check gemeente autorisatie
SELECT * FROM client_municipalities 
WHERE client_id = :client_id 
AND municipality_code = :municipality_code;
```

**Voorbeeld fout:**
```json
{
  "status": 403,
  "title": "Forbidden",
  "detail": "Insufficient permissions for this operation"
}
```

---

### 4.2 Workflow Status

**Regels:**
- ✅ Dossier moet in correcte status zijn voor mutatie
- ✅ Mutatie mag niet worden uitgevoerd op voltooide dossiers
- ✅ Mutatie mag niet worden uitgevoerd op geannuleerde dossiers

**Voorbeeld fout:**
```json
{
  "status": 403,
  "title": "Forbidden",
  "detail": "Cannot modify dossier in current status"
}
```

---

## 5. Error Response Structuur

### 5.1 Syntactische Fout (400 Bad Request)

```json
{
  "status": 400,
  "title": "Bad Request",
  "detail": "Validation failed",
  "errors": [
    {
      "field": "field.path",
      "message": "Human readable error message"
    }
  ]
}
```

### 5.2 Semantische Fout (422 Unprocessable Entity)

```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    {
      "field": "field.path",
      "message": "Human readable error message",
      "code": "ERROR_CODE",
      "obstructions": ["OBSTRUCTION_TYPE"]
    }
  ]
}
```

**Alternatieve vorm:**
```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    "Veld 'E-mail' ontbreekt",
    "1 van de 2 regels bevatten fouten"
  ]
}
```

### 5.3 Autorisation Fout (403 Forbidden)

```json
{
  "status": 403,
  "title": "Forbidden",
  "detail": "Insufficient permissions"
}
```

---

## 6. Validatie Timing

### Wanneer gebeurt validatie?

1. **Direct na ontvangst** - Syntactische validatie
2. **Na syntactische validatie** - Semantische validatie (met database queries)
3. **Na semantische validatie** - Autorisation validatie
4. **Als alles OK** - Database write

### Wat gebeurt er bij validatie fout?

- ❌ **Geen database write** - Transactie wordt niet gestart
- ✅ **Error response** - Gestructureerde error met details
- ✅ **Geen side effects** - Geen events, geen tasks, niets wordt opgeslagen

---

## 7. Database Queries voor Validatie

### 7.1 BSN Validatie

```sql
-- Check of BSN bestaat
SELECT 
    p.bsn,
    p.status,
    p.overlijdensdatum,
    p.geschorst,
    p.actueel
FROM personen p
WHERE p.bsn = :bsn
AND p.actueel = true;

-- Check obstructions
SELECT 
    CASE 
        WHEN p.status = 'BLOCKED' THEN 'PERSON_RECORD_IS_BLOCKED'
        WHEN p.overlijdensdatum IS NOT NULL THEN 'PERSON_IS_DECEASED'
        WHEN p.geschorst = true THEN 'PERSONLIST_SUSPENDED'
    END as obstruction
FROM personen p
WHERE p.bsn = :bsn;
```

### 7.2 Relocator Validatie

```sql
-- Check relaties
SELECT 
    r.bsn,
    r.relationship_type,
    r.declaration_type,
    r.suitable_for_relocation,
    r.suitable_for,
    r.obstructions
FROM relaties r
WHERE r.declarant_bsn = :declarant_bsn;

-- Check lopende verhuizingen
SELECT 
    d.dossier_id,
    d.status
FROM dossiers d
WHERE d.bsn = :relocator_bsn 
AND d.dossier_type = 'intra_mun_relocation'
AND d.status IN ('INCOMPLETE', 'PROCESSING');

-- Check adres verschil
SELECT 
    CASE 
        WHEN a1.adres != a2.adres THEN 'DIFFERENT_ADDRESS'
    END as obstruction
FROM adressen a1
JOIN adressen a2 ON a2.bsn = :declarant_bsn
WHERE a1.bsn = :relocator_bsn
AND a1.actueel = true
AND a2.actueel = true;
```

### 7.3 Adres Validatie

```sql
-- Check postcode
SELECT * FROM postcodes 
WHERE postcode = :postcode;

-- Check straat
SELECT * FROM straten 
WHERE straatnaam = :straatnaam 
AND postcode = :postcode;

-- Check woonplaats
SELECT * FROM woonplaatsen 
WHERE woonplaats = :woonplaats 
AND postcode = :postcode;

-- Check combinatie
SELECT * FROM adressen 
WHERE straatnaam = :straatnaam
AND huisnummer = :huisnummer
AND postcode = :postcode
AND woonplaats = :woonplaats
AND actueel = true;
```

---

## 8. Validatie Checklist per Mutatie Type

### 8.1 Verhuizing Checklist

**Syntactisch:**
- [ ] JSON is geldig
- [ ] `declarant.bsn` is aanwezig en 9 cijfers
- [ ] `newAddress.postalCode` heeft formaat 1234AB
- [ ] `newAddress.houseNumber` is aanwezig
- [ ] `newAddress.street` is aanwezig
- [ ] `newAddress.city` is aanwezig
- [ ] `relocationDate` is ISO 8601 formaat (indien opgegeven)

**Semantisch:**
- [ ] Declarant BSN bestaat in BRP
- [ ] Declarant is niet geblokkeerd
- [ ] Declarant is niet overleden
- [ ] Alle relocators bestaan in BRP
- [ ] Alle relocators zijn geschikt voor verhuizing
- [ ] Geen relocators hebben obstructions
- [ ] Nieuw adres bestaat in adresregister
- [ ] Nieuw adres is binnen gemeente (voor intra-relocation)
- [ ] Hoofdhuurder bestaat (als `liveInApplicable: true`)
- [ ] Verhuisdatum is niet in verleden

**Autorisation:**
- [ ] Client heeft rechten voor verhuizing
- [ ] Client is bevoegd voor gemeente

---

### 8.2 Geboorte Checklist

**Syntactisch:**
- [ ] JSON is geldig
- [ ] `child.firstName` is aanwezig
- [ ] `child.lastName` is aanwezig
- [ ] `child.birthDate` is ISO 8601 formaat
- [ ] `mother.bsn` is aanwezig en 9 cijfers
- [ ] `father.bsn` is 9 cijfers (indien opgegeven)

**Semantisch:**
- [ ] Moeder BSN bestaat in BRP
- [ ] Moeder is niet geblokkeerd
- [ ] Vader BSN bestaat in BRP (indien opgegeven)
- [ ] Vader is niet geblokkeerd (indien opgegeven)
- [ ] Geboortedatum is niet in toekomst
- [ ] Geboorteplaats bestaat (indien opgegeven)

**Autorisation:**
- [ ] Client heeft rechten voor geboorte
- [ ] Client is bevoegd voor gemeente

---

### 8.3 Partnerschap Checklist

**Syntactisch:**
- [ ] JSON is geldig
- [ ] `partner1.bsn` is aanwezig en 9 cijfers
- [ ] `partner2.bsn` is aanwezig en 9 cijfers
- [ ] `commitmentDate` is ISO 8601 formaat

**Semantisch:**
- [ ] Partner 1 BSN bestaat in BRP
- [ ] Partner 2 BSN bestaat in BRP
- [ ] Partner 1 is niet geblokkeerd
- [ ] Partner 2 is niet geblokkeerd
- [ ] Partner 1 is niet al getrouwd
- [ ] Partner 2 is niet al getrouwd
- [ ] Partners zijn niet dezelfde persoon
- [ ] Partnerschapsdatum is niet in verleden

**Autorisation:**
- [ ] Client heeft rechten voor partnerschap
- [ ] Client is bevoegd voor gemeente

---

## 9. Obstruction Types

### 9.1 Lijst van Obstruction Types

- `EXISTING_RELOCATION_CASE` - Er is al een lopende verhuizing
- `DIFFERENT_ADDRESS` - Persoon woont op ander adres
- `PERSON_IS_DECEASED` - Persoon is overleden
- `PERSON_RECORD_IS_BLOCKED` - Persoon record is geblokkeerd
- `PERSONLIST_SUSPENDED` - Persoon lijst is geschorst
- `RELATIONSHIP_HAS_ENDED` - Relatie is beëindigd
- `NO_PERSON_RECORD_FOUND` - Geen persoon record gevonden

### 9.2 Suitable For Types

- `GENERAL_USE_CASE` - Geschikt voor algemeen gebruik
- `NEW_RELOCATION_CASE` - Geschikt voor nieuwe verhuizing
- `NEW_BRP_EXTRACT_CASE` - Geschikt voor BRP uittreksel
- `NEW_CONFIDENTIALITY_CASE` - Geschikt voor geheimhouding

---

## 10. Export Formaat

### 10.1 JSON Schema voor Validaties

```json
{
  "validations": [
    {
      "type": "syntactic",
      "field": "declarant.bsn",
      "rules": [
        {
          "name": "required",
          "message": "BSN is required"
        },
        {
          "name": "length",
          "value": 9,
          "message": "BSN must be 9 digits"
        },
        {
          "name": "format",
          "pattern": "^\\d{9}$",
          "message": "BSN must contain only digits"
        }
      ]
    },
    {
      "type": "semantic",
      "field": "declarant.bsn",
      "rules": [
        {
          "name": "exists_in_brp",
          "database_query": "SELECT * FROM personen WHERE bsn = :bsn AND actueel = true",
          "message": "BSN does not exist in BRP"
        },
        {
          "name": "not_blocked",
          "database_query": "SELECT * FROM personen WHERE bsn = :bsn AND status = 'BLOCKED'",
          "message": "Person record is blocked",
          "obstruction": "PERSON_RECORD_IS_BLOCKED"
        }
      ]
    }
  ]
}
```

---

## 11. Samenvatting

### Validatie Lagen

1. **Syntactisch (400)** - JSON, formaten, verplichte velden
2. **Semantisch (422)** - Business rules, database checks, obstructions
3. **Autorisation (403)** - Rechten, bevoegdheden, workflow status

### Validatie Timing

- ✅ Direct na ontvangst request
- ✅ Voordat database write
- ✅ Geen side effects bij fout

### Validatie Resultaat

- ✅ Success → Database write + Response
- ❌ Fout → Error response + Geen database write

---

## Referenties

- [vrijBRP Dossiers API Documentatie](https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation)
- [VRJIBRP-VALIDATIE-IN-MUTATIE.md](./VRJIBRP-VALIDATIE-IN-MUTATIE.md)
- [VRJIBRP-MUTATIES-TECHNISCH.md](./VRJIBRP-MUTATIES-TECHNISCH.md)
- [VRJIBRP-SERVER-SIDE-VALIDATIE-UITLEG.md](./VRJIBRP-SERVER-SIDE-VALIDATIE-UITLEG.md)







