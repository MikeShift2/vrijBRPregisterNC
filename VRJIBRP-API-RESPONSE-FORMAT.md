# vrijBRP Dossiers API - Response Formaat

## Overzicht

De vrijBRP Dossiers API retourneert alle responses in **JSON formaat** met `Content-Type: application/json`.

---

## 1. Content-Type Header

**Alle responses:**
```http
Content-Type: application/json
```

**Security Headers (altijd aanwezig):**
```http
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Cache-Control: no-cache, no-store, max-age=0, must-revalidate
Pragma: no-cache
Expires: 0
X-Frame-Options: DENY
```

---

## 2. Success Responses

### 2.1 POST - Resource Aangemaakt (201 Created)

**Voorbeeld: Verhuizing aanmaken**
```http
HTTP/1.1 201 Created
Content-Type: application/json
Content-Length: 156

{
  "dossierId": "abc123-def456-ghi789",
  "referenceId": "REF-12345",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

**Voorbeeld: Geboorte aanmaken**
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "dossierId": "geb-abc123-def456",
  "referenceId": "GEB-12345",
  "status": "incomplete",
  "dossierType": "birth",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

### 2.2 GET - Resource Ophalen (200 OK)

**Voorbeeld: Verhuizing ophalen**
```http
HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: 2661

{
  "dossierId": "abc123-def456-ghi789",
  "referenceId": "REF-12345",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "declarant": {
    "bsn": "000000048"
  },
  "relocators": [
    {
      "bsn": "000000048",
      "relationshipType": "REGISTERED"
    },
    {
      "bsn": "000000103",
      "relationshipType": "CHILD"
    }
  ],
  "oldAddress": {
    "street": "Hoofdstraat",
    "houseNumber": "1",
    "houseNumberAddition": "A",
    "postalCode": "1234AB",
    "city": "Amsterdam"
  },
  "newAddress": {
    "street": "Nieuwstraat",
    "houseNumber": "10",
    "postalCode": "5678CD",
    "city": "Rotterdam",
    "liveIn": {
      "liveInApplicable": true,
      "consent": "PENDING",
      "mainOccupant": {
        "bsn": "000000085"
      }
    }
  },
  "relocationDate": "2024-01-15",
  "createdAt": "2024-01-10T10:30:00Z",
  "updatedAt": "2024-01-10T10:30:00Z"
}
```

**Voorbeeld: Relaties ophalen**
```http
HTTP/1.1 200 OK
Content-Type: application/json
Content-Length: 2661

{
  "relatives": [
    {
      "person": {
        "bsn": "000000048",
        "age": 40
      },
      "relationshipType": "REGISTERED",
      "declarationType": "REGISTERED",
      "suitableForRelocation": false,
      "suitableFor": ["GENERAL_USE_CASE"],
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    },
    {
      "person": {
        "bsn": "000000085",
        "age": 39
      },
      "relationshipType": "PARTNER",
      "declarationType": "PARTNER",
      "suitableForRelocation": false,
      "suitableFor": ["GENERAL_USE_CASE"],
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

**Voorbeeld: Tasks ophalen**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "tasks": [
    {
      "taskId": "task-123",
      "dossierId": "abc123-def456-ghi789",
      "dossierType": "intra_mun_relocation",
      "taskType": "relocation_consent",
      "status": "planned",
      "bsn": "000000085",
      "description": "Toestemming hoofdhuurder vereist",
      "createdAt": "2024-01-10T10:30:00Z",
      "dueDate": "2024-01-17T10:30:00Z"
    }
  ]
}
```

### 2.3 PUT - Resource Bijgewerkt (200 OK)

**Voorbeeld: Partnerschap bijwerken**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "dossierId": "part-abc123-def456",
  "referenceId": "PART-12345",
  "status": "complete",
  "dossierType": "commitment",
  "updatedAt": "2024-01-12T14:20:00Z"
}
```

### 2.4 DELETE - Resource Verwijderd (200 OK)

**Voorbeeld: Partnerschap annuleren**
```http
HTTP/1.1 200 OK
Content-Length: 0
```

**Let op:** DELETE responses hebben vaak een lege body (Content-Length: 0).

### 2.5 POST - Actie Uitgevoerd (200 OK)

**Voorbeeld: Toestemming hoofdhuurder**
```http
HTTP/1.1 200 OK
Content-Length: 0
```

---

## 3. Error Responses

Alle error responses volgen een gestandaardiseerd formaat:

### 3.1 400 Bad Request - Syntactische Validatie Fout

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "status": 400,
  "title": "Bad Request",
  "detail": "Validation failed",
  "errors": [
    {
      "field": "newAddress.postalCode",
      "message": "Postal code is invalid"
    },
    {
      "field": "relocators[0].bsn",
      "message": "Person is not suitable for relocation",
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

### 3.2 401 Unauthorized - Authenticatie Fout

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "status": 401,
  "title": "Unauthorized",
  "detail": "Invalid or expired token"
}
```

### 3.3 403 Forbidden - Autorisation Fout

```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "status": 403,
  "title": "Forbidden",
  "detail": "Insufficient permissions"
}
```

### 3.4 404 Not Found - Resource Niet Gevonden

```http
HTTP/1.1 404 Not Found
Content-Type: application/json

{
  "status": 404,
  "title": "Not Found",
  "detail": "Dossier not found"
}
```

### 3.5 422 Unprocessable Entity - Semantische Validatie Fout

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

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

**Alternatieve vorm (met field details):**
```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Business rule violation",
  "errors": [
    {
      "field": "relocators[0]",
      "message": "Person is not suitable for relocation",
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

### 3.6 500 Internal Server Error - Server Fout

```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "status": 500,
  "title": "Internal Server Error",
  "detail": "An unexpected error occurred"
}
```

---

## 4. Response Structuur Overzicht

### 4.1 Success Response Structuur

**POST (201 Created):**
```json
{
  "dossierId": "string (UUID)",
  "referenceId": "string (optioneel)",
  "status": "string",
  "dossierType": "string",
  "createdAt": "ISO 8601 datetime"
}
```

**GET (200 OK):**
- Volledige resource data in JSON object
- Structuur verschilt per endpoint type

**PUT (200 OK):**
- Meestal zelfde structuur als POST response
- Bevat `updatedAt` in plaats van `createdAt`

**DELETE (200 OK):**
- Lege body (Content-Length: 0)

### 4.2 Error Response Structuur

**Standaard formaat:**
```json
{
  "status": "number (HTTP status code)",
  "title": "string (korte beschrijving)",
  "detail": "string (gedetailleerde beschrijving)",
  "errors": [
    "string (algemene foutmelding)"
    // OF
    {
      "field": "string (veld pad)",
      "message": "string (foutmelding)",
      "code": "string (optioneel, error code)",
      "obstructions": ["string"] // (optioneel, voor relaties)
    }
  ]
}
```

---

## 5. Datum/Tijd Formaat

**Formaat:** ISO 8601 (RFC 3339)

**Voorbeelden:**
- `"2024-01-10T10:30:00Z"` - UTC tijd
- `"2024-01-10T10:30:00+01:00"` - Met timezone offset
- `"2024-01-15"` - Alleen datum (voor datums zonder tijd)

---

## 6. Dossier ID Formaat

**Formaat:** UUID (Universally Unique Identifier)

**Voorbeelden:**
- `"abc123-def456-ghi789"`
- `"geb-abc123-def456"`
- `"part-abc123-def456"`

**Let op:** Het formaat kan variëren, maar is altijd een string.

---

## 7. Status Waarden

### Dossier Status
- `"incomplete"` - Dossier is aangemaakt maar nog niet compleet
- `"complete"` - Dossier is compleet en klaar voor verwerking
- `"processing"` - Dossier wordt verwerkt
- `"completed"` - Dossier is succesvol verwerkt
- `"rejected"` - Dossier is afgewezen
- `"cancelled"` - Dossier is geannuleerd

### Task Status
- `"planned"` - Task is gepland maar nog niet gestart
- `"in_progress"` - Task is in behandeling
- `"done"` - Task is voltooid

### Consent Status
- `"PENDING"` - Toestemming is nog niet gegeven
- `"GRANTED"` - Toestemming is gegeven
- `"DENIED"` - Toestemming is geweigerd

---

## 8. Voorbeelden per Endpoint Type

### 8.1 Relaties Endpoint

**GET /api/v1/relatives/{bsn}**
```json
{
  "relatives": [
    {
      "person": {
        "bsn": "000000048",
        "age": 40
      },
      "relationshipType": "REGISTERED",
      "declarationType": "REGISTERED",
      "suitableForRelocation": false,
      "suitableFor": ["GENERAL_USE_CASE"],
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

### 8.2 Verhuizing Endpoints

**POST /api/v1/relocations/intra**
```json
{
  "dossierId": "abc123-def456-ghi789",
  "referenceId": "REF-12345",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

**GET /api/v1/relocations/intra/{dossierId}**
```json
{
  "dossierId": "abc123-def456-ghi789",
  "referenceId": "REF-12345",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "declarant": {...},
  "relocators": [...],
  "oldAddress": {...},
  "newAddress": {...},
  "relocationDate": "2024-01-15",
  "createdAt": "2024-01-10T10:30:00Z",
  "updatedAt": "2024-01-10T10:30:00Z"
}
```

### 8.3 Geboorte Endpoints

**POST /api/v1/birth**
```json
{
  "dossierId": "geb-abc123-def456",
  "referenceId": "GEB-12345",
  "status": "incomplete",
  "dossierType": "birth",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

### 8.4 Partnerschap Endpoints

**POST /api/v1/commitment**
```json
{
  "dossierId": "part-abc123-def456",
  "referenceId": "PART-12345",
  "status": "incomplete",
  "dossierType": "commitment",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

**PUT /api/v1/commitment/{dossierId}**
```json
{
  "dossierId": "part-abc123-def456",
  "referenceId": "PART-12345",
  "status": "complete",
  "dossierType": "commitment",
  "updatedAt": "2024-01-12T14:20:00Z"
}
```

**DELETE /api/v1/commitment/{dossierId}**
```http
HTTP/1.1 200 OK
Content-Length: 0
```

---

## 9. Vergelijking met Open Registers

### Wat is hetzelfde

| Aspect | vrijBRP Dossiers API | Open Registers |
|--------|---------------------|----------------|
| **Content-Type** | `application/json` | `application/json` |
| **JSON formaat** | ✅ | ✅ |
| **ISO 8601 datums** | ✅ | ✅ |
| **UUID voor IDs** | ✅ | ✅ |
| **Error structuur** | Gestructureerd | Gestructureerd |

### Wat is anders

| Aspect | vrijBRP Dossiers API | Open Registers |
|--------|---------------------|----------------|
| **Response structuur** | Dossier-gebaseerd | Resource-gebaseerd |
| **Error formaat** | `status`, `title`, `detail` | Kan variëren |
| **Security headers** | Veel headers | Basis headers |

---

## 10. Samenvatting

### Response Formaat

- **Content-Type:** `application/json`
- **Structuur:** JSON objecten
- **Datum formaat:** ISO 8601 (RFC 3339)
- **ID formaat:** UUID (string)
- **Error formaat:** Gestructureerd met `status`, `title`, `detail`, `errors`

### HTTP Status Codes

- `200 OK` - Succesvolle GET/PUT/DELETE
- `201 Created` - Succesvolle POST (nieuwe resource)
- `400 Bad Request` - Syntactische validatie fout
- `401 Unauthorized` - Authenticatie fout
- `403 Forbidden` - Autorisation fout
- `404 Not Found` - Resource niet gevonden
- `422 Unprocessable Entity` - Semantische validatie fout
- `500 Internal Server Error` - Server fout

### Belangrijkste Kenmerken

1. **Altijd JSON** - Alle responses zijn JSON, geen XML of andere formaten
2. **Gestructureerde errors** - Errors hebben altijd dezelfde structuur
3. **Security headers** - Veel security headers worden altijd meegestuurd
4. **ISO 8601 datums** - Datums zijn altijd in ISO 8601 formaat
5. **UUID IDs** - Dossier IDs zijn altijd UUIDs (strings)

---

## Referenties

- [vrijBRP Dossiers API Documentatie](https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation)
- [VRJIBRP-MUTATIES-TECHNISCH.md](./VRJIBRP-MUTATIES-TECHNISCH.md)







