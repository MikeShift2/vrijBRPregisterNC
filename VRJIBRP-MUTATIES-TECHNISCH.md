# Technische Analyse: Mutaties in vrijBRP Dossiers API

## Overzicht

Dit document beschrijft hoe mutaties technisch werken in de vrijBRP Dossiers API, gebaseerd op de API documentatie en architectuur.

**Bron:** https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation

---

## 1. Mutatie Flow Overzicht

### Algemene Flow

```
[Client] → [POST Request] → [vrijBRP Dossiers API] → [Validatie] → [Database] → [Response]
                ↓                                              ↓
         [JWT Token]                                    [Dossier ID]
                ↓                                              ↓
         [Authenticatie]                              [Eventing/Tasks]
```

### Stappen in Mutatie Proces

1. **Authenticatie** - Client authenticatie met JWT Bearer token
2. **Request** - POST/PUT/DELETE request met mutatie data
3. **Validatie** - Server-side validatie van data en business rules
4. **Persistentie** - Opslaan in database
5. **Response** - Retourneren van dossier ID en status
6. **Eventing** - Genereren van events en tasks voor workflows

---

## 2. Authenticatie

### JWT Bearer Token

**Methode:** OAuth 2.0 Client Credentials Flow

**Request:**
```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials&client_id=sim&client_secret=VZV970qmdVY86g@
```

**Response:**
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

**Gebruik in Requests:**
```http
Authorization: Bearer eyJhbGciOiJSUzI1NiJ9...
```

---

## 3. Mutatie Endpoints

### 3.1 Verhuizing (Intra-relocation)

#### POST - Nieuwe Verhuizing Aanmaken

**Endpoint:** `POST /api/v1/relocations/intra`

**Request Headers:**
```http
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body (Complete):**
```json
{
  "referenceId": "REF-12345",
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
      "mainOccupant": {
        "bsn": "000000085"
      }
    }
  },
  "relocationDate": "2024-01-15"
}
```

**Request Body (Minimal):**
```json
{
  "declarant": {
    "bsn": "000000048"
  },
  "newAddress": {
    "street": "Nieuwstraat",
    "houseNumber": "10",
    "postalCode": "5678CD",
    "city": "Rotterdam"
  }
}
```

**Response (Success - 201 Created):**
```json
{
  "dossierId": "abc123-def456-ghi789",
  "referenceId": "REF-12345",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

**Response (Error - 400 Bad Request):**
```json
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

**Response (Error - 422 Unprocessable Entity):**
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

#### GET - Verhuizing Ophalen

**Endpoint:** `GET /api/v1/relocations/intra/{dossierId}`

**Response:**
```json
{
  "dossierId": "abc123-def456-ghi789",
  "referenceId": "REF-12345",
  "status": "incomplete",
  "dossierType": "intra_mun_relocation",
  "declarant": {
    "bsn": "000000048"
  },
  "relocators": [...],
  "oldAddress": {...},
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

#### POST - Toestemming Hoofdhuurder

**Endpoint:** `POST /api/v1/relocations/intra/{dossierId}/lodging-consent`

**Request Body:**
```json
{
  "consent": true,
  "consentDate": "2024-01-12"
}
```

**Response (200 OK):**
```http
HTTP/1.1 200 OK
Content-Length: 0
```

---

### 3.2 Geboorte

#### POST - Nieuwe Geboorte Aanmaken

**Endpoint:** `POST /api/v1/birth`

**Request Body:**
```json
{
  "referenceId": "GEB-12345",
  "child": {
    "firstName": "Jan",
    "lastName": "Jansen",
    "birthDate": "2024-01-10",
    "birthPlace": "Amsterdam",
    "gender": "M"
  },
  "mother": {
    "bsn": "000000048"
  },
  "father": {
    "bsn": "000000085"
  },
  "acknowledgement": {
    "acknowledged": true,
    "acknowledger": {
      "bsn": "000000085"
    }
  },
  "nameSelection": {
    "selected": true,
    "lastName": "Jansen"
  }
}
```

**Response:**
```json
{
  "dossierId": "geb-abc123-def456",
  "referenceId": "GEB-12345",
  "status": "incomplete",
  "dossierType": "birth",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

---

### 3.3 Partnerschap

#### POST - Nieuw Partnerschap

**Endpoint:** `POST /api/v1/commitment`

**Request Body:**
```json
{
  "referenceId": "PART-12345",
  "partner1": {
    "bsn": "000000048"
  },
  "partner2": {
    "bsn": "000000085"
  },
  "commitmentDate": "2024-01-15",
  "commitmentPlace": "Amsterdam"
}
```

#### PUT - Partnerschap Bijwerken

**Endpoint:** `PUT /api/v1/commitment/{dossierId}`

**Request Body:**
```json
{
  "commitmentDate": "2024-01-20",
  "commitmentPlace": "Rotterdam"
}
```

#### DELETE - Partnerschap Annuleren

**Endpoint:** `DELETE /api/v1/commitment/{dossierId}`

**Response (200 OK):**
```http
HTTP/1.1 200 OK
Content-Length: 0
```

---

## 4. Validatie Proces

### 4.1 Validatie Lagen

#### Laag 1: Syntactische Validatie
- JSON schema validatie
- Verplichte velden check
- Datatype validatie
- Formaat validatie (BSN, postcode, etc.)

#### Laag 2: Semantische Validatie
- Business rule validatie
- RVIG-regels
- Consistentie checks
- Relatie validatie

#### Laag 3: Autorisation Validatie
- Bevoegdheid check
- Rechten verificatie
- Workflow status check

### 4.2 Validatie Voorbeelden

**BSN Validatie:**
- Moet 9 cijfers zijn
- Moet bestaan in BRP
- Moet niet geblokkeerd zijn

**Relocator Validatie:**
- Moet `suitableForRelocation: true` zijn
- Geen obstructions
- Correcte relatie type

**Adres Validatie:**
- Postcode formaat (1234AB)
- Straat bestaat
- Woonplaats bestaat
- Combinatie is geldig

### 4.3 Error Response Structuur

**Structuur:**
```json
{
  "status": 400,
  "title": "Bad Request",
  "detail": "Validation failed",
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

---

## 5. Dossier Status Tracking

### Status Waarden

- `incomplete` - Dossier is aangemaakt maar nog niet compleet
- `complete` - Dossier is compleet en klaar voor verwerking
- `processing` - Dossier wordt verwerkt
- `completed` - Dossier is succesvol verwerkt
- `rejected` - Dossier is afgewezen
- `cancelled` - Dossier is geannuleerd

### Status Transitions

```
incomplete → complete → processing → completed
     ↓           ↓
  rejected    cancelled
```

---

## 6. Tasks Systeem

### Task Types

- `relocation_consent` - Toestemming hoofdhuurder voor verhuizing
- `birth_acknowledgement` - Erkenning geboorte
- `document_upload` - Document upload vereist
- `review` - Review vereist

### Task Status

- `planned` - Task is gepland maar nog niet gestart
- `in_progress` - Task is in behandeling
- `done` - Task is voltooid

### Task Endpoint

**GET /api/v1/tasks**

**Query Parameters:**
- `bsn` - Filter op BSN
- `taskType` - Filter op task type
- `status` - Filter op status (planned, in_progress, done)

**Response:**
```json
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

### Task Flow voor Verhuizing

1. **Verhuizing aangemaakt** → Task `relocation_consent` wordt aangemaakt (status: `planned`)
2. **Hoofdhuurder logt in** → Task wordt gevonden via `GET /api/v1/tasks?bsn={bsn}`
3. **Toestemming gegeven** → `POST /api/v1/relocations/intra/{dossierId}/lodging-consent`
4. **Task voltooid** → Task status wordt `done`

---

## 7. Database Persistente Structuur

### Dossier Tabel (Conceptueel)

```sql
CREATE TABLE dossiers (
    dossier_id UUID PRIMARY KEY,
    reference_id VARCHAR(255),
    dossier_type VARCHAR(50), -- 'intra_mun_relocation', 'birth', 'commitment', etc.
    status VARCHAR(50), -- 'incomplete', 'complete', 'processing', etc.
    data JSONB, -- Volledige dossier data
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    created_by VARCHAR(255)
);
```

### Tasks Tabel (Conceptueel)

```sql
CREATE TABLE tasks (
    task_id UUID PRIMARY KEY,
    dossier_id UUID REFERENCES dossiers(dossier_id),
    task_type VARCHAR(50),
    status VARCHAR(50), -- 'planned', 'in_progress', 'done'
    bsn VARCHAR(9),
    description TEXT,
    created_at TIMESTAMP,
    due_date TIMESTAMP,
    completed_at TIMESTAMP
);
```

### Mutatie Historie (Conceptueel)

```sql
CREATE TABLE mutation_history (
    mutation_id UUID PRIMARY KEY,
    dossier_id UUID REFERENCES dossiers(dossier_id),
    mutation_type VARCHAR(50), -- 'create', 'update', 'delete'
    old_value JSONB,
    new_value JSONB,
    created_at TIMESTAMP,
    created_by VARCHAR(255)
);
```

---

## 8. Eventing & Notificaties

### Events bij Mutaties

**Event Types:**
- `dossier.created` - Dossier aangemaakt
- `dossier.updated` - Dossier bijgewerkt
- `dossier.completed` - Dossier voltooid
- `dossier.rejected` - Dossier afgewezen
- `task.created` - Task aangemaakt
- `task.completed` - Task voltooid

### Event Structuur

```json
{
  "eventId": "event-123",
  "eventType": "dossier.created",
  "dossierId": "abc123-def456-ghi789",
  "dossierType": "intra_mun_relocation",
  "timestamp": "2024-01-10T10:30:00Z",
  "data": {
    "referenceId": "REF-12345",
    "status": "incomplete"
  }
}
```

---

## 9. Technische Implementatie Details

### 9.1 Request Processing Flow

```
1. [HTTP Request] 
   ↓
2. [Authentication Middleware] - Valideer JWT token
   ↓
3. [Authorization Middleware] - Check rechten
   ↓
4. [Request Validation] - JSON schema + syntactische validatie
   ↓
5. [Business Logic Service] - Semantische validatie + transformatie
   ↓
6. [Database Transaction] - Atomic write
   ↓
7. [Event Publisher] - Publiceer events
   ↓
8. [Task Creator] - Maak tasks aan indien nodig
   ↓
9. [Response Builder] - Bouw response
   ↓
10. [HTTP Response]
```

### 9.2 Transactie Management

**Atomic Operations:**
- Dossier aanmaken + Task aanmaken = 1 transactie
- Dossier bijwerken + Historie opslaan = 1 transactie
- Toestemming geven + Task voltooien = 1 transactie

**Rollback Scenario's:**
- Validatie faalt → Geen database write
- Database error → Rollback transactie
- Event publishing faalt → Rollback transactie (of compensatie)

### 9.3 Data Transformatie

**API Format → Database Format:**

```json
// API Request
{
  "newAddress": {
    "street": "Nieuwstraat",
    "houseNumber": "10",
    "postalCode": "5678CD"
  }
}

// Database Format (conceptueel)
{
  "adres": {
    "straatnaam": "Nieuwstraat",
    "huisnummer": 10,
    "postcode": "5678CD",
    "c_straat": 12345,
    "c_wpl": 67890
  }
}
```

**Database Format → API Format:**

Omgekeerde transformatie bij GET requests.

---

## 10. Error Handling

### HTTP Status Codes

- `200 OK` - Succesvolle GET/PUT/DELETE
- `201 Created` - Succesvolle POST (nieuwe resource)
- `400 Bad Request` - Syntactische validatie fout
- `401 Unauthorized` - Authenticatie fout
- `403 Forbidden` - Autorisation fout
- `404 Not Found` - Resource niet gevonden
- `422 Unprocessable Entity` - Semantische validatie fout
- `500 Internal Server Error` - Server error

### Error Response Voorbeelden

**400 Bad Request:**
```json
{
  "status": 400,
  "title": "Bad Request",
  "detail": "Invalid BSN format",
  "errors": [
    {
      "field": "declarant.bsn",
      "message": "BSN must be 9 digits"
    }
  ]
}
```

**422 Unprocessable Entity:**
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
    }
  ]
}
```

---

## 11. Vergelijking met Open Registers

### Wat werkt hetzelfde

| Aspect | vrijBRP Dossiers API | Open Registers |
|--------|---------------------|----------------|
| **POST voor aanmaken** | ✅ | ✅ Mogelijk |
| **PUT voor bijwerken** | ✅ | ✅ Mogelijk |
| **DELETE voor verwijderen** | ✅ | ✅ Mogelijk |
| **Dossier ID retourneren** | ✅ | ✅ UUID |
| **Status tracking** | ✅ | ✅ Via object velden |
| **Historie/versiebeheer** | ✅ | ✅ Out-of-the-box |

### Wat werkt anders

| Aspect | vrijBRP Dossiers API | Open Registers |
|--------|---------------------|----------------|
| **Validatie** | ✅ Server-side | ⚠️ Moet worden gebouwd |
| **Tasks systeem** | ✅ Geïntegreerd | ❌ Moet worden gebouwd |
| **Eventing** | ✅ Geïntegreerd | ✅ Out-of-the-box |
| **Workflow engine** | ✅ Geïntegreerd | ❌ Moet worden gebouwd |
| **RVIG validatie** | ✅ Geïntegreerd | ❌ Moet worden gebouwd |

### Wat moet worden gebouwd voor Open Registers

1. **Validatie Service** - vrijBRP Logica Service voor RVIG-validaties
2. **Tasks Systeem** - Task tracking en workflow orchestration
3. **Workflow Engine** - Procesorkestratie bovenop Open Register
4. **Mutatie Endpoints** - POST/PUT/DELETE endpoints in controller
5. **Error Handling** - Gestandaardiseerde error responses

---

## 12. Conclusie

### Technische Flow Samenvatting

1. **Client** → Authenticatie met JWT Bearer token
2. **API** → Valideer request (syntactisch + semantisch)
3. **Service** → Voer business logic uit
4. **Database** → Persisteer in transactie
5. **Events** → Publiceer events voor subscribers
6. **Tasks** → Maak tasks aan indien nodig
7. **Response** → Retourneer dossier ID en status

### Belangrijkste Technische Aspecten

- **Atomic Transactions** - Mutaties zijn atomair
- **Validatie Lagen** - Syntactisch → Semantisch → Autorisation
- **Status Tracking** - Dossier status wordt bijgehouden
- **Tasks Systeem** - Workflow orchestration via tasks
- **Eventing** - Events voor notificaties en synchronisatie
- **Error Handling** - Gestandaardiseerde error responses

### Implementatie in Open Registers

**Mogelijk:** ✅ Ja, maar vereist:
- Mutatie-endpoints implementeren
- Validatie service bouwen
- Tasks systeem bouwen
- Workflow engine bouwen

**Aanbeveling:** Start met mutatie-endpoints en validatie service, daarna tasks en workflow.

---

## Referenties

- [vrijBRP Dossiers API Documentatie](https://vrijbrp-ediensten.simgroep-test.nl/dossiers/documentation)
- [OPENREGISTER-BRP-ARCHITECTUUR.md](./OPENREGISTER-BRP-ARCHITECTUUR.md)
- [VRJIBRP-DOSSIERS-API-VERGELIJKING.md](./VRJIBRP-DOSSIERS-API-VERGELIJKING.md)







