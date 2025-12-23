# Berichtenroutes Architectuur - Huidige Inrichting

**Datum:** 2025-01-27  
**Status:** Analyse & Advies  
**Doel:** Schematische weergave van berichtenroutes voor huidige Open Register + vrijBRP setup

---

## Huidige Architectuur Overzicht

### Common Ground 5-Lagen Model

```
┌─────────────────────────────────────────────────────────────┐
│                    LAAG 5: UI/Interfaces                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │   Web UI     │  │  Mobile App  │  │  API Clients  │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│              LAAG 4: Processen/Microservices               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ ZGW Systeem  │  │  Workflow    │  │  Externe     │    │
│  │  (Zaak)      │  │  Orchestrator│  │  Systemen    │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│              LAAG 3: Diensten (Business Logic)             │
│  ┌────────────────────────────────────────────────────┐   │
│  │         vrijBRP Logica Service                      │   │
│  │  ┌──────────────┐  ┌──────────────┐                │   │
│  │  │ Syntactische │  │ Semantische │                │   │
│  │  │ Validatie    │  │ Validatie   │                │   │
│  │  └──────────────┘  └──────────────┘                │   │
│  │  ┌──────────────┐  ┌──────────────┐                │   │
│  │  │ RVIG Validatie│  │ Data Trans- │                │   │
│  │  │              │  │ formatie    │                │   │
│  │  └──────────────┘  └──────────────┘                │   │
│  └────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│          LAAG 2: Componenten (Open Register)                │
│  ┌────────────────────────────────────────────────────┐   │
│  │  Controllers                                        │   │
│  │  ┌──────────────┐  ┌──────────────┐               │   │
│  │  │VrijBrpDossiers│  │HaalCentraalBrp│              │   │
│  │  │Controller     │  │Controller     │              │   │
│  │  └──────────────┘  └──────────────┘               │   │
│  │  ┌──────────────┐  ┌──────────────┐               │   │
│  │  │ZgwZaak       │  │ZgwDocument   │               │   │
│  │  │Controller    │  │Controller    │               │   │
│  │  └──────────────┘  └──────────────┘               │   │
│  └────────────────────────────────────────────────────┘   │
│  ┌────────────────────────────────────────────────────┐   │
│  │  Services                                           │   │
│  │  ┌──────────────┐  ┌──────────────┐               │   │
│  │  │ObjectService │  │Validation    │               │   │
│  │  │              │  │Service       │               │   │
│  │  └──────────────┘  └──────────────┘               │   │
│  └────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│              LAAG 1: Data Opslag                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ PostgreSQL   │  │ Open Register│  │  Cache       │    │
│  │  (bevax)     │  │  Objects DB  │  │  (Redis?)    │    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

---

## Berichtenroutes - Gedetailleerd Overzicht

### Route 1: Mutatie Request (POST)

```
┌─────────────────┐
│  ZGW Systeem    │
│  (Laag 4)       │
└────────┬────────┘
         │ POST /api/v1/relocations/intra
         │ POST /api/v1/birth
         │ POST /api/v1/commitment
         │ POST /api/v1/deaths/in-municipality
         ↓
┌─────────────────────────────────────────┐
│  Open Register (Laag 2)                 │
│  ┌──────────────────────────────────┐  │
│  │  Routes.php                       │  │
│  │  → VrijBrpDossiersController     │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Autorisatie Check]         │
│         │   - JWT token validatie       │
│         │   - Rechten check             │
│         │                                │
│         ├─→ [Request Parsing]           │
│         │   - JSON parsing              │
│         │   - Input sanitization         │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  VrijBrpValidationService        │  │
│  │  (Laag 3 - vrijBRP Logica)       │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ SyntacticValidator          │
│         │   - JSON schema check         │
│         │   - Format validatie          │
│         │                                │
│         ├─→ SemanticValidator            │
│         │   - BSN bestaat check          │
│         │   - Database queries           │
│         │   - Business rules             │
│         │                                │
│         ├─→ RvigValidator               │
│         │   - Complexe BRP-regels       │
│         │   - RVIG compliance            │
│         │                                │
│         ├─→ DataTransformationService   │
│         │   - API formaat → DB formaat   │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  Validatie Resultaat             │  │
│  │  ├─→ Success → Getransformeerde │  │
│  │  │            data               │  │
│  │  └─→ Error → Gestructureerde    │  │
│  │              error response      │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  ObjectService                    │  │
│  │  → createFromArray()              │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  Database Write                   │  │
│  │  → openregister_objects           │  │
│  │  → oc_openregister_mutaties       │  │
│  │  (backward compatibility)         │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Eventing] ⚠️ NIET GEÏMPL. │
│         │   - Event zou hier moeten     │
│         │     worden gegenereerd        │
│         │                                │
│         └─→ [Response]                  │
│             - dossier_id                │
│             - status                    │
│             - created_at                │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────────┐
│  HTTP Response  │
│  201 Created    │
└─────────────────┘
```

### Route 2: Bevragen Request (GET)

```
┌─────────────────┐
│  Client/UI      │
│  (Laag 5)       │
└────────┬────────┘
         │ GET /ingeschrevenpersonen/{bsn}
         │ GET /ingeschrevenpersonen
         │ GET /zgw/zaken
         │ GET /zgw/zaken/{zaakId}
         ↓
┌─────────────────────────────────────────┐
│  Open Register (Laag 2)                 │
│  ┌──────────────────────────────────┐  │
│  │  Routes.php                       │  │
│  │  → HaalCentraalBrpController     │  │
│  │  → ZgwZaakController             │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Autorisatie Check]         │
│         │   - Nextcloud auth            │
│         │   - Rechten check             │
│         │                                │
│         ├─→ [Cache Check] ⚠️ OPTIONEEL │
│         │   - CacheService              │
│         │   - 30 min cache               │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  Database Query                  │  │
│  │  → getPersonByBsnFromDatabase()  │  │
│  │  → getZakenFromDatabase()        │  │
│  │  → JSON_EXTRACT queries          │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Field Selection]            │
│         │   - FieldSelectionService      │
│         │   - fields parameter           │
│         │                                │
│         ├─→ [Expand]                    │
│         │   - ExpandService              │
│         │   - Relaties ophalen           │
│         │   - expand parameter           │
│         │                                │
│         ├─→ [Data Transformatie]        │
│         │   - transformToHaalCentraal() │
│         │   - transformToZgwZaak()      │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  Search Trail Logging              │  │
│  │  → SearchTrailService              │  │
│  │  - Query logging                   │  │
│  │  - Performance metrics             │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         └─→ [Response]                  │
│             - JSON response             │
│             - Haal Centraal formaat     │
│             - ZGW formaat               │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────────┐
│  HTTP Response  │
│  200 OK         │
└─────────────────┘
```

### Route 3: Zaak Aanmaken (POST)

```
┌─────────────────┐
│  ZGW Systeem    │
│  (Laag 4)       │
└────────┬────────┘
         │ POST /zgw/zaken
         ↓
┌─────────────────────────────────────────┐
│  Open Register (Laag 2)                 │
│  ┌──────────────────────────────────┐  │
│  │  ZgwZaakController               │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Validatie]                 │
│         │   - Required fields check     │
│         │   - ZGW formaat validatie     │
│         │                                │
│         ├─→ [Data Transformatie]        │
│         │   - transformFromZgwZaak()    │
│         │   - ZGW → Open Register       │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  ObjectService                    │  │
│  │  → createFromArray()              │  │
│  │  → Register ID 5 (Zaken)         │  │
│  │  → Schema ID 20 (Zaken)          │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ↓                                │
│  ┌──────────────────────────────────┐  │
│  │  Database Write                   │  │
│  │  → openregister_objects           │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ [Eventing] ⚠️ NIET GEÏMPL. │
│         │   - Zaak aangemaakt event     │
│         │                                │
│         ├─→ [Task Aanmaak] ⚠️ NIET GEÏMPL.│
│         │   - Automatische tasks        │
│         │                                │
│         └─→ [Response]                  │
│             - ZGW Zaak object          │
│             - transformToZgwZaak()     │
└─────────────────────────────────────────┘
         │
         ↓
┌─────────────────┐
│  HTTP Response  │
│  201 Created    │
└─────────────────┘
```

---

## Ontbrekende Componenten (⚠️)

### 1. Eventing Systeem

**Huidige Status:** ❌ Niet geïmplementeerd

**Wat ontbreekt:**
```
┌─────────────────────────────────────────┐
│  Event Publisher                        │
│  ┌──────────────────────────────────┐  │
│  │  Event Types:                    │  │
│  │  - dossier.created               │  │
│  │  - dossier.updated               │  │
│  │  - zaak.created                  │  │
│  │  - zaak.status_changed           │  │
│  │  - mutatie.validated             │  │
│  └──────────────────────────────────┘  │
│         │                                │
│         ├─→ Message Queue (RabbitMQ?)  │
│         ├─→ Webhook Subscribers         │
│         └─→ Event Store                 │
└─────────────────────────────────────────┘
```

**Impact:** 
- Geen real-time notificaties
- Geen event-driven workflows
- Geen audit trail voor events

### 2. Notificaties Systeem

**Huidige Status:** ❌ Niet geïmplementeerd

**Wat ontbreekt:**
```
┌─────────────────────────────────────────┐
│  Notificatie Service                    │
│  ┌──────────────────────────────────┐  │
│  │  - Email notificaties            │  │
│  │  - SMS notificaties              │  │
│  │  - Push notificaties             │  │
│  │  - Webhook callbacks              │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

**Impact:**
- Gebruikers worden niet geïnformeerd bij wijzigingen
- Geen proactieve communicatie

### 3. Message Queue

**Huidige Status:** ❌ Niet geïmplementeerd

**Wat ontbreekt:**
```
┌─────────────────────────────────────────┐
│  Message Queue (RabbitMQ/Redis)        │
│  ┌──────────────────────────────────┐  │
│  │  Queues:                         │  │
│  │  - mutaties.queue                │  │
│  │  - zaken.queue                   │  │
│  │  - notificaties.queue            │  │
│  │  - events.queue                  │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

**Impact:**
- Geen asynchrone verwerking
- Geen retry mechanisme
- Geen load balancing

---

## Aanbevolen Berichtenroutes Architectuur

### Optie A: Synchronous (Huidige Situatie)

```
[Client] → [Controller] → [Service] → [Database]
                              ↓
                         [Response]
```

**Voordelen:**
- ✅ Eenvoudig te implementeren
- ✅ Directe feedback
- ✅ Geen extra infrastructuur nodig

**Nadelen:**
- ❌ Geen asynchrone verwerking
- ❌ Geen retry mechanisme
- ❌ Geen event-driven workflows

### Optie B: Asynchronous met Message Queue (Aanbevolen)

```
[Client] → [Controller] → [Message Queue] → [Worker] → [Database]
                              ↓                                ↓
                         [Response]                      [Event Publisher]
                                                               ↓
                                                         [Subscribers]
```

**Voordelen:**
- ✅ Asynchrone verwerking
- ✅ Retry mechanisme
- ✅ Event-driven workflows
- ✅ Schaalbaarheid
- ✅ Decoupling

**Nadelen:**
- ❌ Complexer te implementeren
- ❌ Extra infrastructuur nodig
- ❌ Eventual consistency

### Optie C: Hybrid (Aanbevolen voor Jullie Situatie)

```
┌─────────────────────────────────────────────────────────────┐
│  Synchronous Path (Directe Requests)                       │
│  [Client] → [Controller] → [Service] → [Database]         │
│                              ↓                              │
│                         [Response]                          │
└─────────────────────────────────────────────────────────────┘
                            │
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  Asynchronous Path (Events & Notificaties)                  │
│  [Database Change] → [Event Publisher] → [Message Queue]   │
│                              ↓                              │
│                    ┌─────────┴─────────┐                  │
│                    ↓                   ↓                    │
│            [Notificatie      [Workflow                     │
│             Service]          Engine]                      │
└─────────────────────────────────────────────────────────────┘
```

**Voordelen:**
- ✅ Beste van beide werelden
- ✅ Directe feedback voor gebruikers
- ✅ Asynchrone verwerking voor events
- ✅ Geleidelijke migratie mogelijk

---

## Advies & Aanbevelingen

### 1. Directe Acties (Quick Wins)

#### A. Eventing Systeem Basis Implementeren
**Prioriteit:** 🔴 Hoog  
**Tijd:** 1-2 weken

```php
// lib/Service/Eventing/EventPublisher.php
class EventPublisher {
    public function publish(string $eventType, array $data): void {
        // Log event naar database
        // Optioneel: publish naar message queue
    }
}

// In Controllers:
$this->eventPublisher->publish('dossier.created', [
    'dossier_id' => $dossierId,
    'mutation_type' => 'relocation',
    'timestamp' => date('c')
]);
```

**Voordelen:**
- Audit trail voor alle events
- Basis voor notificaties
- Basis voor workflows

#### B. Database Event Log Tabel
**Prioriteit:** 🔴 Hoog  
**Tijd:** 1 dag

```sql
CREATE TABLE oc_openregister_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);
```

### 2. Middellange Termijn (2-4 weken)

#### A. Message Queue Integratie
**Prioriteit:** 🟡 Medium  
**Tijd:** 2-3 weken

**Opties:**
1. **Redis** (aanbevolen voor jullie setup)
   - Eenvoudig te integreren
   - Goed voor caching + queue
   - Geen extra Docker container nodig (kan in bestaande setup)

2. **RabbitMQ**
   - Volledig message queue systeem
   - Complexer maar krachtiger
   - Vereist extra Docker container

**Implementatie:**
```php
// lib/Service/Queue/QueueService.php
class QueueService {
    public function enqueue(string $queue, array $message): void {
        // Redis queue implementatie
    }
    
    public function dequeue(string $queue): ?array {
        // Worker implementatie
    }
}
```

#### B. Notificatie Service
**Prioriteit:** 🟡 Medium  
**Tijd:** 1-2 weken

```php
// lib/Service/Notification/NotificationService.php
class NotificationService {
    public function notify(string $type, array $recipients, array $data): void {
        // Email/SMS/Webhook implementatie
    }
}
```

### 3. Lange Termijn (4-8 weken)

#### A. Volledig Event-Driven Architectuur
**Prioriteit:** 🟢 Laag  
**Tijd:** 4-6 weken

- Event sourcing implementeren
- CQRS pattern
- Saga pattern voor complexe workflows

---

## Concrete Implementatie Stappen

### Stap 1: Event Logging (Week 1)

1. ✅ Event tabel aanmaken
2. ✅ EventPublisher service bouwen
3. ✅ Events loggen in alle controllers
4. ✅ Event query API endpoint

**Resultaat:** Volledige audit trail van alle events

### Stap 2: Message Queue Setup (Week 2-3)

1. ✅ Redis installeren/configureren
2. ✅ QueueService implementeren
3. ✅ Workers implementeren
4. ✅ Retry mechanisme

**Resultaat:** Asynchrone verwerking mogelijk

### Stap 3: Notificaties (Week 4)

1. ✅ NotificationService implementeren
2. ✅ Email templates
3. ✅ Webhook ondersteuning
4. ✅ Abonnementen systeem

**Resultaat:** Gebruikers worden geïnformeerd

### Stap 4: Workflow Engine (Week 5-6)

1. ✅ Workflow definities
2. ✅ Status transitions
3. ✅ Automatische task aanmaak
4. ✅ Event-driven workflows

**Resultaat:** Volledige workflow ondersteuning

---

## Risico's & Overwegingen

### Risico's

1. **Complexiteit**
   - Message queue voegt complexiteit toe
   - Vereist monitoring en onderhoud
   - **Mitigatie:** Start met eenvoudige implementatie

2. **Performance**
   - Extra laag kan latency toevoegen
   - **Mitigatie:** Gebruik caching en async waar mogelijk

3. **Data Consistency**
   - Eventual consistency bij async verwerking
   - **Mitigatie:** Duidelijke consistency guarantees

### Overwegingen

1. **Monitoring**
   - Events moeten gemonitord worden
   - Queue lengte moet worden gecontroleerd
   - **Aanbeveling:** Implementeer monitoring dashboard

2. **Error Handling**
   - Failed messages moeten worden afgehandeld
   - Retry mechanisme nodig
   - **Aanbeveling:** Dead letter queue implementeren

3. **Testing**
   - Async flows zijn moeilijker te testen
   - **Aanbeveling:** Integration tests voor event flows

---

## Conclusie

**Huidige Situatie:**
- ✅ Goede basis architectuur
- ✅ Duidelijke scheiding van verantwoordelijkheden
- ❌ Geen eventing systeem
- ❌ Geen message queue
- ❌ Geen notificaties

**Aanbeveling:**
1. **Start met Event Logging** (Week 1) - Quick win, directe waarde
2. **Implementeer Redis Queue** (Week 2-3) - Basis voor async verwerking
3. **Bouw Notificatie Service** (Week 4) - Gebruikerservaring verbeteren
4. **Workflow Engine** (Week 5-6) - Volledige workflow ondersteuning

**Verwachte Resultaat:**
- Volledige audit trail
- Asynchrone verwerking
- Proactieve notificaties
- Event-driven workflows
- Schaalbaar systeem

---

**Status:** ✅ Analyse compleet, klaar voor implementatie  
**Volgende Stap:** Beslissen over implementatie volgorde en starten met Event Logging



