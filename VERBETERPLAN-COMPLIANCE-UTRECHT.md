# Verbeterplan: Compliance Utrecht Uitvraag

**Datum:** 2025-01-27  
**Huidige Compliance:** 58%  
**Doel Compliance:** 80%+  
**Geschatte tijd:** 6-8 weken

---

## Prioriteiten Overzicht

| Prioriteit | Item | Impact | Tijd | Compliance Verbetering |
|------------|------|--------|------|----------------------|
| 🔴 **1** | Workflow Engine | Hoog | 2-3 weken | +15% (80% → 95%) |
| 🔴 **2** | Authenticatie (JWT/Bearer) | Hoog | 1-2 weken | +20% (40% → 60%) |
| 🟡 **3** | Mutaties naar BRP | Hoog | 3-4 weken | +15% (20% → 35%) |
| 🟡 **4** | Haal Centraal Compliance | Medium | 1-2 weken | +5% (75% → 80%) |
| 🟢 **5** | Notificaties & Eventing | Medium | 1 week | +3% (algemeen) |

**Totaal geschatte tijd:** 8-12 weken  
**Verwachte nieuwe compliance:** **75-80%**

---

## 🔴 Prioriteit 1: Workflow Engine (Hoogste Prioriteit)

### Waarom dit eerst?
- **Impact:** Workflow & Processen gaat van 80% → 95%
- **Gebruik:** Direct nodig voor automatische status transitions
- **Complexiteit:** Middel (2-3 weken)

### Wat moet worden gebouwd:

#### 1.1 Basis Workflow Engine
- ✅ Status transition rules definiëren
- ✅ Automatische status updates bij events
- ✅ Workflow definities per zaaktype

**Implementatie:**
```php
// lib/Service/Workflow/WorkflowEngine.php
class WorkflowEngine {
    public function processStatusTransition(string $zaakId, string $newStatus): void
    public function canTransition(string $currentStatus, string $newStatus): bool
    public function getNextValidStatuses(string $currentStatus): array
}
```

#### 1.2 Automatische Task Aanmaak
- ✅ Bij zaak-aanmaak automatisch tasks genereren
- ✅ Task templates per zaaktype
- ✅ Task dependencies (task A moet voltooid zijn voordat task B start)

**Implementatie:**
```php
// lib/Service/Workflow/TaskOrchestrator.php
class TaskOrchestrator {
    public function createTasksForZaak(string $zaakId, string $zaakType): void
    public function checkTaskDependencies(string $taskId): bool
    public function completeTask(string $taskId): void
}
```

#### 1.3 Status Transition Rules
- ✅ Definieer welke status transitions toegestaan zijn
- ✅ Automatische validatie bij status wijziging
- ✅ Event generatie bij status transitions

**Voorbeeld:**
```php
// Config: workflow-rules.php
return [
    'geboorte' => [
        'statuses' => ['incomplete', 'complete', 'processing', 'completed', 'rejected'],
        'transitions' => [
            'incomplete' => ['complete', 'rejected'],
            'complete' => ['processing', 'cancelled'],
            'processing' => ['completed', 'rejected'],
        ],
        'tasks' => [
            'incomplete' => ['validate_documents', 'check_requirements'],
            'complete' => ['approve_dossier', 'process_mutation'],
            'processing' => ['finalize_mutation', 'notify_parties'],
        ]
    ]
];
```

### Stappenplan:

**Week 1:**
1. ✅ WorkflowEngine class bouwen
2. ✅ Status transition rules definiëren
3. ✅ Unit tests schrijven

**Week 2:**
1. ✅ TaskOrchestrator class bouwen
2. ✅ Automatische task-aanmaak implementeren
3. ✅ Task dependencies implementeren

**Week 3:**
1. ✅ Integratie met ZgwZaakController
2. ✅ Integratie met ZgwTaskController
3. ✅ End-to-end testen

**Geschatte tijd:** 2-3 weken  
**Compliance verbetering:** Workflow & Processen: 80% → **95%**

---

## 🔴 Prioriteit 2: Authenticatie (JWT/Bearer Token)

### Waarom dit belangrijk?
- **Impact:** Authenticatie gaat van 40% → 60%
- **Gebruik:** Vereist voor externe systemen
- **Complexiteit:** Laag-Middel (1-2 weken)

### Wat moet worden gebouwd:

#### 2.1 JWT Token Generatie
- ✅ OAuth2 Client Credentials Flow
- ✅ JWT token generatie met expiratie
- ✅ Token refresh mechanisme

**Implementatie:**
```php
// lib/Service/Auth/JwtTokenService.php
class JwtTokenService {
    public function generateToken(string $clientId, array $scopes): string
    public function validateToken(string $token): ?array
    public function refreshToken(string $refreshToken): string
}
```

#### 2.2 API Key Management
- ✅ API key generatie en opslag
- ✅ Rate limiting per API key
- ✅ API key revocation

**Database tabel:**
```sql
CREATE TABLE oc_openregister_api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    secret VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) UNIQUE NOT NULL,
    rate_limit INT DEFAULT 1000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2.3 Bearer Token Middleware
- ✅ Bearer token validatie in alle endpoints
- ✅ Fallback naar Nextcloud authenticatie
- ✅ Rate limiting implementatie

**Implementatie:**
```php
// lib/Middleware/BearerTokenMiddleware.php
class BearerTokenMiddleware {
    public function validateRequest(IRequest $request): bool
    public function getClientId(IRequest $request): ?string
    public function checkRateLimit(string $clientId): bool
}
```

### Stappenplan:

**Week 1:**
1. ✅ JWT library installeren (firebase/php-jwt)
2. ✅ JwtTokenService bouwen
3. ✅ OAuth2 endpoint implementeren (`/oauth/token`)

**Week 2:**
1. ✅ API key management systeem bouwen
2. ✅ BearerTokenMiddleware implementeren
3. ✅ Rate limiting implementeren
4. ✅ Integratie met bestaande endpoints

**Geschatte tijd:** 1-2 weken  
**Compliance verbetering:** Authenticatie: 40% → **60%**

---

## 🟡 Prioriteit 3: Mutaties naar BRP

### Waarom dit belangrijk?
- **Impact:** Mutaties gaat van 20% → 35%
- **Gebruik:** Core functionaliteit voor BRP mutaties
- **Complexiteit:** Hoog (3-4 weken)

### Wat moet worden gebouwd:

#### 3.1 vrijBRP Logica Service
- ✅ Syntactische validatie (JSON schema, formaten)
- ✅ Semantische validatie (BSN bestaat, obstructions)
- ✅ RVIG-regels implementatie
- ✅ Datatransformatie (API formaat → Database formaat)

**Implementatie:**
```php
// lib/Service/Validation/VrijBrpValidationService.php (deels aanwezig)
class VrijBrpValidationService {
    public function validateRelocation(array $request): ValidationResult
    public function validateBirth(array $request): ValidationResult
    public function validatePartnership(array $request): ValidationResult
    // ... andere mutatie types
}
```

#### 3.2 Mutatie Endpoints
- ✅ POST endpoints voor aanmaken (geboorte, verhuizing, etc.)
- ✅ PUT endpoints voor bijwerken
- ✅ DELETE endpoints voor verwijderen
- ✅ Integratie met vrijBRP Logica Service

**Endpoints:**
- `POST /api/v1/relocations/intra` - Verhuizing
- `POST /api/v1/births` - Geboorte
- `POST /api/v1/partnerships` - Partnerschap
- etc.

#### 3.3 Error Handling
- ✅ Gestructureerde error responses
- ✅ Haal Centraal-compliant error codes
- ✅ Validatie error details

### Stappenplan:

**Week 1-2:**
1. ✅ VrijBrpValidationService uitbreiden
2. ✅ Syntactische validators implementeren
3. ✅ Semantische validators implementeren

**Week 3:**
1. ✅ RVIG-regels implementeren (basis set)
2. ✅ Datatransformatie implementeren
3. ✅ Error handling verbeteren

**Week 4:**
1. ✅ Mutatie endpoints implementeren
2. ✅ Integratie met Open Register
3. ✅ End-to-end testen

**Geschatte tijd:** 3-4 weken  
**Compliance verbetering:** Mutaties: 20% → **35%**

---

## 🟡 Prioriteit 4: Haal Centraal Compliance

### Wat moet worden gebouwd:

#### 4.1 Query Parameters
- ✅ `fields` parameter voor field selection
- ✅ `expand` parameter voor relaties
- ✅ Geavanceerde filters en sortering

#### 4.2 OpenAPI Specificatie
- ✅ Volledige OpenAPI 3.0 specificatie genereren
- ✅ Swagger UI beschikbaar maken
- ✅ API documentatie

**Geschatte tijd:** 1-2 weken  
**Compliance verbetering:** Bevragen: 75% → **80%**

---

## 🟢 Prioriteit 5: Notificaties & Eventing

### Wat moet worden gebouwd:

#### 5.1 Eventing Systeem
- ✅ Events bij status wijzigingen
- ✅ Events bij mutaties
- ✅ Webhook ondersteuning

#### 5.2 Notificaties
- ✅ Notificaties bij task status wijzigingen
- ✅ Notificaties bij zaak status wijzigingen
- ✅ Email/SMS notificaties (optioneel)

**Geschatte tijd:** 1 week  
**Compliance verbetering:** +3% algemeen

---

## Implementatie Volgorde (Aanbevolen)

### Fase 1: Quick Wins (2-3 weken)
1. ✅ **Authenticatie (JWT/Bearer)** - 1-2 weken
2. ✅ **Haal Centraal Compliance** - 1 week

**Resultaat:** Compliance 58% → **65%**

### Fase 2: Core Functionaliteit (3-4 weken)
3. ✅ **Workflow Engine** - 2-3 weken
4. ✅ **Notificaties & Eventing** - 1 week

**Resultaat:** Compliance 65% → **75%**

### Fase 3: Mutaties (3-4 weken)
5. ✅ **Mutaties naar BRP** - 3-4 weken

**Resultaat:** Compliance 75% → **80%**

---

## Quick Wins (Kunnen direct worden gestart)

### 1. Authenticatie - Basis Setup (1 dag)
- ✅ JWT library installeren
- ✅ Basis JWT token generatie
- ✅ Bearer token validatie middleware

### 2. Workflow Engine - Basis (1 dag)
- ✅ Status transition rules definiëren
- ✅ Basis WorkflowEngine class
- ✅ Integratie met ZgwZaakController

### 3. Haal Centraal - Query Parameters (2-3 dagen)
- ✅ `fields` parameter implementeren
- ✅ `expand` parameter implementeren

---

## Conclusie

**Aanbevolen Volgorde:**
1. 🔴 **Authenticatie** (1-2 weken) - Quick win, hoog impact
2. 🔴 **Workflow Engine** (2-3 weken) - Core functionaliteit
3. 🟡 **Mutaties naar BRP** (3-4 weken) - Complex maar belangrijk
4. 🟡 **Haal Centraal Compliance** (1-2 weken) - Nice to have
5. 🟢 **Notificaties** (1 week) - Nice to have

**Verwachte Resultaat:**
- **Na Fase 1:** 58% → **65%** (+7%)
- **Na Fase 2:** 65% → **75%** (+10%)
- **Na Fase 3:** 75% → **80%** (+5%)

**Totaal:** 58% → **80%** (+22%)

---

**Status:** ✅ Klaar voor implementatie  
**Volgende Stap:** Start met Authenticatie (Prioriteit 2) of Workflow Engine (Prioriteit 1)







