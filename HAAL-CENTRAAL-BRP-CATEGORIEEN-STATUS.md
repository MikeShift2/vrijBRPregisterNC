# Haal Centraal BRP API - Categorieën Status

**Datum:** 2025-12-23  
**Status:** Overzicht van welke BRP categorieën beschikbaar zijn via Haal Centraal BRP Bevragen API

---

## ✅ Beschikbaar via Haal Centraal BRP Bevragen API

| Cat.nr. | Naam | Endpoint | Status |
|---------|------|----------|--------|
| **01** | **Inschrijving** | `GET /ingeschrevenpersonen/{bsn}` | ⚠️ Mogelijk in persoon data |
| **02** | **Persoon** | `GET /ingeschrevenpersonen/{bsn}` | ✅ Volledig beschikbaar |
| **03** | **Ouder 1** | `GET /ingeschrevenpersonen/{bsn}/ouders` | ✅ Volledig beschikbaar |
| **04** | **Ouder 2** | `GET /ingeschrevenpersonen/{bsn}/ouders` | ✅ Volledig beschikbaar |
| **05** | **Nationaliteit** | `GET /ingeschrevenpersonen/{bsn}/nationaliteiten` | ✅ Volledig beschikbaar |
| **06** | **Huwelijk/Geregistreerd partnerschap** | `GET /ingeschrevenpersonen/{bsn}/partners` | ✅ Volledig beschikbaar |
| **07** | **Verblijfplaats (adres)** | `GET /ingeschrevenpersonen/{bsn}/verblijfplaats` | ✅ Volledig beschikbaar |
| **10** | **Kind** | `GET /ingeschrevenpersonen/{bsn}/kinderen` | ✅ Volledig beschikbaar |

---

## ❌ Niet beschikbaar in Haal Centraal BRP Bevragen API

De volgende categorieën zijn **niet** beschikbaar in de Haal Centraal BRP Bevragen API specificatie:

| Cat.nr. | Naam | Reden |
|---------|------|-------|
| **08** | **Verblijfstitel** | Niet opgenomen in BRP Bevragen API |
| **09** | **Verblijf in het buitenland** | Niet opgenomen in BRP Bevragen API |
| **11** | **Overlijden** | ⚠️ Mogelijk wel in persoon data, maar geen apart endpoint |
| **12** | **Verblijfsaantekening EU/EER** | Niet opgenomen in BRP Bevragen API |
| **13** | **Gezag** | Niet opgenomen in BRP Bevragen API |
| **14** | **Reisdocument** | Niet opgenomen in BRP Bevragen API |
| **15** | **Kiesrecht** | Niet opgenomen in BRP Bevragen API |
| **16** | **Verwijzing** | Niet opgenomen in BRP Bevragen API |
| **21** | **Contactgegevens (optioneel)** | Niet opgenomen in BRP Bevragen API |

---

## 📊 Samenvatting

### Beschikbaar (8 categorieën):
- ✅ 01. Inschrijving (mogelijk)
- ✅ 02. Persoon
- ✅ 03. Ouder 1
- ✅ 04. Ouder 2
- ✅ 05. Nationaliteit
- ✅ 06. Huwelijk/Geregistreerd partnerschap
- ✅ 07. Verblijfplaats (adres)
- ✅ 10. Kind

### Niet beschikbaar (9 categorieën):
- ❌ 08. Verblijfstitel
- ❌ 09. Verblijf in het buitenland
- ⚠️ 11. Overlijden (mogelijk in persoon data)
- ❌ 12. Verblijfsaantekening EU/EER
- ❌ 13. Gezag
- ❌ 14. Reisdocument
- ❌ 15. Kiesrecht
- ❌ 16. Verwijzing
- ❌ 21. Contactgegevens

---

## 🔍 Haal Centraal BRP Bevragen API Specificatie

De [Haal Centraal BRP Bevragen API](https://github.com/BRP-API/Haal-Centraal-BRP-bevragen) is een **beperkte subset** van de volledige BRP data. Het is primair gericht op:

1. **Kerngegevens** van personen (naam, geboortedatum, geslacht)
2. **Relaties** (partners, kinderen, ouders)
3. **Verblijfplaats** (adres)
4. **Nationaliteiten**

**Niet** opgenomen zijn:
- Verblijfsrechtelijke gegevens (verblijfstitel)
- Emigratie gegevens
- Overlijdensgegevens (behalve mogelijk in persoon data)
- Gezag gegevens
- Reisdocumenten
- Kiesrecht
- Verwijzingen
- Contactgegevens

---

## 💡 Mogelijke Oplossingen

### Optie 1: Directe Database Queries
Voor categorieën die niet beschikbaar zijn via de Haal Centraal API, kunnen we directe PostgreSQL queries uitvoeren op de `bevax` database tabellen.

### Optie 2: OpenRegister Data
Sommige gegevens kunnen mogelijk beschikbaar zijn in het OpenRegister object zelf (via `_embedded` of directe velden).

### Optie 3: Lege Velden Tonen
Voor categorieën die niet beschikbaar zijn, tonen we lege velden met een melding dat deze data niet beschikbaar is via de Haal Centraal BRP Bevragen API.

---

## ✅ Huidige Implementatie Status

**Geïmplementeerd:**
- ✅ Alle beschikbare Haal Centraal endpoints worden gebruikt
- ✅ Relaties worden automatisch opgehaald (partners, kinderen, ouders, nationaliteiten)
- ✅ Verblijfplaats wordt opgehaald
- ✅ Persoon data wordt volledig opgehaald

**Nog te implementeren:**
- ⚠️ Aanvullende data ophalen uit persoon object (inschrijving, overlijden)
- ⚠️ Directe database queries voor niet-beschikbare categorieën (indien gewenst)
- ⚠️ Meldingen tonen voor niet-beschikbare categorieën

