# ZGW Implementatie Status

**Datum:** 2025-01-27  
**Status:** In Uitvoering

---

## ✅ Voltooid

### 1. Schema Configuratie ✅

- ✅ **Schema ID 20 (Zaken)** - Geconfigureerd met ZGW-compliant properties
  - Identificatie, bronorganisatie, zaaktype, status, etc.
  - Properties volgens ZGW API specificatie

- ✅ **Schema ID 22 (Tasks)** - Aangemaakt
  - Task ID, zaak ID, task type, status, BSN, etc.
  - Status enum: planned, in_progress, done

### 2. ZGW Controllers ✅

- ✅ **ZgwZaakController.php** - Aangemaakt
  - `getZaken()` - Lijst alle zaken
  - `getZaak()` - Specifieke zaak ophalen
  - `createZaak()` - Nieuwe zaak aanmaken
  - `updateZaak()` - Zaak bijwerken
  - `deleteZaak()` - Zaak verwijderen
  - Data transformatie (Open Register ↔ ZGW formaat)

---

## 🔄 In Uitvoering

### 3. Routes Configuratie 🔄

- 🔄 Routes toevoegen aan `routes.php`
  - `/zgw/zaken` - GET, POST
  - `/zgw/zaken/{zaakId}` - GET, PUT, DELETE

---

## ⏳ Nog Te Doen

### 4. ZgwTaskController ⏳

- ⏳ `ZgwTaskController.php` bouwen
  - `getTasks()` - Lijst alle tasks
  - `getTask()` - Specifieke task ophalen
  - `createTask()` - Nieuwe task aanmaken
  - `updateTask()` - Task bijwerken
  - `deleteTask()` - Task verwijderen

### 5. Register Aanmaken ⏳

- ⏳ Register ID 3 (Zaken) aanmaken
- ⏳ Register voor Tasks aanmaken
- ⏳ Schema's koppelen aan registers

### 6. Testen ⏳

- ⏳ Test zaak-aanmaak
- ⏳ Test zaak-ophaling
- ⏳ Test task-aanmaak
- ⏳ Test ZGW API compliance

---

## 📋 Volgende Stappen

1. ✅ Schema's configureren - **VOLTOOID**
2. ✅ ZgwZaakController bouwen - **VOLTOOID**
3. 🔄 Routes toevoegen - **IN UITVOERING**
4. ⏳ ZgwTaskController bouwen
5. ⏳ Registers aanmaken
6. ⏳ Testen

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27







