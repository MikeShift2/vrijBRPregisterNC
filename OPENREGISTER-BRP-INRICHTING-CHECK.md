# Open Register BRP Inrichting - Check tegen Documentatie

**Datum:** 2025-01-27  
**Doel:** Controleren of de huidige Open Register inrichting overeenkomt met de architectuur documentatie

---

## 📋 Architectuur Documentatie Vereisten

Volgens de documentatie moet vrijBRP worden ontsloten als **volwaardig bronregister (Scenario 3)** met:

### Vereiste REST API Endpoints
- `/registers/inwoners` - Inwoners register
- `/registers/huwelijken` - Huwelijken register  
- `/registers/reisdoc` - Reisdocumenten register
- `/registers/kiesrecht` - Kiesrecht register
- etc.

### Vereiste Functionaliteit
- ✅ Schrijf- en leestoegang via REST API's
- ✅ Logging (POM) 
- ✅ Eventpublicatie / webhook
- ✅ Common Ground compliant
- ✅ Logisch Ontwerp BRP compliant

---

## 🔍 Huidige Implementatie

### 1. Register Structuur

**Huidige situatie:**
- Register ID 2: `vrijBRPpersonen` (bevat meerdere schemas)
- Register ID 3: `Adressen`
- Register ID 7: `Mutaties`
- Register ID 5: `Zaken`

**Schemas binnen Register 2:**
- Schema ID 6: Personen (vrijBRP)
- Schema ID 21: GGM IngeschrevenPersoon
- Schema ID 7: Adressen
- Plus andere schemas (Huwelijken, Nationaliteiten, Reisdocumenten, etc.)

### 2. API Endpoints

**Huidige endpoints:**
- ✅ `/api/registers/{id}` - Generieke register endpoints
- ✅ `/api/objects/{register}/{schema}/{id}` - Object CRUD
- ✅ `/ingeschrevenpersonen` - Haal Centraal BRP Bevragen endpoints
- ✅ `/ingeschrevenpersonen/{bsn}/partners` - Relaties
- ✅ `/ingeschrevenpersonen/{bsn}/kinderen` - Relaties
- ✅ `/ingeschrevenpersonen/{bsn}/ouders` - Relaties
- ✅ `/ingeschrevenpersonen/{bsn}/nationaliteiten` - Relaties
- ✅ `/ingeschrevenpersonen/{bsn}/verblijfplaatshistorie` - Historie

**❌ ONTBREEKT:**
- `/registers/inwoners` - Specifiek register endpoint
- `/registers/huwelijken` - Specifiek register endpoint
- `/registers/reisdoc` - Specifiek register endpoint
- `/registers/kiesrecht` - Specifiek register endpoint

### 3. Database Structuur

**PostgreSQL (probev schema):**
- ✅ `inw_ax` - Inwoners (cat 1)
- ✅ `huw_ax` - Huwelijken (cat 5)
- ✅ `reisd_ax` - Reisdocumenten (cat 12)
- ✅ `kiesr_ax` - Kiesrecht (cat 13)
- ✅ `nat_ax` - Nationaliteiten (cat 4)
- ✅ `vb_ax` - Verblijven (cat 8)
- ✅ `pl` - Persoonslijst kleerhanger

**OpenRegister configuratie:**
- ✅ Source ID 1: Bevax database (probev schema)
- ✅ Schemas gekoppeld aan externe source
- ⚠️ Schemas verwijzen nog naar oude structuur (moet worden bijgewerkt)

### 4. Functionaliteit

**✅ Aanwezig:**
- ✅ CRUD operaties via `/api/objects/{register}/{schema}/{id}`
- ✅ Haal Centraal BRP Bevragen API implementatie
- ✅ Audit trails (logging)
- ✅ Search trails
- ✅ Relaties via `_embedded` objecten
- ✅ Historie endpoints

**❌ ONTBREEKT of ONVOLDOENDE:**
- ⚠️ Specifieke register endpoints per categorie (`/registers/inwoners`, etc.)
- ❌ **Eventpublicatie / webhook functionaliteit** - **NIET GEÏMPLEMENTEERD**
  - Geen `EventPublisher` service
  - Geen `oc_openregister_events` tabel
  - Geen webhook endpoints
  - Geen message queue integratie
- ⚠️ POM logging specifiek voor BRP (wel audit trails, maar niet specifiek POM)
- ⚠️ Schrijftoegang via specifieke register endpoints

---

## 📊 Vergelijking: Documentatie vs Huidige Implementatie

| Vereiste | Documentatie | Huidige Implementatie | Status |
|----------|-------------|----------------------|--------|
| **Register structuur** | Aparte registers per categorie (`/registers/inwoners`, etc.) | Eén register met meerdere schemas | ⚠️ **VERSCHIL** |
| **API endpoints** | `/registers/{categorie}` | `/api/registers/{id}` + `/api/objects/{register}/{schema}` | ⚠️ **VERSCHIL** |
| **Leestoegang** | Via register endpoints | Via object endpoints + Haal Centraal | ✅ **AANWEZIG** |
| **Schrijftoegang** | Via register endpoints | Via object endpoints | ⚠️ **ANDERS** |
| **Logging (POM)** | Vereist | Audit trails aanwezig | ⚠️ **ONVOLDOENDE** |
| **Eventpublicatie** | Vereist | **NIET GEÏMPLEMENTEERD** (geen EventPublisher, geen events tabel, geen webhooks) | ❌ **ONTBREEKT** |
| **Common Ground** | Vereist | Haal Centraal API compliant | ✅ **AANWEZIG** |
| **LO BRP** | Vereist | Probev schema volgens PL-AX | ✅ **AANWEZIG** |

---

## 🔧 Aanbevelingen

### Optie 1: Huidige Structuur Aanpassen (Aanbevolen)

**Voordeel:** Behoudt bestaande functionaliteit, voegt alleen endpoints toe

**Acties:**
1. **Maak aparte registers aan per categorie:**
   - Register: `inwoners` → Schema: Personen
   - Register: `huwelijken` → Schema: Huwelijken
   - Register: `reisdoc` → Schema: Reisdocumenten
   - Register: `kiesrecht` → Schema: Kiesrecht
   - etc.

2. **Voeg register-specifieke routes toe:**
   ```php
   // In routes.php
   ['name' => 'registers#getInwoners', 'url' => '/registers/inwoners', 'verb' => 'GET'],
   ['name' => 'registers#createInwoner', 'url' => '/registers/inwoners', 'verb' => 'POST'],
   ['name' => 'registers#getHuwelijken', 'url' => '/registers/huwelijken', 'verb' => 'GET'],
   // etc.
   ```

3. **Implementeer register controllers:**
   - `BrpRegisterController.php` met methods per categorie
   - Mapt naar bestaande `/api/objects/{register}/{schema}` endpoints

### Optie 2: Documentatie Aanpassen

**Voordeel:** Geen code wijzigingen nodig

**Acties:**
1. Documentatie bijwerken om aan te geven dat:
   - Registers worden benaderd via `/api/registers/{id}`
   - Objecten via `/api/objects/{register}/{schema}/{id}`
   - Haal Centraal endpoints via `/ingeschrevenpersonen`

---

## ✅ Wat WEL Goed Is

1. **Database structuur:** ✅ Probev schema volgens PL-AX specificatie
2. **Haal Centraal API:** ✅ Volledig geïmplementeerd
3. **CRUD functionaliteit:** ✅ Via object endpoints
4. **Relaties:** ✅ Via `_embedded` objecten
5. **Historie:** ✅ Via Haal Centraal Historie API
6. **Audit trails:** ✅ Logging aanwezig
7. **Common Ground:** ✅ Haal Centraal compliant

---

## ❓ Vragen voor Verduidelijking

1. **Moeten er echt aparte `/registers/inwoners` endpoints komen, of is `/api/registers/2` + `/api/objects/2/6/{id}` voldoende?**
2. **Is eventpublicatie / webhook functionaliteit vereist, of is audit trail logging voldoende?**
3. **Moet POM logging specifiek worden geïmplementeerd, of zijn audit trails voldoende?**
4. **Moeten registers per categorie worden gescheiden, of is één register met meerdere schemas acceptabel?**

---

## 📝 Conclusie

De huidige implementatie is **grotendeels functioneel** maar heeft **belangrijke verschillen** met de documentatie:

### ✅ Wat WEL goed is:
- ✅ **CRUD functionaliteit:** Volledig via object endpoints
- ✅ **Haal Centraal API:** Volledig geïmplementeerd
- ✅ **Relaties:** Via `_embedded` objecten
- ✅ **Historie:** Via Haal Centraal Historie API
- ✅ **Audit trails:** Logging aanwezig
- ✅ **Database structuur:** Probev schema volgens PL-AX

### ❌ Wat ONTBREEKT:
- ❌ **Eventpublicatie / webhooks:** **NIET GEÏMPLEMENTEERD**
  - Geen EventPublisher service
  - Geen events database tabel
  - Geen webhook endpoints
  - Geen message queue integratie
- ⚠️ **Register structuur:** Geen aparte `/registers/{categorie}` endpoints
- ⚠️ **POM logging:** Audit trails aanwezig, maar niet specifiek POM-formaat

### ⚠️ Structuurverschillen:
- ⚠️ **API endpoints:** Documentatie beschrijft `/registers/inwoners`, implementatie gebruikt `/api/registers/{id}` + `/api/objects/{register}/{schema}`
- ⚠️ **Register organisatie:** Documentatie suggereert aparte registers per categorie, implementatie gebruikt één register met meerdere schemas

**Aanbeveling:** 
1. **Prioriteit 1:** Implementeer eventpublicatie / webhook functionaliteit (vereist volgens documentatie)
2. **Prioriteit 2:** Kies voor **Optie 1** (aparte register endpoints) als de documentatie exact moet worden gevolgd, of **Optie 2** (documentatie aanpassen) als de huidige structuur acceptabel is
