# KTB Landschapsoverzicht - Mapping naar Huidige Open Register Inrichting

**Datum:** 2025-01-27  
**Bron:** [OWC] KTB landschapsoverzicht - versie 0_1.pdf  
**Doel:** Reproductie van berichtenroutes voor huidige Open Register + vrijBRP setup

---

## Overzicht: KTB Componenten vs. Huidige Inrichting

### Component Mapping

| KTB Component | Huidige Equivalent | Status | Opmerkingen |
|---------------|---------------------|--------|-------------|
| **KISS** | ❌ Niet aanwezig | Ontbreekt | Klantcontact systeem voor KCC medewerkers |
| **MOBB** (Mijn Overheid BerichtenBox) | ❌ Niet aanwezig | Ontbreekt | Berichtenbox voor burgers |
| **OF** (Formulieren Component) | ⚠️ Gedeeltelijk | Via ZGW | Formulieren kunnen via ZGW zaken worden aangemaakt |
| **DRC** (Document Registratie Component) | ✅ ZgwDocumentController | Aanwezig | Documenten API geïmplementeerd |
| **CRC** (Contact Registratie Component) | ❌ Niet aanwezig | Ontbreekt | Contact registratie systeem |
| **KNC** (Klant Notificatie Component) | ❌ Niet aanwezig | Ontbreekt | Notificatie systeem (Logius) |
| **OMC** (Output Management Component) | ❌ Niet aanwezig | Ontbreekt | Output management voor print/post |
| **KRC** (Klant Registratie Component) | ⚠️ Gedeeltelijk | Via Open Register | Personen data in Open Register |
| **MO** (MijnOmgeving) | ❌ Niet aanwezig | Ontbreekt | Burger portaal |
| **VRC** (Verzoek Registratie Component) | ⚠️ Gedeeltelijk | Via ZGW Zaken | Verzoeken als Zaken in ZgwZaakController |
| **NRC** (Notificatie Routering Component) | ❌ Niet aanwezig | Ontbreekt | Event routing systeem |
| **ZAC** (Zaak Afhandel Component) | ⚠️ Gedeeltelijk | ZgwZaakController | Basis zaakafhandeling aanwezig |
| **TRC** (Taak Registratie Component) | ⚠️ Gedeeltelijk | ZgwTaskController | Tasks API basis aanwezig |
| **BRC** (Bericht Registratie Component) | ❌ Niet aanwezig | Ontbreekt | Berichten registratie systeem |
| **ZRC** (Zaak Registratie Component) | ✅ ZgwZaakController | Aanwezig | Zaken API geïmplementeerd |
| **TSA** (Taak Specifieke Applicatie) | ❌ Niet aanwezig | Ontbreekt | Back-office applicaties |

**Legenda:**
- ✅ Volledig aanwezig
- ⚠️ Gedeeltelijk aanwezig
- ❌ Niet aanwezig

---

## Huidige Architectuur - Berichtenroutes

### Route 1: Mutatie Verzoek (vrijBRP Dossiers)

```
┌─────────────────────────────────────────────────────────────┐
│  ACTOR: Inwoner / Ondernemer                               │
│  (Via formulier of externe applicatie)                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ POST /api/v1/relocations/intra
                     │ POST /api/v1/birth
                     │ POST /api/v1/commitment
                     │ POST /api/v1/deaths/in-municipality
                     ↓
┌─────────────────────────────────────────────────────────────┐
│  LAAG 2: Open Register                                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  VrijBrpDossiersController                          │   │
│  │  → Routes: /api/v1/*                                │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ [Autorisatie]                                   │
│         │   - JWT token validatie                           │
│         │   - Rechten check                                 │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  LAAG 3: vrijBRP Logica Service                      │   │
│  │  ┌──────────────────────────────────────────────┐   │   │
│  │  │  VrijBrpValidationService                     │   │   │
│  │  │  ├─→ SyntacticValidator                       │   │   │
│  │  │  ├─→ SemanticValidator                       │   │   │
│  │  │  ├─→ RvigValidator                           │   │   │
│  │  │  └─→ DataTransformationService               │   │   │
│  │  └──────────────────────────────────────────────┘   │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ [Validatie Resultaat]                          │
│         │   ├─→ Success → Getransformeerde data            │
│         │   └─→ Error → Gestructureerde error response    │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ObjectService                                       │   │
│  │  → createFromArray()                                 │   │
│  │  → Register ID 7 (Mutaties)                          │   │
│  │  → Schema ID 24 (Mutaties)                           │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  LAAG 1: Database                                     │   │
│  │  → openregister_objects                              │   │
│  │  → oc_openregister_mutaties                          │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ ⚠️ EVENT: mutatie.created (NIET GEÏMPL.)      │
│         │                                                    │
│         └─→ [Response]                                      │
│             - dossier_id                                    │
│             - status                                        │
│             - created_at                                    │
└─────────────────────────────────────────────────────────────┘
```

**Verschil met KTB:**
- ❌ Geen VRC (Verzoek Registratie Component) - mutaties gaan direct naar Open Register
- ❌ Geen NRC (Notificatie Routering Component) - geen event routing
- ❌ Geen BRC (Bericht Registratie Component) - geen bericht wordt aangemaakt
- ❌ Geen TRC (Taak Registratie Component) - geen automatische taak aanmaak
- ❌ Geen KNC (Klant Notificatie Component) - geen notificatie naar burger

---

### Route 2: Zaak Aanmaken (ZGW)

```
┌─────────────────────────────────────────────────────────────┐
│  ACTOR: MDW Medewerker Back-office                         │
│  (Via TSA of direct via API)                               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ POST /zgw/zaken
                     ↓
┌─────────────────────────────────────────────────────────────┐
│  LAAG 2: Open Register                                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ZgwZaakController                                   │   │
│  │  → Routes: /zgw/zaken                                │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ [Validatie]                                     │
│         │   - Required fields check                         │
│         │   - ZGW formaat validatie                        │
│         │                                                    │
│         ├─→ [Data Transformatie]                            │
│         │   - transformFromZgwZaak()                       │
│         │   - ZGW → Open Register formaat                   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ObjectService                                       │   │
│  │  → createFromArray()                                 │   │
│  │  → Register ID 5 (Zaken)                             │   │
│  │  → Schema ID 20 (Zaken)                              │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  LAAG 1: Database                                     │   │
│  │  → openregister_objects                              │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ ⚠️ EVENT: zaak.created (NIET GEÏMPL.)         │
│         │                                                    │
│         ├─→ ⚠️ EVENT: bericht.aangemaakt (NIET GEÏMPL.)   │
│         │   → Zou naar BRC moeten gaan                      │
│         │                                                    │
│         ├─→ ⚠️ EVENT: taak.aangemaakt (NIET GEÏMPL.)     │
│         │   → Zou naar TRC moeten gaan                      │
│         │                                                    │
│         └─→ [Response]                                      │
│             - ZGW Zaak object                               │
└─────────────────────────────────────────────────────────────┘
```

**Verschil met KTB:**
- ✅ ZRC (Zaak Registratie Component) aanwezig via ZgwZaakController
- ❌ Geen NRC (Notificatie Routering Component) - geen event routing
- ❌ Geen BRC (Bericht Registratie Component) - geen automatisch bericht
- ❌ Geen TRC (Taak Registratie Component) - geen automatische taak aanmaak
- ❌ Geen KNC (Klant Notificatie Component) - geen notificatie

---

### Route 3: Bericht Ophalen (Bevragen)

```
┌─────────────────────────────────────────────────────────────┐
│  ACTOR: Inwoner / Ondernemer                               │
│  (Via externe applicatie of direct API call)               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ GET /ingeschrevenpersonen/{bsn}
                     │ GET /ingeschrevenpersonen
                     │ GET /zgw/zaken
                     │ GET /zgw/zaken/{zaakId}
                     ↓
┌─────────────────────────────────────────────────────────────┐
│  LAAG 2: Open Register                                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  HaalCentraalBrpController                          │   │
│  │  ZgwZaakController                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ [Autorisatie]                                   │
│         │   - Nextcloud auth                                │
│         │                                                    │
│         ├─→ [Cache Check]                                   │
│         │   - CacheService (30 min cache)                   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Database Query                                       │   │
│  │  → getPersonByBsnFromDatabase()                      │   │
│  │  → getZakenFromDatabase()                             │   │
│  │  → JSON_EXTRACT queries                               │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ [Field Selection]                               │
│         │   - FieldSelectionService                         │
│         │                                                    │
│         ├─→ [Expand]                                         │
│         │   - ExpandService                                 │
│         │   - Relaties ophalen                              │
│         │                                                    │
│         ├─→ [Data Transformatie]                            │
│         │   - transformToHaalCentraal()                    │
│         │   - transformToZgwZaak()                          │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Search Trail Logging                                 │   │
│  │  → SearchTrailService                                 │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         └─→ [Response]                                      │
│             - JSON response                                 │
└─────────────────────────────────────────────────────────────┘
```

**Verschil met KTB:**
- ❌ Geen MO (MijnOmgeving) - geen burger portaal
- ❌ Geen MOBB (Mijn Overheid BerichtenBox) - geen berichtenbox
- ❌ Geen BRC (Bericht Registratie Component) - geen berichten systeem
- ✅ Directe API calls mogelijk (niet via portaal)

---

## Gewenste Architectuur - KTB Model voor Open Register

### Route 1: Mutatie Verzoek met Event-Driven Flow

```
┌─────────────────────────────────────────────────────────────┐
│  ACTOR: Inwoner / Ondernemer                               │
│  (Via formulier of externe applicatie)                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ POST /api/v1/relocations/intra
                     ↓
┌─────────────────────────────────────────────────────────────┐
│  LAAG 2: Open Register                                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  VrijBrpDossiersController                          │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ [Autorisatie]                                   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  LAAG 3: vrijBRP Logica Service                      │   │
│  │  → Validatie & Transformatie                         │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ObjectService                                       │   │
│  │  → createFromArray()                                 │   │
│  │  → Register ID 7 (Mutaties)                         │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  LAAG 1: Database                                     │   │
│  │  → openregister_objects                              │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ⚠️ TE IMPLEMENTEREN: Event Publisher                │   │
│  │  → EVENT: mutatie.created                            │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ↓                                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ⚠️ TE IMPLEMENTEREN: NRC                            │   │
│  │  (Notificatie Routering Component)                    │   │
│  │  → Routeert events naar juiste componenten            │   │
│  └──────────────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ EVENT: verzoek.created                          │
│         │   ↓                                                │
│         │  ┌────────────────────────────────────────────┐   │
│         │  │ ⚠️ TE IMPLEMENTEREN: VRC                    │   │
│         │  │ (Verzoek Registratie Component)             │   │
│         │  │ → Registreert verzoek                       │   │
│         │  └────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ EVENT: bericht.aangemaakt                      │
│         │   ↓                                                │
│         │  ┌────────────────────────────────────────────┐   │
│         │  │ ⚠️ TE IMPLEMENTEREN: BRC                    │   │
│         │  │ (Bericht Registratie Component)             │   │
│         │  │ → Registreert bericht                       │   │
│         │  │ → POST /bericht                             │   │
│         │  └────────────────────────────────────────────┘   │
│         │                                                    │
│         ├─→ EVENT: taak.aangemaakt                          │
│         │   ↓                                                │
│         │  ┌────────────────────────────────────────────┐   │
│         │  │ ⚠️ TE IMPLEMENTEREN: TRC                    │   │
│         │  │ (Taak Registratie Component)                │   │
│         │  │ → Registreert taak                          │   │
│         │  │ → POST /externetaken                        │   │
│         │  └────────────────────────────────────────────┘   │
│         │                                                    │
│         └─→ EVENT: notificatie.versturen                    │
│             ↓                                                │
│            ┌────────────────────────────────────────────┐   │
│            │ ⚠️ TE IMPLEMENTEREN: KNC                    │   │
│            │ (Klant Notificatie Component)                │   │
│            │ → Verstuurt notificatie                      │   │
│            │ → Via OMC (Output Management Component)     │   │
│            └────────────────────────────────────────────┘   │
│                     │                                        │
│                     ↓                                        │
│            ┌────────────────────────────────────────────┐   │
│            │ ⚠️ TE IMPLEMENTEREN: MOBB                   │   │
│            │ (Mijn Overheid BerichtenBox)                │   │
│            │ → Bericht beschikbaar voor burger           │   │
│            └────────────────────────────────────────────┘   │
```

---

## Implementatie Roadmap

### Fase 1: Event-Driven Basis (Weken 1-2)

**Doel:** Basis eventing systeem implementeren

1. **Event Publisher Service**
   ```php
   // lib/Service/Eventing/EventPublisher.php
   class EventPublisher {
       public function publish(string $eventType, array $data): void
   }
   ```

2. **Event Database Tabel**
   ```sql
   CREATE TABLE oc_openregister_events (
       id INT AUTO_INCREMENT PRIMARY KEY,
       event_type VARCHAR(100) NOT NULL,
       event_data JSON,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

3. **Events in Controllers**
   - `mutatie.created` in VrijBrpDossiersController
   - `zaak.created` in ZgwZaakController
   - `zaak.status_changed` in ZgwZaakController

**Resultaat:** Alle mutaties genereren events

---

### Fase 2: Notificatie Routering Component (NRC) (Weken 3-4)

**Doel:** Event routing systeem

1. **NRC Service**
   ```php
   // lib/Service/Routing/NotificationRoutingService.php
   class NotificationRoutingService {
       public function routeEvent(string $eventType, array $data): void
   }
   ```

2. **Routing Rules**
   - `mutatie.created` → `verzoek.created` → `bericht.aangemaakt` → `taak.aangemaakt`
   - `zaak.created` → `bericht.aangemaakt` → `taak.aangemaakt`
   - `zaak.status_changed` → `bericht.aangemaakt`

**Resultaat:** Events worden gerouteerd naar juiste componenten

---

### Fase 3: Bericht Registratie Component (BRC) (Weken 5-6)

**Doel:** Berichten systeem

1. **BRC Controller**
   ```php
   // lib/Controller/BerichtController.php
   class BerichtController {
       public function createBericht(): JSONResponse
       public function getBerichten(): JSONResponse
       public function setBerichtGelezen(string $berichtId): JSONResponse
   }
   ```

2. **Bericht Schema**
   - Schema ID 25 (Berichten)
   - Register ID 8 (Berichten)

3. **API Endpoints**
   - `POST /berichten` - Bericht aanmaken
   - `GET /berichten` - Berichten ophalen
   - `PATCH /berichten/{id}/gelezen` - Bericht op gelezen zetten

**Resultaat:** Berichten kunnen worden geregistreerd en opgehaald

---

### Fase 4: Taak Registratie Component (TRC) Uitbreiding (Weken 7-8)

**Doel:** Automatische taak aanmaak

1. **TRC Uitbreiding**
   - Automatische taak aanmaak bij events
   - Task templates per mutatie type
   - Task dependencies

2. **API Endpoints**
   - `POST /externetaken` - Externe taak aanmaken
   - `GET /taken` - Taken ophalen
   - `PATCH /taken/{id}/status` - Taak status bijwerken

**Resultaat:** Taken worden automatisch aangemaakt bij mutaties

---

### Fase 5: Mijn Overheid BerichtenBox (MOBB) (Weken 9-10)

**Doel:** Burger portaal voor berichten

1. **MOBB Component**
   - Berichten ophalen voor burger
   - Bericht op gelezen zetten
   - Bericht archiveren

2. **API Endpoints**
   - `GET /mijn-berichten` - Berichten voor ingelogde gebruiker
   - `PATCH /mijn-berichten/{id}/gelezen` - Bericht op gelezen
   - `GET /mijn-taken` - Taken voor ingelogde gebruiker

**Resultaat:** Burgers kunnen hun berichten en taken inzien

---

### Fase 6: Klant Notificatie Component (KNC) (Weken 11-12)

**Doel:** Notificaties versturen

1. **KNC Service**
   ```php
   // lib/Service/Notification/KlantNotificationService.php
   class KlantNotificationService {
       public function sendNotification(string $bsn, string $type, array $data): void
   }
   ```

2. **Output Management Component (OMC)**
   - Kanaalvoorkeur ophalen (Digitaal Post J/N)
   - Contactgegevens ophalen
   - Print/Poststraat activeren

3. **Notificatie Kanalen**
   - Email
   - SMS (optioneel)
   - Digitaal Post (via MOBB)
   - Print/Post (via OMC)

**Resultaat:** Burgers ontvangen notificaties bij mutaties

---

## Advies & Aanbevelingen

### Prioriteit 1: Event-Driven Basis (🔴 Hoog)

**Waarom eerst:**
- Basis voor alle andere componenten
- Relatief eenvoudig te implementeren
- Directe waarde (audit trail)

**Implementatie:**
- Event Publisher Service
- Event database tabel
- Events in bestaande controllers

**Tijd:** 1-2 weken

---

### Prioriteit 2: NRC + BRC (🟡 Medium)

**Waarom tweede:**
- Basis voor berichten systeem
- Noodzakelijk voor notificaties
- Relatief eenvoudig te implementeren

**Implementatie:**
- NotificationRoutingService
- BerichtController
- Bericht schema en register

**Tijd:** 3-4 weken

---

### Prioriteit 3: TRC Uitbreiding (🟡 Medium)

**Waarom derde:**
- Automatische taak aanmaak
- Workflow ondersteuning
- Bestaande TRC uitbreiden

**Implementatie:**
- Automatische taak aanmaak bij events
- Task templates
- Task dependencies

**Tijd:** 2-3 weken

---

### Prioriteit 4: MOBB (🟢 Laag)

**Waarom vierde:**
- Burger portaal
- Complexer te implementeren
- Vereist frontend ontwikkeling

**Implementatie:**
- MijnOmgeving portaal
- Mijn Berichten module
- Mijn Taken module

**Tijd:** 4-6 weken

---

### Prioriteit 5: KNC + OMC (🟢 Laag)

**Waarom vijfde:**
- Notificaties systeem
- Vereist externe integraties
- Complexer te implementeren

**Implementatie:**
- KlantNotificationService
- OutputManagementService
- Email/SMS integraties

**Tijd:** 3-4 weken

---

## Conclusie

**Huidige Situatie:**
- ✅ Basis architectuur aanwezig
- ✅ ZGW API's geïmplementeerd
- ✅ vrijBRP validatie aanwezig
- ❌ Geen event-driven flow
- ❌ Geen berichten systeem
- ❌ Geen notificaties

**Gewenste Situatie (KTB Model):**
- ✅ Event-driven architectuur
- ✅ Berichten systeem (BRC)
- ✅ Notificaties (KNC)
- ✅ Burger portaal (MOBB)
- ✅ Automatische taak aanmaak (TRC)

**Aanbevolen Aanpak:**
1. **Start met Event-Driven Basis** (Weken 1-2)
2. **Implementeer NRC + BRC** (Weken 3-6)
3. **Uitbreid TRC** (Weken 7-8)
4. **Bouw MOBB** (Weken 9-10)
5. **Implementeer KNC + OMC** (Weken 11-12)

**Totale Tijd:** 12-14 weken (3-3.5 maanden)

**Verwachte Resultaat:**
- Volledige KTB-compliant architectuur
- Event-driven workflows
- Berichten systeem
- Notificaties
- Burger portaal

---

**Status:** ✅ Analyse compleet, roadmap klaar  
**Volgende Stap:** Beslissen over implementatie volgorde en starten met Fase 1



