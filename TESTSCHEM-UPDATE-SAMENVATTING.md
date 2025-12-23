# Testscherm Update - Relaties Weergave

## ✅ Uitgevoerde Wijzigingen

### Testscherm Uitgebreid

Het testscherm (`haalcentraaltest.php`) is bijgewerkt om automatisch relaties op te halen en weer te geven wanneer een persoon wordt gevonden.

**Nieuwe Functionaliteit:**
- ✅ Automatisch ophalen van partners wanneer een persoon wordt getoond
- ✅ Automatisch ophalen van kinderen wanneer een persoon wordt getoond
- ✅ Automatisch ophalen van ouders wanneer een persoon wordt getoond
- ✅ Automatisch ophalen van verblijfplaats (via apart endpoint)
- ✅ Automatisch ophalen van nationaliteiten wanneer een persoon wordt getoond

### Technische Implementatie

**Nieuwe Functie: `loadRelaties(bsn)`**
- Haalt alle relaties parallel op via Promise.all()
- Gebruikt de nieuwe Haal Centraal endpoints:
  - `/ingeschrevenpersonen/{bsn}/partners`
  - `/ingeschrevenpersonen/{bsn}/kinderen`
  - `/ingeschrevenpersonen/{bsn}/ouders`
  - `/ingeschrevenpersonen/{bsn}/verblijfplaats`
  - `/ingeschrevenpersonen/{bsn}/nationaliteiten`
- Toont relaties in gestructureerde HTML-secties
- Error handling voor ontbrekende relaties

**Integratie:**
- `displayPerson()` functie roept nu automatisch `loadRelaties()` aan
- Relaties worden getoond in een aparte sectie onder de persoonsgegevens
- Loading indicator tijdens het ophalen van relaties

## 📋 Open Register Configuratie

**Geen wijzigingen nodig in Open Register!**

De implementatie gebruikt:
- ✅ Bestaande Haal Centraal API endpoints (die we net hebben gemaakt)
- ✅ Bestaande schemas (ID 6 voor vrijBRP, ID 21 voor GGM)
- ✅ Bestaande registers (ID 2 voor vrijBRPpersonen)

**Waarom geen wijzigingen nodig:**
- De endpoints gebruiken directe PostgreSQL queries op probev-tabellen
- Relaties worden opgehaald via `pl_id` uit de persoon-objecten
- Geen nieuwe schemas of registers nodig voor relaties

## 🧪 Testen

### Via Testscherm

1. Ga naar: `http://localhost:8080/apps/openregister/haal-centraal-test`
2. Selecteer "Zoek in vrijBRP"
3. Zoek op een BSN (bijv. `168149291`)
4. Wacht tot de persoon wordt getoond
5. Scroll naar beneden - relaties worden automatisch geladen en getoond

### Via API Direct

```bash
# Test partners endpoint
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners"

# Test kinderen endpoint
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen"

# Test ouders endpoint
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders"

# Test verblijfplaats endpoint
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/verblijfplaats"

# Test nationaliteiten endpoint
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten"
```

## 📊 Weergave Structuur

Wanneer een persoon wordt getoond, worden de volgende secties automatisch toegevoegd:

1. **05. Partners** - Lijst van partners met naam en BSN
2. **09. Kinderen** - Lijst van kinderen met naam, geboortedatum en BSN
3. **09. Ouders** - Lijst van ouders (ouder 1 en ouder 2) met naam en BSN
4. **08. Verblijfplaats** - Volledige adresgegevens (als apart opgehaald)
5. **04. Nationaliteiten** - Lijst van nationaliteiten met code en omschrijving

## ⚠️ Belangrijke Notities

1. **Performance**: Relaties worden parallel opgehaald, maar dit kan nog steeds traag zijn bij veel relaties
2. **Error Handling**: Als een relatie-endpoint faalt, wordt een lege array getoond (geen error)
3. **pl_id Requirement**: Relaties vereisen `pl_id` in het persoon-object - zorg dat de view dit bevat
4. **Schema Type**: Relaties respecteren het geselecteerde schema type (vrijBRP of GGM)

## ✅ Status

- ✅ Testscherm bijgewerkt
- ✅ Relaties worden automatisch opgehaald
- ✅ Relaties worden gestructureerd weergegeven
- ✅ Geen wijzigingen nodig in Open Register schemas/registers

Het testscherm is nu volledig functioneel met alle relaties!







