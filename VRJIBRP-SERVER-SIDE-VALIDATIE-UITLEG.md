# Server-Side Validatie - Uitleg

## Wat betekent "Server-Side"?

**Server-side** betekent dat de validatie gebeurt **op de server** (de API server), **niet** op de client (de browser/app die de request stuurt).

---

## Client vs. Server

### Client-Side (in de browser/app)

```
[Browser/App] 
  ↓
[JavaScript validatie]
  ↓
[Als OK → Stuur request]
```

**Voorbeelden:**
- Form validatie in JavaScript
- Check of velden zijn ingevuld
- Check of email formaat klopt
- Check of BSN 9 cijfers heeft

**Probleem:** Client-side validatie kan worden omzeild!

### Server-Side (op de API server)

```
[Browser/App] 
  ↓
[Stuur request naar API]
  ↓
[API Server ontvangt request]
  ↓
[Server valideert] ← HIER gebeurt het!
  ↓
[Als OK → Database write]
[Als fout → Error response]
```

**Voordeel:** Server-side validatie kan **niet** worden omzeild!

---

## Wat gebeurt er precies Server-Side?

### Stap-voor-stap proces

#### 1. Request komt aan op server

```http
POST /api/v1/relocations/intra HTTP/1.1
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJSUzI1NiJ9...

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

#### 2. Server ontvangt request

**Waar:** Op de vrijBRP Dossiers API server (Java/Spring Boot applicatie)

**Wat gebeurt er:**
- Request wordt ontvangen door de web server
- Request wordt doorgestuurd naar de applicatie
- Applicatie start validatie proces

#### 3. Syntactische Validatie (op server)

**Waar:** In de API applicatie code

**Wat gebeurt er:**
```java
// Pseudo-code voorbeeld
if (request.getDeclarant().getBsn().length() != 9) {
    return error("BSN must be 9 digits");
}

if (!isValidPostalCode(request.getNewAddress().getPostalCode())) {
    return error("Postal code format invalid");
}
```

**Geen database query nodig** - Alleen checken van de request data zelf.

#### 4. Semantische Validatie (op server, met database)

**Waar:** In de API applicatie code, maar met database queries

**Wat gebeurt er:**

**BSN bestaat in BRP:**
```java
// Pseudo-code voorbeeld
Person person = database.findPersonByBsn("000000048");
if (person == null) {
    return error("BSN does not exist in BRP");
}
```

**Database query wordt uitgevoerd:**
```sql
SELECT * FROM personen WHERE bsn = '000000048';
```

**Persoon is niet geblokkeerd:**
```java
if (person.isBlocked()) {
    return error("Person record is blocked");
}
```

**Database query wordt uitgevoerd:**
```sql
SELECT * FROM personen WHERE bsn = '000000048' AND status = 'BLOCKED';
```

**Relocator is geschikt:**
```java
// Haal relaties op
Relatives relatives = getRelatives("000000048");
Relocator relocator = findRelocator(relatives, "000000103");

if (!relocator.isSuitableForRelocation()) {
    return error("Person is not suitable for relocation", 
                 relocator.getObstructions());
}
```

**Database queries worden uitgevoerd:**
```sql
-- Check relaties
SELECT * FROM relaties WHERE bsn = '000000048';

-- Check obstructions
SELECT * FROM verhuizingen WHERE bsn = '000000103' AND status = 'INCOMPLETE';
```

**Geen obstructions:**
```java
List<Obstruction> obstructions = checkObstructions(relocator);
if (!obstructions.isEmpty()) {
    return error("Person has obstructions", obstructions);
}
```

**Database queries worden uitgevoerd:**
```sql
-- Check lopende verhuizingen
SELECT * FROM verhuizingen 
WHERE bsn = '000000103' 
AND status IN ('INCOMPLETE', 'PROCESSING');

-- Check of persoon overleden is
SELECT * FROM personen 
WHERE bsn = '000000103' 
AND overlijdensdatum IS NOT NULL;

-- Check of persoon geblokkeerd is
SELECT * FROM personen 
WHERE bsn = '000000103' 
AND status = 'BLOCKED';
```

#### 5. Autorisation Validatie (op server)

**Waar:** In de API applicatie code

**Wat gebeurt er:**
```java
// Check of client rechten heeft
if (!hasPermission(client, "CREATE_RELOCATION")) {
    return error("Insufficient permissions");
}

// Check of client bevoegd is voor deze gemeente
if (!isAuthorizedForMunicipality(client, municipality)) {
    return error("Not authorized for this municipality");
}
```

**Database queries worden uitgevoerd:**
```sql
-- Check client rechten
SELECT * FROM client_permissions 
WHERE client_id = 'sim' 
AND permission = 'CREATE_RELOCATION';

-- Check gemeente autorisatie
SELECT * FROM client_municipalities 
WHERE client_id = 'sim' 
AND municipality_code = '1234';
```

#### 6. Als alles OK → Database Write

**Waar:** Op de database server

**Wat gebeurt er:**
```java
// Start database transactie
beginTransaction();

// Schrijf dossier
insertDossier(dossier);

// Maak tasks aan
createTasks(dossier);

// Commit transactie
commitTransaction();
```

**Database queries worden uitgevoerd:**
```sql
-- Insert dossier
INSERT INTO dossiers (dossier_id, reference_id, status, data) 
VALUES ('abc123', 'REF-12345', 'incomplete', '{"declarant": {...}}');

-- Insert tasks
INSERT INTO tasks (task_id, dossier_id, task_type, status) 
VALUES ('task-123', 'abc123', 'relocation_consent', 'planned');
```

#### 7. Response terug naar client

**Waar:** Van server naar client

**Wat gebeurt er:**
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "dossierId": "abc123-def456-ghi789",
  "status": "incomplete",
  "createdAt": "2024-01-10T10:30:00Z"
}
```

---

## Waarom Server-Side?

### 1. Security - Kan niet worden omzeild

**Client-side validatie:**
```javascript
// In browser JavaScript
if (bsn.length !== 9) {
    alert("BSN moet 9 cijfers zijn");
    return; // Stopt hier
}
// Maar gebruiker kan JavaScript uitschakelen!
```

**Server-side validatie:**
```java
// Op server
if (bsn.length() != 9) {
    return error("BSN must be 9 digits");
}
// Kan NIET worden omzeild!
```

### 2. Database Checks - Alleen server heeft toegang

**Client kan niet:**
- Direct database queries uitvoeren
- Checken of BSN bestaat
- Checken of persoon geblokkeerd is
- Checken obstructions

**Server kan wel:**
- Database queries uitvoeren
- Alle checks doen
- Volledige validatie uitvoeren

### 3. Business Rules - Complexe logica

**Client kan niet:**
- Complexe business rules implementeren
- RVIG-regels checken
- Relatie validatie uitvoeren

**Server kan wel:**
- Alle business rules implementeren
- Complexe validatie logica
- Volledige controle

---

## Waar draait de Server-Side Code?

### vrijBRP Dossiers API Server

**Infrastructuur:**
```
[Internet]
  ↓
[Load Balancer]
  ↓
[API Server 1] ← Hier draait de code
[API Server 2] ← Hier draait de code
[API Server 3] ← Hier draait de code
  ↓
[Database Server]
```

**Server Code:**
- Java/Spring Boot applicatie
- Draait op server machines (niet op client)
- Heeft toegang tot database
- Kan alle validatie uitvoeren

### Code Locatie

**Server-side code:**
```
/opt/vrijbrp/api/
  ├── src/
  │   ├── controllers/
  │   │   └── RelocationController.java ← Hier gebeurt validatie
  │   ├── services/
  │   │   ├── ValidationService.java ← Validatie logica
  │   │   └── BusinessLogicService.java ← Business rules
  │   └── repositories/
  │       └── PersonRepository.java ← Database queries
  └── application.properties
```

**Client-side code (browser):**
```
/var/www/html/
  └── app.js ← Alleen UI, geen validatie!
```

---

## Wat gebeurt er precies bij Semantische Validatie?

### Voorbeeld: Relocator Validatie

**Request:**
```json
{
  "relocators": [
    {
      "bsn": "000000103",
      "relationshipType": "CHILD"
    }
  ]
}
```

**Server-side proces:**

#### Stap 1: Haal relaties op
```java
// Op server
Relatives relatives = getRelatives("000000048");
```

**Database query:**
```sql
SELECT 
    p.bsn,
    r.relationship_type,
    r.suitable_for_relocation,
    r.obstructions
FROM personen p
JOIN relaties r ON p.pl_id = r.pl_id
WHERE p.bsn = '000000048';
```

**Resultaat:**
```json
{
  "relatives": [
    {
      "bsn": "000000103",
      "relationshipType": "CHILD",
      "suitableForRelocation": false,
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

#### Stap 2: Check obstructions
```java
// Op server
if (relocator.hasObstruction("EXISTING_RELOCATION_CASE")) {
    return error("Person has existing relocation case");
}
```

**Database query:**
```sql
SELECT * FROM verhuizingen 
WHERE bsn = '000000103' 
AND status IN ('INCOMPLETE', 'PROCESSING');
```

**Resultaat:** Er is een lopende verhuizing gevonden!

#### Stap 3: Return error
```java
// Op server
return new ErrorResponse(
    422,
    "Unprocessable Entity",
    "Person is not suitable for relocation",
    new ErrorDetail("relocators[0]", "EXISTING_RELOCATION_CASE")
);
```

**Response naar client:**
```json
{
  "status": 422,
  "title": "Unprocessable Entity",
  "detail": "Person is not suitable for relocation",
  "errors": [
    {
      "field": "relocators[0]",
      "message": "Person has existing relocation case",
      "obstructions": ["EXISTING_RELOCATION_CASE"]
    }
  ]
}
```

---

## Verschil met Client-Side

### Client-Side (kan niet)

```javascript
// In browser - KAN NIET!
const bsn = "000000103";

// Kan niet checken of BSN bestaat
// (geen database toegang)

// Kan niet checken obstructions
// (geen database toegang)

// Kan alleen syntactische checks
if (bsn.length !== 9) {
    alert("BSN moet 9 cijfers zijn");
}
```

### Server-Side (kan wel)

```java
// Op server - KAN WEL!
String bsn = "000000103";

// Kan checken of BSN bestaat
Person person = database.findByBsn(bsn);
if (person == null) {
    return error("BSN does not exist");
}

// Kan checken obstructions
List<Relocation> relocations = database.findActiveRelocations(bsn);
if (!relocations.isEmpty()) {
    return error("Person has existing relocation case");
}

// Kan alle checks doen
if (!person.isSuitableForRelocation()) {
    return error("Person is not suitable", person.getObstructions());
}
```

---

## Samenvatting

### Server-Side betekent:

1. **Code draait op de server** (niet in browser)
2. **Database toegang** - Kan database queries uitvoeren
3. **Kan niet worden omzeild** - Security
4. **Volledige validatie** - Syntactisch + Semantisch + Autorisation
5. **Business rules** - Complexe logica mogelijk

### Wat gebeurt er precies?

1. **Request komt aan** op API server
2. **Server valideert** met database queries
3. **Als fout** → Error response (geen database write)
4. **Als OK** → Database write + Success response

### Waarom Server-Side?

- ✅ **Security** - Kan niet worden omzeild
- ✅ **Database toegang** - Kan alle checks doen
- ✅ **Business rules** - Complexe validatie mogelijk
- ✅ **Betrouwbaarheid** - Altijd uitgevoerd, ongeacht client

---

## Voor Open Registers

**Bij implementatie in Open Registers:**

**Server-side validatie betekent:**
- Validatie gebeurt in de **PHP controller** (op de Nextcloud server)
- Controller heeft toegang tot **PostgreSQL database**
- Controller kan **database queries** uitvoeren voor validatie
- Validatie gebeurt **voordat** data wordt opgeslagen in Open Register

**Voorbeeld:**
```php
// In HaalCentraalBrpController.php (op server)
public function createRelocation($request) {
    // Server-side validatie
    $bsn = $request['declarant']['bsn'];
    
    // Database query (server-side)
    $person = $this->checkPersonExists($bsn);
    if (!$person) {
        return new JSONResponse(['error' => 'BSN does not exist'], 422);
    }
    
    // Database query (server-side)
    $obstructions = $this->checkObstructions($bsn);
    if (!empty($obstructions)) {
        return new JSONResponse(['error' => 'Has obstructions', 'obstructions' => $obstructions], 422);
    }
    
    // Als alles OK → Opslaan
    $dossier = $this->saveDossier($request);
    return new JSONResponse($dossier, 201);
}
```

---

## Conclusie

**Server-side validatie** betekent dat de validatie gebeurt **op de API server**, niet in de browser/app van de gebruiker. De server heeft toegang tot de database en kan alle checks uitvoeren die nodig zijn voor semantische validatie.

**Belangrijkste punten:**
- ✅ Validatie gebeurt op de server (niet client)
- ✅ Server heeft database toegang
- ✅ Kan niet worden omzeild
- ✅ Volledige controle mogelijk

---

## Referenties

- [VRJIBRP-VALIDATIE-IN-MUTATIE.md](./VRJIBRP-VALIDATIE-IN-MUTATIE.md)
- [VRJIBRP-MUTATIES-TECHNISCH.md](./VRJIBRP-MUTATIES-TECHNISCH.md)







