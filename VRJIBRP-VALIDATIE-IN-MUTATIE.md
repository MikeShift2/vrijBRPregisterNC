# Validatie in Mutatie Requests - vrijBRP Dossiers API

## Overzicht

**Ja, er zit uitgebreide validatie in mutatie requests**, maar deze gebeurt **server-side** na ontvangst van de request. De validatie gebeurt **niet** in de request zelf, maar door de API server.

---

## 1. Validatie Flow

### Request Flow met Validatie

```
[Client] 
  ↓
[POST Request met JSON body]
  ↓
[API Server ontvangt request]
  ↓
[Laag 1: Syntactische Validatie] ← VALIDATIE START
  ↓ (als OK)
[Laag 2: Semantische Validatie]
  ↓ (als OK)
[Laag 3: Autorisation Validatie]
  ↓ (als OK)
[Business Logic Service]
  ↓
[Database Write]
  ↓
[Success Response]
```

**Als validatie faalt:**
```
[Validatie Fout]
  ↓
[Error Response met details]
  ↓
[Geen database write]
```

---

## 2. Validatie Lagen

### Laag 1: Syntactische Validatie (400 Bad Request)

**Wanneer:** Direct na ontvangst van de request

**Wat wordt gecontroleerd:**
- ✅ JSON is geldig (geen syntax errors)
- ✅ JSON schema validatie (velden bestaan, correcte structuur)
- ✅ Verplichte velden zijn aanwezig
- ✅ Datatype validatie (string, number, boolean, etc.)
- ✅ Formaat validatie:
  - BSN moet 9 cijfers zijn
  - Postcode moet formaat `1234AB` hebben
  - Datum moet ISO 8601 formaat zijn
  - Email moet geldig email formaat zijn

**Voorbeeld Error:**
```json
{
  "status": 400,
  "title": "Bad Request",
  "detail": "Validation failed",
  "errors": [
    {
      "field": "declarant.bsn",
      "message": "BSN must be 9 digits"
    },
    {
      "field": "newAddress.postalCode",
      "message": "Postal code format is invalid (expected: 1234AB)"
    }
  ]
}
```

### Laag 2: Semantische Validatie (422 Unprocessable Entity)

**Wanneer:** Na syntactische validatie

**Wat wordt gecontroleerd:**
- ✅ Business rule validatie
- ✅ RVIG-regels (Rijksdienst voor Identiteitsgegevens)
- ✅ Consistentie checks:
  - BSN bestaat in BRP
  - BSN is niet geblokkeerd
  - Persoon is niet overleden
  - Relocator is geschikt voor verhuizing (`suitableForRelocation: true`)
  - Geen obstructions (bijv. `EXISTING_RELOCATION_CASE`)
- ✅ Relatie validatie:
  - Relocator heeft correcte relatie type
  - Ouders bestaan voor geboorte
  - Partners bestaan voor partnerschap
- ✅ Adres validatie:
  - Postcode bestaat
  - Straat bestaat
  - Woonplaats bestaat
  - Combinatie is geldig

**Voorbeeld Error:**
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
      "field": "declarant.bsn",
      "message": "Person record is blocked"
    }
  ]
}
```

### Laag 3: Autorisation Validatie (403 Forbidden)

**Wanneer:** Na semantische validatie

**Wat wordt gecontroleerd:**
- ✅ Client heeft rechten voor deze operatie
- ✅ Client heeft bevoegdheid voor dit dossier type
- ✅ Workflow status check (mag mutatie worden uitgevoerd in huidige status?)

**Voorbeeld Error:**
```json
{
  "status": 403,
  "title": "Forbidden",
  "detail": "Insufficient permissions for this operation"
}
```

---

## 3. Validatie Voorbeelden per Veld

### BSN Validatie

**Syntactisch:**
- Moet string zijn
- Moet exact 9 cijfers bevatten
- Geen letters of speciale tekens

**Semantisch:**
- BSN moet bestaan in BRP database
- BSN mag niet geblokkeerd zijn (`PERSON_RECORD_IS_BLOCKED`)
- Persoon mag niet overleden zijn (`PERSON_IS_DECEASED`)
- Persoon mag niet geschorst zijn (`PERSONLIST_SUSPENDED`)

**Voorbeeld Error:**
```json
{
  "field": "declarant.bsn",
  "message": "Person record is blocked",
  "obstructions": ["PERSON_RECORD_IS_BLOCKED"]
}
```

### Relocator Validatie

**Syntactisch:**
- Moet object zijn met `bsn` en `relationshipType`
- `bsn` moet geldig BSN zijn
- `relationshipType` moet geldige waarde zijn

**Semantisch:**
- BSN moet bestaan
- Persoon moet `suitableForRelocation: true` zijn
- Geen obstructions:
  - `EXISTING_RELOCATION_CASE` - Er is al een lopende verhuizing
  - `DIFFERENT_ADDRESS` - Persoon woont op ander adres
  - `PERSON_IS_DECEASED` - Persoon is overleden
  - `PERSON_RECORD_IS_BLOCKED` - Persoon record is geblokkeerd
  - `RELATIONSHIP_HAS_ENDED` - Relatie is beëindigd (ex-partner)

**Voorbeeld Error:**
```json
{
  "field": "relocators[0]",
  "message": "Person is not suitable for relocation",
  "obstructions": ["EXISTING_RELOCATION_CASE", "DIFFERENT_ADDRESS"]
}
```

### Adres Validatie

**Syntactisch:**
- Postcode moet formaat `1234AB` hebben (4 cijfers + 2 letters)
- Huisnummer moet nummer of string zijn
- Straat moet string zijn

**Semantisch:**
- Postcode moet bestaan in adresregister
- Straat moet bestaan
- Woonplaats moet bestaan
- Combinatie moet geldig zijn (straat + huisnummer + postcode + woonplaats)

**Voorbeeld Error:**
```json
{
  "field": "newAddress.postalCode",
  "message": "Postal code does not exist"
}
```

### Datum Validatie

**Syntactisch:**
- Moet ISO 8601 formaat zijn (`YYYY-MM-DD` of `YYYY-MM-DDTHH:MM:SSZ`)
- Moet geldige datum zijn (geen 32 januari)

**Semantisch:**
- Verhuisdatum mag niet in het verleden zijn (voor bepaalde mutaties)
- Geboortedatum mag niet in de toekomst zijn
- Datum moet logisch zijn binnen context

**Voorbeeld Error:**
```json
{
  "field": "relocationDate",
  "message": "Relocation date cannot be in the past"
}
```

---

## 4. Validatie Timing

### Wanneer gebeurt validatie?

**Server-side, direct na ontvangst:**

1. **Request ontvangen** → Direct syntactische validatie
2. **Syntactisch OK** → Semantische validatie (database queries)
3. **Semantisch OK** → Autorisation validatie
4. **Alles OK** → Business logic + database write

### Wat gebeurt er bij validatie fout?

- ❌ **Geen database write** - Transactie wordt niet gestart
- ✅ **Error response** - Gestructureerde error met details
- ✅ **Geen side effects** - Geen events, geen tasks, niets wordt opgeslagen

---

## 5. Validatie in Request Body vs. Server-side

### Wat zit NIET in de request?

- ❌ Geen client-side validatie vereist
- ❌ Geen validatie tokens
- ❌ Geen checksums
- ❌ Geen pre-validation

### Wat gebeurt WEL server-side?

- ✅ Volledige syntactische validatie
- ✅ Volledige semantische validatie (met database queries)
- ✅ Volledige autorisation validatie
- ✅ Business rule validatie

---

## 6. Validatie Response Structuur

### Success (geen validatie errors)

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "dossierId": "abc123-def456-ghi789",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

### Syntactische Validatie Fout (400 Bad Request)

```json
{
  "status": 400,
  "title": "Bad Request",
  "detail": "Validation failed",
  "errors": [
    {
      "field": "declarant.bsn",
      "message": "BSN must be 9 digits"
    },
    {
      "field": "newAddress.postalCode",
      "message": "Postal code format is invalid"
    }
  ]
}
```

### Semantische Validatie Fout (422 Unprocessable Entity)

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
    "Veld 'E-mail' ontbreekt",
    "1 van de 2 regels bevatten fouten"
  ]
}
```

---

## 7. Validatie vs. Pre-flight Checks

### Pre-flight Checks (optioneel, client-side)

**Voordat je een mutatie request stuurt, kun je:**

1. **Relaties ophalen** - `GET /api/v1/relatives/{bsn}`
   - Check `suitableForRelocation`
   - Check `obstructions`
   - Check `suitableFor`

2. **Dossier ophalen** - `GET /api/v1/relocations/intra/{dossierId}`
   - Check huidige status
   - Check of mutatie mogelijk is

3. **Tasks ophalen** - `GET /api/v1/tasks?bsn={bsn}`
   - Check of er openstaande tasks zijn

**Maar:** Deze zijn **optioneel**. De server valideert altijd opnieuw.

---

## 8. Validatie Regels per Mutatie Type

### Verhuizing (Intra-relocation)

**Verplichte velden:**
- `declarant.bsn` ✅
- `newAddress.street` ✅
- `newAddress.houseNumber` ✅
- `newAddress.postalCode` ✅
- `newAddress.city` ✅

**Validatie regels:**
- Declarant moet bestaan in BRP
- Declarant mag niet geblokkeerd zijn
- Alle relocators moeten `suitableForRelocation: true` zijn
- Nieuw adres moet geldig zijn
- Als `liveInApplicable: true`, moet hoofdhuurder bestaan

### Geboorte

**Verplichte velden:**
- `child.firstName` ✅
- `child.lastName` ✅
- `child.birthDate` ✅
- `mother.bsn` ✅

**Validatie regels:**
- Moeder moet bestaan in BRP
- Vader (als opgegeven) moet bestaan in BRP
- Geboortedatum mag niet in toekomst zijn
- Geboorteplaats moet geldig zijn

### Partnerschap

**Verplichte velden:**
- `partner1.bsn` ✅
- `partner2.bsn` ✅
- `commitmentDate` ✅

**Validatie regels:**
- Beide partners moeten bestaan in BRP
- Beide partners mogen niet geblokkeerd zijn
- Partners mogen niet al getrouwd zijn
- Datum moet geldig zijn

---

## 9. Validatie Performance

### Wanneer gebeurt validatie?

**Synchroon, direct:**
- Validatie gebeurt **direct** na ontvangst van request
- **Geen** async validatie
- **Geen** background processing
- Response komt **direct** terug (success of error)

### Database Queries tijdens Validatie

**Voor semantische validatie worden database queries uitgevoerd:**
- Check of BSN bestaat
- Check of persoon geblokkeerd is
- Check of relocator geschikt is
- Check of adres geldig is
- Check obstructions

**Dit betekent:**
- Validatie kan **langzamer** zijn dan syntactische validatie
- Database moet **beschikbaar** zijn voor validatie
- Validatie is **niet** alleen client-side

---

## 10. Conclusie

### Validatie in Mutatie Requests

**Ja, er zit uitgebreide validatie in mutatie requests:**

1. ✅ **Syntactische validatie** - Direct na ontvangst
2. ✅ **Semantische validatie** - Met database queries
3. ✅ **Autorisation validatie** - Rechten en bevoegdheden

### Belangrijkste Punten

- **Server-side validatie** - Niet in request zelf, maar door API server
- **Meerdere lagen** - Syntactisch → Semantisch → Autorisation
- **Gestructureerde errors** - Duidelijke foutmeldingen met veld details
- **Geen database write bij fout** - Transactie wordt niet gestart
- **Pre-flight checks optioneel** - Client kan vooraf checken, maar server valideert altijd

### Voor Open Registers Implementatie

**Vereist:**
- ✅ Validatie service bouwen (vrijBRP Logica Service)
- ✅ Syntactische validatie implementeren
- ✅ Semantische validatie implementeren (met database queries)
- ✅ Autorisation validatie implementeren
- ✅ Gestructureerde error responses

---

## Referenties

- [VRJIBRP-MUTATIES-TECHNISCH.md](./VRJIBRP-MUTATIES-TECHNISCH.md)
- [VRJIBRP-API-RESPONSE-FORMAT.md](./VRJIBRP-API-RESPONSE-FORMAT.md)
- [vrijBRP Dossiers API Documentatie](https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation)







