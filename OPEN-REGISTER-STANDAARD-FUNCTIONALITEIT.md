# Open Register - Standaard Functionaliteit

**Datum:** 2025-01-27  
**Doel:** Overzicht van wat Open Register als app zelf al biedt out-of-the-box

---

## Core Functionaliteit

### 1. Registers & Schemas Management ✅

**Wat het biedt:**
- **Registers** - Logische groepering van objecten
  - `GET /api/registers` - Lijst alle registers
  - `GET /api/registers/{id}` - Specifiek register
  - `POST /api/registers` - Nieuw register aanmaken
  - `PUT /api/registers/{id}` - Register bijwerken
  - `DELETE /api/registers/{id}` - Register verwijderen
  - `GET /api/registers/{id}/export` - Export register
  - `POST /api/registers/{id}/import` - Import register
  - `GET /api/registers/{id}/schemas` - Schemas van register
  - `GET /api/registers/{id}/stats` - Statistieken

- **Schemas** - Data structuren definiëren
  - `GET /api/schemas` - Lijst alle schemas
  - `GET /api/schemas/{id}` - Specifiek schema
  - `POST /api/schemas/upload` - Schema uploaden
  - `PUT /api/schemas/{id}/upload` - Schema bijwerken
  - `GET /api/schemas/{id}/download` - Schema downloaden
  - `GET /api/schemas/{id}/related` - Gerelateerde schemas
  - `GET /api/schemas/{id}/stats` - Statistieken
  - `GET /api/schemas/{id}/explore` - Schema exploreren
  - `POST /api/schemas/{id}/update-from-exploration` - Update vanuit exploratie

**Gebruik:**
- Registers voor logische groepering (bijv. "Personen", "Zaken", "Mutaties")
- Schemas voor data structuren (bijv. JSON Schema definities)

---

### 2. Object Management (CRUD) ✅

**Wat het biedt:**
- **Objecten** - Data objecten beheren
  - `GET /api/objects/{register}/{schema}` - Lijst objecten
  - `GET /api/objects/{register}/{schema}/{id}` - Specifiek object
  - `POST /api/objects/{register}/{schema}` - Nieuw object aanmaken
  - `PUT /api/objects/{register}/{schema}/{id}` - Object bijwerken
  - `PATCH /api/objects/{register}/{schema}/{id}` - Object gedeeltelijk bijwerken
  - `DELETE /api/objects/{register}/{schema}/{id}` - Object verwijderen
  - `GET /api/objects/{register}/{schema}/export` - Export objecten
  - `POST /api/objects/{register}/{schema}/{id}/merge` - Objecten samenvoegen
  - `POST /api/migrate` - Migratie tussen registers/schemas

**Features:**
- ✅ Volledige CRUD operaties
- ✅ JSON objecten opslaan
- ✅ Flexibele data structuren
- ✅ Bulk operaties mogelijk

**Gebruik:**
- Personen, zaken, mutaties, documenten opslaan als objecten
- Elke object heeft een UUID en versie

---

### 3. Versiebeheer & Historie ✅

**Wat het biedt:**
- **Versiebeheer** - Automatisch versiebeheer voor objecten
  - Elke wijziging creëert nieuwe versie
  - Versie nummering (1, 2, 3, ...)
  - Versie metadata (created, updated, user)

- **Audit Trail** - Volledige audit trail
  - `GET /api/audit-trails` - Lijst alle audit trails
  - `GET /api/audit-trails/{id}` - Specifieke audit trail
  - `GET /api/objects/{register}/{schema}/{id}/audit-trails` - Audit trails voor object
  - `GET /api/audit-trails/export` - Export audit trails
  - `DELETE /api/audit-trails/{id}` - Audit trail verwijderen
  - `DELETE /api/audit-trails` - Meerdere audit trails verwijderen
  - `DELETE /api/audit-trails/clear-all` - Alle audit trails wissen

**Features:**
- ✅ Automatisch versiebeheer
- ✅ Volledige wijzigingshistorie
- ✅ Wie heeft wat wanneer gewijzigd
- ✅ Rollback mogelijk (via revert)

**Gebruik:**
- Historie van alle wijzigingen
- Compliance en audit doeleinden
- Rollback functionaliteit

---

### 4. Relaties tussen Objecten ✅

**Wat het biedt:**
- **Relaties** - Relaties tussen objecten
  - `GET /api/objects/{register}/{schema}/{id}/contracts` - Contracten (relaties)
  - `GET /api/objects/{register}/{schema}/{id}/uses` - Objecten die dit object gebruiken
  - `GET /api/objects/{register}/{schema}/{id}/used` - Objecten die door dit object worden gebruikt

**Features:**
- ✅ Relaties tussen objecten
- ✅ Contract systeem (relatie definities)
- ✅ Bidirectionele relaties
- ✅ Relatie navigatie

**Gebruik:**
- Personen ↔ Zaken relaties
- Zaken ↔ Documenten relaties
- Mutaties ↔ Personen relaties

---

### 5. Locking & Publicatie ✅

**Wat het biedt:**
- **Locking** - Objecten vergrendelen
  - `POST /api/objects/{register}/{schema}/{id}/lock` - Object vergrendelen
  - `POST /api/objects/{register}/{schema}/{id}/unlock` - Object ontgrendelen

- **Publicatie** - Objecten publiceren/depubliceren
  - `POST /api/objects/{register}/{schema}/{id}/publish` - Object publiceren
  - `POST /api/objects/{register}/{schema}/{id}/depublish` - Object depubliceren

**Features:**
- ✅ Concurrency control
- ✅ Publicatie workflow
- ✅ Draft vs. Published status

**Gebruik:**
- Voorkomen van conflicten bij gelijktijdige wijzigingen
- Workflow voor publicatie

---

### 6. Bulk Operaties ✅

**Wat het biedt:**
- **Bulk Operations** - Meerdere objecten tegelijk bewerken
  - `POST /api/bulk/{register}/{schema}/save` - Bulk opslaan
  - `POST /api/bulk/{register}/{schema}/delete` - Bulk verwijderen
  - `POST /api/bulk/{register}/{schema}/publish` - Bulk publiceren
  - `POST /api/bulk/{register}/{schema}/depublish` - Bulk depubliceren
  - `POST /api/bulk/{register}/{schema}/delete-schema` - Schema verwijderen
  - `POST /api/bulk/{register}/{schema}/publish-schema` - Schema publiceren
  - `POST /api/bulk/{register}/delete-register` - Register verwijderen
  - `POST /api/bulk/schema/{schema}/validate` - Schema validatie

**Features:**
- ✅ Efficiënte bulk operaties
- ✅ Batch processing
- ✅ Performance optimalisatie

**Gebruik:**
- Massa imports
- Bulk updates
- Massa exports

---

### 7. File Management ✅

**Wat het biedt:**
- **Files** - Bestanden koppelen aan objecten
  - `POST /api/objects/{register}/{schema}/{id}/files` - Bestand uploaden
  - `POST /api/objects/{register}/{schema}/{id}/files/save` - Bestand opslaan
  - `GET /api/objects/{register}/{schema}/{id}/files` - Lijst bestanden
  - `GET /api/objects/{register}/{schema}/{id}/files/{fileId}` - Specifiek bestand
  - `PUT /api/objects/{register}/{schema}/{id}/files/{fileId}` - Bestand bijwerken
  - `DELETE /api/objects/{register}/{schema}/{id}/files/{fileId}` - Bestand verwijderen
  - `GET /api/objects/{register}/{schema}/{id}/files/download` - Bestanden downloaden
  - `POST /api/objects/{register}/{schema}/{id}/filesMultipart` - Multipart upload
  - `POST /api/objects/{register}/{schema}/{id}/files/{fileId}/publish` - Bestand publiceren
  - `POST /api/objects/{register}/{schema}/{id}/files/{fileId}/depublish` - Bestand depubliceren
  - `GET /api/files/{fileId}/download` - Direct bestand downloaden

**Features:**
- ✅ Bestanden koppelen aan objecten
- ✅ Versiebeheer voor bestanden
- ✅ Publicatie workflow voor bestanden
- ✅ Multipart upload ondersteuning

**Gebruik:**
- Documenten koppelen aan zaken
- Attachments bij mutaties
- Bewijsstukken bij dossiers

---

### 8. Search & Query ✅

**Wat het biedt:**
- **Search** - Zoeken in objecten
  - `GET /api/search` - Algemene zoekfunctie
  - `POST /api/search/semantic` - Semantische zoekfunctie (SOLR)
  - `POST /api/search/hybrid` - Hybride zoekfunctie (keyword + semantic)
  - `POST /api/search/files/keyword` - Zoeken in bestanden (keyword)
  - `POST /api/search/files/semantic` - Zoeken in bestanden (semantic)
  - `POST /api/search/files/hybrid` - Zoeken in bestanden (hybrid)

**Features:**
- ✅ Full-text search
- ✅ Semantic search (via SOLR)
- ✅ Hybrid search
- ✅ File content search

**Gebruik:**
- Zoeken in personen, zaken, mutaties
- Zoeken in documenten
- Geavanceerde zoekopdrachten

---

### 9. SOLR Integration ✅

**Wat het biedt:**
- **SOLR Management** - SOLR integratie voor zoeken
  - `GET /api/solr/collections` - Lijst collections
  - `POST /api/solr/collections` - Collection aanmaken
  - `DELETE /api/solr/collections/{name}` - Collection verwijderen
  - `POST /api/solr/collections/{name}/clear` - Collection legen
  - `POST /api/solr/collections/{name}/reindex` - Collection reindexeren
  - `GET /api/solr/configsets` - Lijst configsets
  - `POST /api/solr/configsets` - Configset aanmaken
  - `DELETE /api/solr/configsets/{name}` - Configset verwijderen
  - `POST /api/solr/collections/copy` - Collection kopiëren
  - `GET /api/solr/fields` - Lijst velden
  - `POST /api/solr/fields/create-missing` - Ontbrekende velden aanmaken
  - `POST /api/solr/fields/fix-mismatches` - Velden synchroniseren
  - `DELETE /api/solr/fields/{fieldName}` - Veld verwijderen

**Features:**
- ✅ Volledige SOLR integratie
- ✅ Collection management
- ✅ Field management
- ✅ Reindexing functionaliteit

**Gebruik:**
- Geavanceerde zoekfunctionaliteit
- Full-text indexing
- Semantic search

---

### 10. Deleted Objects Management ✅

**Wat het biedt:**
- **Deleted Objects** - Verwijderde objecten beheren
  - `GET /api/deleted` - Lijst verwijderde objecten
  - `GET /api/deleted/statistics` - Statistieken verwijderde objecten
  - `GET /api/deleted/top-deleters` - Top verwijderaars
  - `POST /api/deleted/{id}/restore` - Object herstellen
  - `POST /api/deleted/restore` - Meerdere objecten herstellen
  - `DELETE /api/deleted/{id}` - Object permanent verwijderen
  - `DELETE /api/deleted` - Meerdere objecten permanent verwijderen

**Features:**
- ✅ Soft delete functionaliteit
- ✅ Herstel functionaliteit
- ✅ Statistieken verwijderde objecten
- ✅ Audit trail voor verwijderingen

**Gebruik:**
- Onbedoelde verwijderingen herstellen
- Compliance (verwijderde objecten blijven traceerbaar)

---

### 11. Revert Functionaliteit ✅

**Wat het biedt:**
- **Revert** - Objecten terugzetten naar vorige versie
  - `POST /api/objects/{register}/{schema}/{id}/revert` - Object terugzetten

**Features:**
- ✅ Rollback naar vorige versie
- ✅ Historie navigatie
- ✅ Undo functionaliteit

**Gebruik:**
- Fouten ongedaan maken
- Terugkeren naar vorige staat

---

### 12. Names Service ✅

**Wat het biedt:**
- **Names** - Ultra-fast object name lookup
  - `GET /api/names` - Lijst namen
  - `POST /api/names` - Naam aanmaken
  - `GET /api/names/{id}` - Specifieke naam
  - `GET /api/names/stats` - Statistieken
  - `POST /api/names/warmup` - Cache warmup

**Features:**
- ✅ Snelle naam lookup
- ✅ Caching functionaliteit
- ✅ Performance optimalisatie

**Gebruik:**
- Snelle object naam resolutie
- Autocomplete functionaliteit

---

### 13. Sources Management ✅

**Wat het biedt:**
- **Sources** - Data bronnen beheren
  - `GET /api/sources` - Lijst sources
  - `GET /api/sources/{id}` - Specifieke source
  - `POST /api/sources` - Source aanmaken
  - `PUT /api/sources/{id}` - Source bijwerken
  - `DELETE /api/sources/{id}` - Source verwijderen

**Features:**
- ✅ Database connecties beheren
  - PostgreSQL
  - MySQL/MariaDB
  - SQLite
- ✅ Source configuratie
- ✅ Connection pooling

**Gebruik:**
- Database connecties configureren
- Meerdere data bronnen beheren

---

### 14. Configurations Management ✅

**Wat het biedt:**
- **Configurations** - Configuraties beheren
  - `GET /api/configurations` - Lijst configuraties
  - `GET /api/configurations/{id}` - Specifieke configuratie
  - `POST /api/configurations` - Configuratie aanmaken
  - `PUT /api/configurations/{id}` - Configuratie bijwerken
  - `DELETE /api/configurations/{id}` - Configuratie verwijderen
  - `GET /api/configurations/{id}/export` - Configuratie exporteren
  - `POST /api/configurations/import` - Configuratie importeren

**Features:**
- ✅ Configuratie management
- ✅ Export/import functionaliteit
- ✅ Versiebeheer configuraties

**Gebruik:**
- Systeem configuraties beheren
- Configuraties delen tussen omgevingen

---

### 15. Dashboard & Statistics ✅

**Wat het biedt:**
- **Dashboard** - Overzicht en statistieken
  - `GET /api/dashboard` - Dashboard data
  - `POST /api/dashboard/calculate/{registerId}` - Statistieken berekenen
  - `GET /api/dashboard/charts/audit-trail-actions` - Audit trail acties chart
  - `GET /api/dashboard/charts/objects-by-register` - Objecten per register chart
  - `GET /api/dashboard/charts/objects-by-schema` - Objecten per schema chart
  - `GET /api/dashboard/charts/objects-by-size` - Objecten per grootte chart
  - `GET /api/dashboard/statistics/audit-trail` - Audit trail statistieken
  - `GET /api/dashboard/statistics/audit-trail-distribution` - Audit trail distributie
  - `GET /api/dashboard/statistics/most-active-objects` - Meest actieve objecten

**Features:**
- ✅ Dashboard functionaliteit
- ✅ Statistieken en metrics
- ✅ Charts en visualisaties
- ✅ Performance monitoring

**Gebruik:**
- Overzicht van systeem gebruik
- Performance monitoring
- Statistieken per register/schema

---

### 16. Search Trail Logging ✅

**Wat het biedt:**
- **Search Trails** - Zoekopdrachten loggen
  - `GET /api/search-trails` - Lijst search trails
  - `GET /api/search-trails/{id}` - Specifieke search trail
  - `GET /api/search-trails/statistics` - Statistieken
  - `GET /api/search-trails/popular-terms` - Populaire zoektermen
  - `GET /api/search-trails/activity` - Activiteit
  - `GET /api/search-trails/register-schema-stats` - Register/schema statistieken
  - `GET /api/search-trails/user-agent-stats` - User agent statistieken
  - `GET /api/search-trails/export` - Export search trails
  - `POST /api/search-trails/cleanup` - Cleanup oude trails
  - `DELETE /api/search-trails/{id}` - Search trail verwijderen
  - `DELETE /api/search-trails` - Meerdere search trails verwijderen
  - `DELETE /api/search-trails/clear-all` - Alle search trails wissen

**Features:**
- ✅ Zoekopdrachten loggen
- ✅ Analytics functionaliteit
- ✅ Performance tracking
- ✅ Gebruikersgedrag analyseren

**Gebruik:**
- Zoekgedrag analyseren
- Performance optimalisatie
- Gebruikerservaring verbeteren

---

### 17. Settings Management ✅

**Wat het biedt:**
- **Settings** - Systeem instellingen
  - `GET /api/settings` - Algemene instellingen
  - `PUT /api/settings` - Instellingen bijwerken
  - `POST /api/settings/rebase` - Rebase operatie
  - `GET /api/settings/stats` - Statistieken
  - `GET /api/settings/solr` - SOLR instellingen
  - `PUT /api/settings/solr` - SOLR instellingen bijwerken
  - `POST /api/settings/solr/test` - SOLR connectie testen
  - `POST /api/settings/solr/warmup` - SOLR index warmup
  - `GET /api/settings/rbac` - RBAC instellingen
  - `PUT /api/settings/rbac` - RBAC instellingen bijwerken
  - `GET /api/settings/multitenancy` - Multitenancy instellingen
  - `PUT /api/settings/multitenancy` - Multitenancy instellingen bijwerken
  - `GET /api/settings/llm` - LLM instellingen
  - `POST /api/settings/llm` - LLM instellingen bijwerken
  - `GET /api/settings/files` - File instellingen
  - `PUT /api/settings/files` - File instellingen bijwerken
  - `GET /api/settings/retention` - Retention instellingen
  - `PUT /api/settings/retention` - Retention instellingen bijwerken
  - `GET /api/settings/version` - Versie informatie
  - `GET /api/settings/statistics` - Statistieken
  - `GET /api/settings/cache` - Cache statistieken
  - `DELETE /api/settings/cache` - Cache legen
  - `POST /api/settings/cache/warmup-names` - Names cache warmup
  - `POST /api/settings/validate-all-objects` - Alle objecten valideren
  - `POST /api/settings/mass-validate` - Massa validatie

**Features:**
- ✅ Uitgebreide settings management
- ✅ SOLR configuratie
- ✅ RBAC (Role-Based Access Control)
- ✅ Multitenancy ondersteuning
- ✅ LLM integratie
- ✅ Cache management
- ✅ Retention policies

**Gebruik:**
- Systeem configureren
- Performance tuning
- Security instellingen

---

### 18. Organisations (Multi-tenancy) ✅

**Wat het biedt:**
- **Organisations** - Multi-tenancy ondersteuning
  - `GET /api/organisations` - Lijst organisaties
  - `POST /api/organisations` - Organisatie aanmaken
  - `GET /api/organisations/search` - Organisaties zoeken
  - `GET /api/organisations/stats` - Statistieken
  - `POST /api/organisations/clear-cache` - Cache legen
  - `GET /api/organisations/active` - Actieve organisatie
  - `GET /api/organisations/{uuid}` - Specifieke organisatie
  - `PUT /api/organisations/{uuid}` - Organisatie bijwerken
  - `POST /api/organisations/{uuid}/set-active` - Actieve organisatie instellen
  - `POST /api/organisations/{uuid}/join` - Organisatie joinen
  - `POST /api/organisations/{uuid}/leave` - Organisatie verlaten

**Features:**
- ✅ Multi-tenancy ondersteuning
- ✅ Organisatie isolatie
- ✅ Data scheiding per organisatie

**Gebruik:**
- Meerdere organisaties in één systeem
- Data isolatie
- Shared infrastructure

---

### 19. File Text Extraction & Indexing ✅

**Wat het biedt:**
- **File Text** - Tekst extractie uit bestanden
  - `GET /api/files/{fileId}/text` - Tekst ophalen
  - `POST /api/files/{fileId}/extract` - Tekst extraheren
  - `POST /api/files/extract/bulk` - Bulk extractie
  - `GET /api/files/extraction/stats` - Extractie statistieken
  - `DELETE /api/files/{fileId}/text` - Tekst verwijderen
  - `POST /api/files/chunks/process` - Chunks verwerken
  - `POST /api/files/{fileId}/chunks/process` - Bestand chunks verwerken
  - `GET /api/files/chunks/stats` - Chunk statistieken
  - `POST /api/solr/warmup/files` - Files warmup
  - `POST /api/solr/files/{fileId}/index` - Bestand indexeren
  - `POST /api/solr/files/reindex` - Bestanden reindexeren
  - `GET /api/solr/files/stats` - File index statistieken

**Features:**
- ✅ Tekst extractie uit PDF's, Word, etc.
  - PDF
  - Word
  - Excel
  - Text files
- ✅ Chunking voor grote bestanden
- ✅ SOLR indexing van bestandsinhoud
- ✅ Full-text search in bestanden

**Gebruik:**
- Documenten doorzoekbaar maken
- Content extraction
- Search in documenten

---

### 20. Tags Management ✅

**Wat het biedt:**
- **Tags** - Tags beheren
  - `GET /api/tags` - Lijst alle tags

**Features:**
- ✅ Tag systeem
- ✅ Categorisering van objecten

**Gebruik:**
- Objecten categoriseren
- Filteren op tags

---

### 21. OpenAPI Specification ✅

**Wat het biedt:**
- **OpenAPI** - API specificatie genereren
  - `GET /api/registers/{id}/oas` - OpenAPI spec voor register
  - `GET /api/registers/oas` - OpenAPI spec voor alle registers

**Features:**
- ✅ Automatische OpenAPI generatie
- ✅ API documentatie
- ✅ Swagger UI ondersteuning

**Gebruik:**
- API documentatie genereren
- Client code genereren
- API testing

---

### 22. Chat / AI Assistant ✅

**Wat het biedt:**
- **Chat** - AI assistant functionaliteit
  - `POST /api/chat/send` - Bericht versturen
  - `GET /api/chat/history` - Chat geschiedenis
  - `DELETE /api/chat/history` - Chat geschiedenis wissen
  - `POST /api/chat/feedback` - Feedback versturen

**Features:**
- ✅ AI chat functionaliteit
- ✅ Chat geschiedenis
- ✅ Feedback systeem

**Gebruik:**
- AI-powered assistentie
- Gebruikersondersteuning
- Query assistentie

---

## Wat Open Register NIET Biedt (Out-of-the-Box)

### ❌ Ontbrekende Functionaliteit

1. **Event-Driven Architecture**
   - Geen event publisher systeem
   - Geen message queue integratie
   - Geen event routing

2. **Notificaties**
   - Geen notificatie systeem
   - Geen email/SMS functionaliteit
   - Geen webhook ondersteuning

3. **Workflow Engine**
   - Geen workflow orchestration
   - Geen status transitions
   - Geen automatische task aanmaak

4. **Domein-specifieke Validatie**
   - Geen BRP/RVIG validaties
   - Geen business rules engine
   - Alleen basis JSON schema validatie

5. **API Authenticatie**
   - Geen JWT/Bearer token ondersteuning
   - Alleen Nextcloud Basic Auth
   - Geen OAuth2

6. **Berichten Systeem**
   - Geen berichtenbox functionaliteit
   - Geen bericht routing
   - Geen bericht templates

7. **Burger Portaal**
   - Geen "MijnOmgeving" functionaliteit
   - Geen burger-facing UI
   - Alleen API endpoints

---

## Samenvatting

### ✅ Wat Open Register WEL Biedt

1. **Core Data Management**
   - Registers & Schemas
   - CRUD operaties
   - Versiebeheer
   - Audit trails

2. **Advanced Features**
   - Relaties tussen objecten
   - File management
   - Search & SOLR integratie
   - Bulk operaties

3. **Infrastructure**
   - Multi-tenancy
   - RBAC
   - Caching
   - Settings management

4. **Analytics**
   - Dashboard
   - Statistics
   - Search trails
   - Performance monitoring

### ❌ Wat Open Register NIET Biedt

1. **Event-Driven Features**
   - Event publisher
   - Message queue
   - Event routing

2. **Communication**
   - Notificaties
   - Berichten systeem
   - Email/SMS

3. **Workflow**
   - Workflow engine
   - Status transitions
   - Task orchestration

4. **Domein-specifiek**
   - BRP validaties
   - Business rules
   - Domain logic

5. **User-Facing**
   - Burger portaal
   - UI components
   - Frontend

---

## Conclusie

**Open Register biedt:**
- ✅ Sterke basis voor data management
- ✅ Uitgebreide CRUD functionaliteit
- ✅ Versiebeheer en audit trails
- ✅ Search en indexing
- ✅ Multi-tenancy ondersteuning

**Open Register vereist extra implementatie voor:**
- ❌ Event-driven workflows
- ❌ Notificaties
- ❌ Domein-specifieke validatie
- ❌ Burger portaal
- ❌ Workflow orchestration

**Aanbeveling:**
Open Register is een uitstekende basis voor data management, maar vereist extra implementatie voor:
- Event-driven architecture (NRC, BRC, KNC)
- Workflow engine (TRC uitbreiding)
- Notificaties (KNC, OMC)
- Burger portaal (MOBB)

---

**Status:** ✅ Overzicht compleet  
**Volgende Stap:** Gebruik dit overzicht om te bepalen wat extra geïmplementeerd moet worden voor KTB compliance



