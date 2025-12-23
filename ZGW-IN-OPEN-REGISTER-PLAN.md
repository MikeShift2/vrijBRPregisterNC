# ZGW Functionaliteit Direct in Open Register

**Doel:** ZGW (Zaakgericht Werken) functionaliteit implementeren in Open Register zelf, zonder aparte Open Zaak service

**Voordelen:**
- ✅ Geen extra Docker container nodig
- ✅ Alles in één systeem
- ✅ Eenvoudiger architectuur
- ✅ Gebruik bestaande Open Register infrastructuur
- ✅ Geen extra database nodig

---

## Architectuur

### Huidige Architectuur

```
[Nextcloud] → [Open Register] → [PostgreSQL bevax]
                ↓
         [Haal Centraal API]
```

### Gewenste Architectuur (Met ZGW)

```
[Nextcloud] → [Open Register] → [PostgreSQL bevax]
                ↓                    ↓
         [Haal Centraal API]    [ZGW API's]
                ↓                    ↓
         [GET endpoints]        [Zaken/Tasks/Documenten]
```

**Belangrijk:** Alles blijft in Open Register, geen extra services!

---

## Implementatie Strategie

### Wat Open Register Al Heeft ✅

1. **CRUD Operaties** - POST/PUT/DELETE endpoints voor objecten
2. **Schema's** - Schema ID 20 (Zaken) beschikbaar
3. **Registers** - Registers kunnen worden aangemaakt
4. **Versiebeheer** - Historie/versiebeheer out-of-the-box
5. **Eventing** - Events bij wijzigingen
6. **API Endpoints** - REST API beschikbaar
7. **Relaties** - Relaties tussen objecten mogelijk

### Wat We Moeten Toevoegen 🔨

1. **ZGW-compliant Endpoints** - ZGW API specificatie implementeren
2. **Schema Configuratie** - Schema ID 20 (Zaken) configureren
3. **Tasks Schema** - Nieuw schema voor tasks
4. **ZGW Controllers** - Controllers voor ZGW endpoints
5. **Data Transformatie** - Open Register → ZGW formaat

---

## Implementatieplan

### Fase 1: Schema Configuratie 🔴

#### 1.1 Schema ID 20 (Zaken) Configureren

**ZGW-compliant Schema Properties:**

```json
{
  "identificatie": {
    "type": "string",
    "description": "Unieke identificatie van de zaak"
  },
  "bronorganisatie": {
    "type": "string",
    "description": "RSIN van de organisatie die de zaak heeft gecreëerd"
  },
  "zaaktype": {
    "type": "string",
    "description": "URL naar het zaaktype"
  },
  "registratiedatum": {
    "type": "string",
    "format": "date-time",
    "description": "Datum waarop de zaak is geregistreerd"
  },
  "startdatum": {
    "type": "string",
    "format": "date",
    "description": "Datum waarop de zaak is gestart"
  },
  "einddatum": {
    "type": "string",
    "format": "date",
    "description": "Datum waarop de zaak is afgerond"
  },
  "status": {
    "type": "string",
    "description": "URL naar de status"
  },
  "omschrijving": {
    "type": "string",
    "description": "Omschrijving van de zaak"
  },
  "toelichting": {
    "type": "string",
    "description": "Toelichting op de zaak"
  },
  "verantwoordelijkeOrganisatie": {
    "type": "string",
    "description": "RSIN van de verantwoordelijke organisatie"
  },
  "betrokkeneIdentificaties": {
    "type": "array",
    "items": {
      "type": "object",
      "properties": {
        "identificatie": {
          "type": "string",
          "description": "BSN of ander identificatienummer"
        },
        "type": {
          "type": "string",
          "description": "Type betrokkene (natuurlijk_persoon, etc.)"
        }
      }
    }
  }
}
```

**Acties:**
1. Update schema ID 20 properties
2. Koppel schema aan register
3. Test schema configuratie

**Tijd:** 2-3 uur

---

#### 1.2 Tasks Schema Aanmaken

**Nieuw Schema voor Tasks:**

```json
{
  "task_id": {
    "type": "string",
    "description": "Unieke identificatie van de task"
  },
  "zaak_id": {
    "type": "string",
    "description": "UUID van de bijbehorende zaak"
  },
  "zaak_identificatie": {
    "type": "string",
    "description": "Identificatie van de bijbehorende zaak"
  },
  "task_type": {
    "type": "string",
    "description": "Type van de task (relocation_consent, birth_acknowledgement, etc.)"
  },
  "status": {
    "type": "string",
    "enum": ["planned", "in_progress", "done"],
    "description": "Status van de task"
  },
  "bsn": {
    "type": "string",
    "description": "BSN van de betrokkene"
  },
  "description": {
    "type": "string",
    "description": "Beschrijving van de task"
  },
  "created_at": {
    "type": "string",
    "format": "date-time"
  },
  "due_date": {
    "type": "string",
    "format": "date-time"
  },
  "completed_at": {
    "type": "string",
    "format": "date-time"
  }
}
```

**Acties:**
1. Maak nieuw schema aan (bijv. Schema ID 22)
2. Definieer properties
3. Koppel aan register

**Tijd:** 1-2 uur

---

### Fase 2: ZGW Controllers Bouwen 🔴

#### 2.1 Zaken Controller

**Bestand:** `lib/Controller/ZgwZaakController.php`

**Endpoints:**

```php
/**
 * GET /apps/openregister/zgw/zaken
 * Lijst alle zaken (ZGW-compliant)
 */
public function getZaken(
    ?string $identificatie = null,
    ?string $bronorganisatie = null,
    ?string $zaaktype = null,
    ?string $status = null,
    int $limit = 20,
    int $page = 1
): DataResponse;

/**
 * GET /apps/openregister/zgw/zaken/{zaakId}
 * Specifieke zaak ophalen (ZGW-compliant)
 */
public function getZaak(string $zaakId): DataResponse;

/**
 * POST /apps/openregister/zgw/zaken
 * Nieuwe zaak aanmaken (ZGW-compliant)
 */
public function createZaak(): DataResponse;

/**
 * PUT /apps/openregister/zgw/zaken/{zaakId}
 * Zaak bijwerken (ZGW-compliant)
 */
public function updateZaak(string $zaakId): DataResponse;

/**
 * DELETE /apps/openregister/zgw/zaken/{zaakId}
 * Zaak verwijderen (ZGW-compliant)
 */
public function deleteZaak(string $zaakId): DataResponse;
```

**Functionaliteit:**
- Gebruik Open Register ObjectService voor CRUD
- Transformeer Open Register formaat naar ZGW formaat
- Valideer tegen ZGW specificatie
- Gebruik Schema ID 20 voor opslag

**Tijd:** 6-8 uur

---

#### 2.2 Tasks Controller

**Bestand:** `lib/Controller/ZgwTaskController.php`

**Endpoints:**

```php
/**
 * GET /apps/openregister/zgw/tasks
 * Lijst alle tasks (met filters)
 */
public function getTasks(
    ?string $bsn = null,
    ?string $taskType = null,
    ?string $status = null,
    ?string $zaakId = null,
    int $limit = 20,
    int $page = 1
): DataResponse;

/**
 * GET /apps/openregister/zgw/tasks/{taskId}
 * Specifieke task ophalen
 */
public function getTask(string $taskId): DataResponse;

/**
 * POST /apps/openregister/zgw/tasks
 * Nieuwe task aanmaken
 */
public function createTask(): DataResponse;

/**
 * PUT /apps/openregister/zgw/tasks/{taskId}
 * Task bijwerken
 */
public function updateTask(string $taskId): DataResponse;

/**
 * DELETE /apps/openregister/zgw/tasks/{taskId}
 * Task verwijderen
 */
public function deleteTask(string $taskId): DataResponse;
```

**Functionaliteit:**
- Gebruik Open Register ObjectService
- Koppel tasks aan zaken via `zaak_id`
- Status tracking (planned → in_progress → done)

**Tijd:** 4-6 uur

---

### Fase 3: Data Transformatie 🔴

#### 3.1 ZGW Formaat Transformatie

**Bestand:** `lib/Service/ZgwTransformService.php`

**Functionaliteit:**

```php
class ZgwTransformService {
    /**
     * Transformeer Open Register object naar ZGW Zaak formaat
     */
    public function transformToZgwZaak(array $openRegisterObject): array;
    
    /**
     * Transformeer ZGW Zaak formaat naar Open Register object
     */
    public function transformFromZgwZaak(array $zgwZaak): array;
    
    /**
     * Transformeer Open Register object naar ZGW Task formaat
     */
    public function transformToZgwTask(array $openRegisterObject): array;
    
    /**
     * Transformeer ZGW Task formaat naar Open Register object
     */
    public function transformFromZgwTask(array $zgwTask): array;
}
```

**ZGW Zaak Formaat:**

```json
{
  "url": "http://localhost:8080/apps/openregister/zgw/zaken/{uuid}",
  "identificatie": "ZAAK-001",
  "bronorganisatie": "123456789",
  "zaaktype": "https://catalogi.nl/api/v1/zaaktypen/1",
  "registratiedatum": "2025-01-27T10:00:00Z",
  "startdatum": "2025-01-27",
  "einddatum": null,
  "status": "https://catalogi.nl/api/v1/statussen/1",
  "omschrijving": "Verhuizing binnen gemeente",
  "toelichting": "Verhuizing van Jan Jansen",
  "verantwoordelijkeOrganisatie": "123456789",
  "betrokkeneIdentificaties": [
    {
      "identificatie": "168149291",
      "type": "natuurlijk_persoon"
    }
  ]
}
```

**Tijd:** 3-4 uur

---

### Fase 4: Routes Configuratie 🔴

#### 4.1 Routes Toevoegen

**Bestand:** `lib/appinfo/routes.php`

```php
// ZGW Zaken endpoints
["name" => "ZgwZaak#getZaken", "url" => "/zgw/zaken", "verb" => "GET"],
["name" => "ZgwZaak#getZaak", "url" => "/zgw/zaken/{zaakId}", "verb" => "GET"],
["name" => "ZgwZaak#createZaak", "url" => "/zgw/zaken", "verb" => "POST"],
["name" => "ZgwZaak#updateZaak", "url" => "/zgw/zaken/{zaakId}", "verb" => "PUT"],
["name" => "ZgwZaak#deleteZaak", "url" => "/zgw/zaken/{zaakId}", "verb" => "DELETE"],

// ZGW Tasks endpoints
["name" => "ZgwTask#getTasks", "url" => "/zgw/tasks", "verb" => "GET"],
["name" => "ZgwTask#getTask", "url" => "/zgw/tasks/{taskId}", "verb" => "GET"],
["name" => "ZgwTask#createTask", "url" => "/zgw/tasks", "verb" => "POST"],
["name" => "ZgwTask#updateTask", "url" => "/zgw/tasks/{taskId}", "verb" => "PUT"],
["name" => "ZgwTask#deleteTask", "url" => "/zgw/tasks/{taskId}", "verb" => "DELETE"],
```

**Tijd:** 1 uur

---

### Fase 5: Validatie en Error Handling 🟡

#### 5.1 ZGW Validatie

**Bestand:** `lib/Service/ZgwValidationService.php`

**Functionaliteit:**
- Valideer ZGW request format
- Valideer required velden
- Valideer data types
- Valideer business rules

**Tijd:** 3-4 uur

---

### Fase 6: Relaties en Koppelingen 🟡

#### 6.1 Zaken ↔ Personen Koppeling

**Functionaliteit:**
- Koppel zaken aan personen via `betrokkeneIdentificaties`
- Gebruik BSN voor koppeling
- Relaties via Open Register relaties systeem

**Tijd:** 2-3 uur

---

#### 6.2 Tasks ↔ Zaken Koppeling

**Functionaliteit:**
- Koppel tasks aan zaken via `zaak_id`
- Relaties via Open Register relaties systeem

**Tijd:** 1-2 uur

---

## Technische Details

### Open Register API Gebruik

**Voor Zaken:**

```php
// In ZgwZaakController.php
use OCA\OpenRegister\Service\ObjectService;

// Zaak aanmaken
$zaakData = $this->transformFromZgwZaak($requestData);
$zaak = $this->objectService->create(
    $zaakData,
    self::REGISTER_ID_ZAKEN,  // Register ID voor zaken
    self::SCHEMA_ID_ZAKEN      // Schema ID 20
);

// Zaak ophalen
$zaak = $this->objectService->get(
    $zaakId,
    self::REGISTER_ID_ZAKEN,
    self::SCHEMA_ID_ZAKEN
);

// Zaak bijwerken
$zaak = $this->objectService->update(
    $zaakId,
    $zaakData,
    self::REGISTER_ID_ZAKEN,
    self::SCHEMA_ID_ZAKEN
);

// Zaak verwijderen
$this->objectService->delete(
    $zaakId,
    self::REGISTER_ID_ZAKEN,
    self::SCHEMA_ID_ZAKEN
);
```

**Voor Tasks:**

```php
// In ZgwTaskController.php
use OCA\OpenRegister\Service\ObjectService;

// Task aanmaken
$taskData = $this->transformFromZgwTask($requestData);
$task = $this->objectService->create(
    $taskData,
    self::REGISTER_ID_TASKS,  // Register ID voor tasks
    self::SCHEMA_ID_TASKS      // Schema ID 22
);
```

---

### ZGW Response Formaat

**Zaken Lijst:**

```json
{
  "count": 10,
  "next": "http://localhost:8080/apps/openregister/zgw/zaken?page=2",
  "previous": null,
  "results": [
    {
      "url": "http://localhost:8080/apps/openregister/zgw/zaken/{uuid}",
      "identificatie": "ZAAK-001",
      "bronorganisatie": "123456789",
      "zaaktype": "https://catalogi.nl/api/v1/zaaktypen/1",
      "registratiedatum": "2025-01-27T10:00:00Z",
      "startdatum": "2025-01-27",
      "status": "https://catalogi.nl/api/v1/statussen/1",
      "omschrijving": "Verhuizing binnen gemeente"
    }
  ]
}
```

**Zaak Detail:**

```json
{
  "url": "http://localhost:8080/apps/openregister/zgw/zaken/{uuid}",
  "identificatie": "ZAAK-001",
  "bronorganisatie": "123456789",
  "zaaktype": "https://catalogi.nl/api/v1/zaaktypen/1",
  "registratiedatum": "2025-01-27T10:00:00Z",
  "startdatum": "2025-01-27",
  "einddatum": null,
  "status": "https://catalogi.nl/api/v1/statussen/1",
  "omschrijving": "Verhuizing binnen gemeente",
  "toelichting": "Verhuizing van Jan Jansen",
  "verantwoordelijkeOrganisatie": "123456789",
  "betrokkeneIdentificaties": [
    {
      "identificatie": "168149291",
      "type": "natuurlijk_persoon"
    }
  ]
}
```

---

## Voordelen van Deze Aanpak

### ✅ Voordelen

1. **Geen Extra Services**
   - Geen Open Zaak Docker container nodig
   - Geen extra database nodig
   - Alles in één systeem

2. **Eenvoudiger Architectuur**
   - Minder componenten te beheren
   - Minder configuratie nodig
   - Minder onderhoud

3. **Gebruik Bestaande Infrastructuur**
   - Open Register API al beschikbaar
   - Versiebeheer out-of-the-box
   - Eventing beschikbaar
   - Relaties systeem beschikbaar

4. **Snellere Implementatie**
   - Geen Docker setup nodig
   - Geen database migraties nodig
   - Direct beginnen met code

5. **Betere Integratie**
   - Directe koppeling met Open Register
   - Geen synchronisatie nodig
   - Geen API calls tussen services

---

## Vergelijking: Open Zaak vs. Open Register

| Aspect | Open Zaak (Extern) | Open Register (Intern) |
|--------|-------------------|----------------------|
| **Docker Container** | ✅ Nodig | ❌ Niet nodig |
| **Database** | ✅ Aparte database | ✅ Bestaande database |
| **Configuratie** | ⚠️ Complex | ✅ Eenvoudig |
| **Synchronisatie** | ⚠️ Nodig | ✅ Niet nodig |
| **API Calls** | ⚠️ Tussen services | ✅ Direct |
| **Onderhoud** | ⚠️ Twee systemen | ✅ Één systeem |
| **Performance** | ⚠️ Network overhead | ✅ Direct |
| **Implementatie Tijd** | ⚠️ 4-6 dagen | ✅ 2-3 dagen |

**Conclusie:** Open Register aanpak is sneller en eenvoudiger!

---

## Geschatte Tijd

| Fase | Beschrijving | Tijd |
|------|--------------|------|
| **Fase 1** | Schema Configuratie | 3-5 uur |
| **Fase 2** | ZGW Controllers | 10-14 uur |
| **Fase 3** | Data Transformatie | 3-4 uur |
| **Fase 4** | Routes Configuratie | 1 uur |
| **Fase 5** | Validatie | 3-4 uur |
| **Fase 6** | Relaties | 3-5 uur |
| **Totaal** | | **23-33 uur** (3-4 dagen) |

**Tijd besparing:** 1-2 dagen (geen Docker setup nodig!)

---

## Implementatie Volgorde

### Week 1: Basis Functionaliteit

1. **Dag 1:** Schema ID 20 configureren + Tasks schema aanmaken
2. **Dag 2:** ZgwZaakController bouwen (GET/POST endpoints)
3. **Dag 3:** ZgwTaskController bouwen + Data transformatie
4. **Dag 4:** Routes + Testen

### Week 2: Uitbreidingen

5. **Dag 5:** Validatie + Error handling
6. **Dag 6:** Relaties + Koppelingen
7. **Dag 7:** Documentatie + Testen

---

## Success Criteria

### Gap 1: Dossier/Zaak Systeem ✅

- ✅ Zaken kunnen worden aangemaakt via ZGW API
- ✅ Zaken kunnen worden opgehaald via ZGW API
- ✅ Status tracking werkt
- ✅ Relaties tussen zaken en personen werken

### Gap 2: Tasks Systeem ✅

- ✅ Tasks kunnen worden aangemaakt
- ✅ Tasks kunnen worden opgehaald
- ✅ Task status kan worden bijgewerkt
- ✅ Tasks zijn gekoppeld aan zaken
- ✅ Tasks zijn gekoppeld aan personen (BSN)

---

## Conclusie

**Aanbeveling:** ✅ **Implementeer ZGW direct in Open Register**

**Redenen:**
1. Geen extra Docker container nodig
2. Eenvoudiger architectuur
3. Snellere implementatie (3-4 dagen vs. 4-6 dagen)
4. Betere integratie met bestaande systeem
5. Minder onderhoud

**Volgende Stappen:**
1. Schema ID 20 configureren
2. Tasks schema aanmaken
3. ZGW Controllers bouwen
4. Testen

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Plan klaar voor uitvoering







