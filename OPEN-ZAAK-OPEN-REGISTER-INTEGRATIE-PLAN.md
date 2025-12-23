# Open Zaak + Open Register Integratieplan

**Doel:** Gap 1 (Dossiers/Zaken) en Gap 2 (Tasks) oplossen door Open Zaak te koppelen aan Nextcloud Open Register

**Bronnen:**
- [Open Zaak GitHub](https://github.com/open-zaak/open-zaak)
- [VNG Realisatie GEMMA Zaken](https://github.com/VNG-Realisatie/gemma-zaken)
- [ZGW API Specificaties](https://zaken-api.vng.cloud/)

---

## Overzicht

### Open Zaak

**Wat is Open Zaak?**
- Productiewaardige implementatie van de ZGW API's (Zaakgericht Werken)
- Common Ground-compliant
- Biedt API's voor: Catalogi, Zaken, Documenten, Besluiten, Notificaties, Autorisaties
- Docker container beschikbaar
- Python/Django gebaseerd

**ZGW API's:**
1. **Catalogi API** - Zaaktype-catalogi, zaaktypen
2. **Zaken API** - Registratie van zaken (dossiers)
3. **Documenten API** - Informatieobjecten (documenten)
4. **Besluiten API** - Besluiten gekoppeld aan zaken
5. **Notificaties API** - Abonnementen op wijzigingen
6. **Autorisaties API** - Toegangsbeheer

### Open Register

**Wat hebben we al?**
- Open Register geïnstalleerd in Nextcloud
- PostgreSQL database (`bevax` met `probev` schema)
- Schema ID 20 (Zaken) beschikbaar maar niet geconfigureerd
- Open Register API voor CRUD operaties

---

## Architectuur Integratie

### Huidige Architectuur

```
[Nextcloud] → [Open Register] → [PostgreSQL bevax]
                ↓
         [Haal Centraal API]
```

### Gewenste Architectuur (Na Integratie)

```
[Nextcloud] → [Open Register] → [PostgreSQL bevax]
                ↓                    ↓
         [Haal Centraal API]    [Open Zaak] → [PostgreSQL openzaak]
                ↓                    ↓
         [ZGW API's]          [Zaken/Documenten/Besluiten]
```

### Integratie Model

**Optie A: Open Zaak als Service (Aanbevolen)**

```
[Open Register] ←→ [Open Zaak] ←→ [PostgreSQL openzaak]
     ↓                                    ↓
[PostgreSQL bevax]              [Zaken/Documenten/Besluiten]
```

**Voordelen:**
- ✅ Open Zaak beheert workflows en tasks
- ✅ Open Register beheert BRP data
- ✅ Scheiding van verantwoordelijkheden
- ✅ Common Ground-compliant
- ✅ Productiewaardig

**Hoe werkt het:**
1. Open Zaak beheert zaken (dossiers) en workflows
2. Open Register beheert BRP data (personen, adressen)
3. Zaken kunnen verwijzen naar personen in Open Register
4. Documenten worden opgeslagen in Open Zaak
5. Tasks worden beheerd door Open Zaak

---

## Implementatieplan

### Fase 1: Open Zaak Installeren en Configureren 🔴

**Doel:** Open Zaak installeren en configureren

**Stappen:**

#### 1.1 Open Zaak Installeren

**Optie A: Docker Container (Aanbevolen)**

```bash
# Clone Open Zaak repository
git clone https://github.com/open-zaak/open-zaak.git
cd open-zaak

# Gebruik docker-compose.yml voor lokale setup
docker-compose up -d
```

**Optie B: Integratie in Bestaande Docker Setup**

```yaml
# Voeg toe aan docker-compose.yml
services:
  openzaak:
    image: openzaak/open-zaak:latest
    ports:
      - "8000:8000"
    environment:
      - DATABASE_URL=postgresql://postgres:postgres@db:5432/openzaak
      - SECRET_KEY=your-secret-key
    depends_on:
      - db
```

**Tijd:** 2-4 uur

---

#### 1.2 Database Configureren

**Open Zaak Database:**
- PostgreSQL database `openzaak` aanmaken
- Migraties uitvoeren
- Basis configuratie

**SQL:**
```sql
CREATE DATABASE openzaak;
CREATE USER openzaak WITH PASSWORD 'openzaak_password';
GRANT ALL PRIVILEGES ON DATABASE openzaak TO openzaak;
```

**Tijd:** 1-2 uur

---

#### 1.3 Open Zaak Configureren

**Configuratie Bestanden:**
- `.env` bestand configureren
- Database connectie instellen
- Secret key genereren
- API endpoints configureren

**Tijd:** 2-3 uur

---

### Fase 2: Open Register Schema's Configureren 🔴

**Doel:** Schema ID 20 (Zaken) configureren voor koppeling met Open Zaak

**Stappen:**

#### 2.1 Schema ID 20 (Zaken) Configureren

**Schema Properties (ZGW-compliant):**

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
  "openzaak_url": {
    "type": "string",
    "description": "URL naar de zaak in Open Zaak"
  },
  "openzaak_uuid": {
    "type": "string",
    "description": "UUID van de zaak in Open Zaak"
  }
}
```

**Tijd:** 2-3 uur

---

#### 2.2 Register Aanmaken voor Zaken

**Register Configuratie:**
- Register ID 3 (Zaken) aanmaken
- Schema ID 20 koppelen aan Register ID 3
- Database mapping configureren

**Tijd:** 1-2 uur

---

### Fase 3: Integratie Controller Bouwen 🔴

**Doel:** Controller bouwen die Open Zaak en Open Register koppelt

**Stappen:**

#### 3.1 ZGW Controller Aanmaken

**Bestand:** `lib/Controller/ZgwZaakController.php`

**Functionaliteit:**
- Zaken ophalen uit Open Zaak
- Zaken synchroniseren met Open Register
- Relaties tussen zaken en personen beheren

**Endpoints:**
```php
// GET /apps/openregister/zgw/zaken
// GET /apps/openregister/zgw/zaken/{zaakId}
// POST /apps/openregister/zgw/zaken
// PUT /apps/openregister/zgw/zaken/{zaakId}
// DELETE /apps/openregister/zgw/zaken/{zaakId}
```

**Tijd:** 4-6 uur

---

#### 3.2 Open Zaak Client Service

**Bestand:** `lib/Service/OpenZaakClientService.php`

**Functionaliteit:**
- HTTP client voor Open Zaak API
- Authenticatie met Open Zaak
- Error handling

**Code Structuur:**
```php
class OpenZaakClientService {
    private string $baseUrl;
    private string $apiKey;
    
    public function getZaak(string $zaakId): array;
    public function createZaak(array $data): array;
    public function updateZaak(string $zaakId, array $data): array;
    public function deleteZaak(string $zaakId): void;
    public function getZaken(array $filters = []): array;
}
```

**Tijd:** 3-4 uur

---

#### 3.3 Synchronisatie Service

**Bestand:** `lib/Service/ZaakSyncService.php`

**Functionaliteit:**
- Synchronisatie tussen Open Zaak en Open Register
- Relaties tussen zaken en personen beheren
- Status updates synchroniseren

**Tijd:** 4-6 uur

---

### Fase 4: Tasks Functionaliteit 🔴

**Doel:** Tasks functionaliteit implementeren via Open Zaak

**Stappen:**

#### 4.1 Tasks Schema Aanmaken

**Schema Properties:**

```json
{
  "task_id": {
    "type": "string",
    "description": "Unieke identificatie van de task"
  },
  "zaak_id": {
    "type": "string",
    "description": "Identificatie van de bijbehorende zaak"
  },
  "task_type": {
    "type": "string",
    "description": "Type van de task (bijv. relocation_consent, birth_acknowledgement)"
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

**Tijd:** 2-3 uur

---

#### 4.2 Tasks Controller

**Bestand:** `lib/Controller/ZgwTaskController.php`

**Endpoints:**
```php
// GET /apps/openregister/zgw/tasks
// GET /apps/openregister/zgw/tasks/{taskId}
// POST /apps/openregister/zgw/tasks
// PUT /apps/openregister/zgw/tasks/{taskId}
// DELETE /apps/openregister/zgw/tasks/{taskId}
```

**Tijd:** 3-4 uur

---

### Fase 5: Documenten Integratie 🟡

**Doel:** Documenten koppelen aan zaken

**Stappen:**

#### 5.1 Documenten API Integratie

**Open Zaak Documenten API:**
- Documenten uploaden
- Documenten koppelen aan zaken
- Document metadata beheren

**Tijd:** 4-6 uur

---

### Fase 6: Notificaties en Eventing 🟡

**Doel:** Notificaties bij wijzigingen

**Stappen:**

#### 6.1 Notificaties API Integratie

**Open Zaak Notificaties API:**
- Abonnementen registreren
- Notificaties ontvangen bij wijzigingen
- Events publiceren naar Open Register

**Tijd:** 3-4 uur

---

## Technische Details

### Open Zaak API Endpoints

**Zaken API:**
```
GET    /api/v1/zaken                    # Lijst zaken
GET    /api/v1/zaken/{zaakId}          # Specifieke zaak
POST   /api/v1/zaken                   # Nieuwe zaak
PUT    /api/v1/zaken/{zaakId}          # Zaak bijwerken
DELETE /api/v1/zaken/{zaakId}          # Zaak verwijderen
```

**Documenten API:**
```
GET    /api/v1/documenten              # Lijst documenten
POST   /api/v1/documenten              # Nieuw document
GET    /api/v1/documenten/{documentId} # Specifiek document
```

**Notificaties API:**
```
POST   /api/v1/notificaties           # Abonnement registreren
GET    /api/v1/notificaties            # Lijst abonnementen
```

---

### Authenticatie

**Open Zaak Authenticatie:**
- API Key authenticatie
- OAuth2 Client Credentials Flow
- JWT Bearer tokens

**Configuratie:**
```php
// lib/Service/OpenZaakClientService.php
private function getAuthHeaders(): array {
    return [
        'Authorization' => 'Bearer ' . $this->apiKey,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ];
}
```

---

### Data Synchronisatie

**Synchronisatie Strategie:**

1. **Open Zaak → Open Register**
   - Bij aanmaken zaak: maak object in Open Register
   - Bij wijzigen zaak: update object in Open Register
   - Bij verwijderen zaak: archiveer object in Open Register

2. **Open Register → Open Zaak**
   - Bij wijzigen persoon: check of er openstaande zaken zijn
   - Publiceer event naar Open Zaak indien nodig

**Code Voorbeeld:**
```php
// lib/Service/ZaakSyncService.php
public function syncZaakToOpenRegister(string $zaakId): void {
    // Haal zaak op uit Open Zaak
    $zaak = $this->openZaakClient->getZaak($zaakId);
    
    // Transformeer naar Open Register formaat
    $openRegisterData = $this->transformZaakToOpenRegister($zaak);
    
    // Sla op in Open Register
    $this->objectService->create($openRegisterData, self::REGISTER_ID_ZAKEN, self::SCHEMA_ID_ZAKEN);
}
```

---

## Configuratie Bestanden

### Open Zaak Configuratie

**.env bestand:**
```env
DATABASE_URL=postgresql://openzaak:openzaak_password@db:5432/openzaak
SECRET_KEY=your-secret-key-here
ALLOWED_HOSTS=localhost,127.0.0.1,openzaak.local
DEBUG=False
```

### Nextcloud Configuratie

**config.php toevoegen:**
```php
'openzaak' => [
    'base_url' => 'http://openzaak:8000',
    'api_key' => 'your-api-key',
    'timeout' => 30,
],
```

---

## Routes Configuratie

**appinfo/routes.php:**

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

---

## Test Plan

### Test 1: Open Zaak Installatie ✅

```bash
# Test Open Zaak API
curl -X GET "http://localhost:8000/api/v1/zaken" \
  -H "Authorization: Bearer your-api-key"
```

**Verwachte Resultaat:** Lijst met zaken (mogelijk leeg)

---

### Test 2: Zaak Aanmaken ✅

```bash
# Maak zaak aan via Open Zaak
curl -X POST "http://localhost:8000/api/v1/zaken" \
  -H "Authorization: Bearer your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "identificatie": "ZAAK-001",
    "bronorganisatie": "123456789",
    "zaaktype": "https://catalogi.nl/api/v1/zaaktypen/1",
    "registratiedatum": "2025-01-27T10:00:00Z",
    "startdatum": "2025-01-27",
    "omschrijving": "Test zaak"
  }'
```

**Verwachte Resultaat:** Zaak aangemaakt met UUID

---

### Test 3: Synchronisatie Open Register ✅

```bash
# Test synchronisatie
curl -X GET "http://localhost:8080/apps/openregister/zgw/zaken" \
  -u admin:password
```

**Verwachte Resultaat:** Zaken uit Open Zaak gesynchroniseerd met Open Register

---

### Test 4: Task Aanmaken ✅

```bash
# Maak task aan
curl -X POST "http://localhost:8080/apps/openregister/zgw/tasks" \
  -u admin:password \
  -H "Content-Type: application/json" \
  -d '{
    "zaak_id": "zaak-uuid",
    "task_type": "relocation_consent",
    "status": "planned",
    "bsn": "168149291",
    "description": "Toestemming hoofdhuurder vereist"
  }'
```

**Verwachte Resultaat:** Task aangemaakt

---

## Geschatte Tijd

| Fase | Beschrijving | Tijd |
|------|--------------|------|
| **Fase 1** | Open Zaak Installeren | 5-9 uur |
| **Fase 2** | Open Register Schema's | 3-5 uur |
| **Fase 3** | Integratie Controller | 11-16 uur |
| **Fase 4** | Tasks Functionaliteit | 5-7 uur |
| **Fase 5** | Documenten Integratie | 4-6 uur |
| **Fase 6** | Notificaties | 3-4 uur |
| **Totaal** | | **31-47 uur** (4-6 dagen) |

---

## Success Criteria

### Gap 1: Dossier/Zaak Systeem ✅

- ✅ Zaken kunnen worden aangemaakt via Open Zaak
- ✅ Zaken kunnen worden opgehaald via Open Register API
- ✅ Zaken zijn gesynchroniseerd tussen Open Zaak en Open Register
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

**Doel:** Gap 1 en Gap 2 oplossen door Open Zaak te koppelen aan Open Register

**Voordelen:**
- ✅ Productiewaardige ZGW-implementatie
- ✅ Common Ground-compliant
- ✅ Volledige workflow-functionaliteit
- ✅ Tasks systeem out-of-the-box
- ✅ Documenten management
- ✅ Notificaties en eventing

**Geschatte Tijd:** 4-6 dagen werk

**Volgende Stappen:**
1. Open Zaak installeren (Fase 1)
2. Schema's configureren (Fase 2)
3. Integratie bouwen (Fase 3-6)

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Plan klaar voor uitvoering







