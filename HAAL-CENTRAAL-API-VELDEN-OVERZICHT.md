# Haal Centraal BRP API - Velden Overzicht per Categorie

Dit document beschrijft alle velden die beschikbaar zijn via de Haal Centraal BRP Bevragen API per categorie.

## Categorie 01: Inschrijving
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}`

Beschikbare velden:
- `burgerservicenummer` (01.20)
- `aNummer` (01.10)
- `naam.voornamen` (02.10)
- `naam.geslachtsnaam` (02.40)
- `naam.voorvoegsel`
- `geboorte.datum.datum` (03.10)
- `geboorte.plaats` (03.20)
- `geboorte.land` (03.30)
- `geslachtsaanduiding` (04.10)
- Document velden (82.10, 82.20, 82.30)
- Geldigheid velden (85.10, 85.11, 86.10)

## Categorie 02: Persoon
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}`

Beschikbare velden:
- `burgerservicenummer` (01.20)
- `aNummer` (01.10)
- `naam.voornamen` (02.10)
- `naam.geslachtsnaam` (02.40)
- `naam.voorvoegsel`
- `naam.aanhef`
- `naam.geslachtsnaamPartner`
- `naam.voorvoegselPartner`
- `geboorte.datum.datum` (03.10)
- `geboorte.plaats` (03.20)
- `geboorte.land` (03.30)
- `geslachtsaanduiding` (04.10)
- `leeftijd`
- `voorletters`
- `adressering`
- `datumIngangFamilierechtelijkeBetrekking` (62.10)
- Document velden (82.10, 82.20, 82.30)
- Geldigheid velden (85.10, 85.11, 86.10)

## Categorie 03 & 04: Ouder 1 & Ouder 2
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}/ouders`

Beschikbare velden (volledige persoongegevens):
- `burgerservicenummer` (01.20)
- `aNummer` (01.10)
- `naam.voornamen` (02.10)
- `naam.geslachtsnaam` (02.40)
- `naam.voorvoegsel`
- `geboorte.datum.datum` (03.10)
- `geboorte.plaats` (03.20)
- `geboorte.land` (03.30)
- `geslachtsaanduiding` (04.10)
- `datumIngangFamilierechtelijkeBetrekking` (62.10)
- `nationaliteit` (05.10) - alleen voor ouder 2
- `redenOpnameNationaliteit` (63.10) - alleen voor ouder 2
- Document velden (82.10, 82.20, 82.30)
- Geldigheid velden (85.10, 85.11, 86.10)

## Categorie 05: Nationaliteit
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}/nationaliteiten`

Beschikbare velden:
- `nationaliteit.code`
- `nationaliteit.omschrijving` (05.10)
- `redenOpname` (63.10)
- `datumIngang` (85.10)
- `datumEinde`
- `volgcode` (85.11)
- Document velden (82.10, 82.20, 82.30)
- Geldigheid velden (85.10, 85.11, 86.10)

## Categorie 06: Huwelijk/Geregistreerd Partnerschap
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}/partners`

Beschikbare velden (volledige persoongegevens van partner):
- `burgerservicenummer` (01.20)
- `aNummer` (01.10)
- `naam.voornamen` (02.10)
- `naam.geslachtsnaam` (02.40)
- `naam.voorvoegsel`
- `geboorte.datum.datum` (03.10)
- `geboorte.plaats` (03.20)
- `geboorte.land` (03.30)
- `geslachtsaanduiding` (04.10)
- `soortVerbintenis` (15.10)
- `datumSluiting` (06.10)
- `plaatsSluiting` (06.20)
- `landSluiting` (06.30)
- Document velden (82.10, 82.20, 82.30)
- Geldigheid velden (85.10, 85.11, 86.10)

## Categorie 07: Verblijfplaats (adres)
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}/verblijfplaats`

Beschikbare velden:
- `straatnaam` (11.10)
- `straatnaamOfficieel` (11.11)
- `straatnaamNen` (11.12)
- `openbareRuimte` (11.15)
- `huisnummer` (11.20)
- `huisnummertoevoeging`
- `postcode` (11.60)
- `woonplaatsnaam` (11.70)
- `identificatieVerblijfplaats` (11.80)
- `identificatiecodeNummeraanduiding` (11.90)
- `gemeenteVanInschrijving` (09.10)
- `codeGemeenteVanInschrijving` (09.11)
- `datumInschrijving` (09.20)
- `functieAdres` (10.10)
- `gemeentedeel` (10.20)
- `datumAanvangAdreshouding` (10.30)
- `landVanwaarIngeschreven` (14.10)
- `datumVestigingInNederland` (14.20)
- `omschrijvingAangifteAdreshouding` (72.10)
- `datumEersteInschrijvingGba` (68.10)
- `indicatieGeheim` (70.10)
- `versienummer` (80.10)
- `datumtijdstempel` (80.20)
- Geldigheid velden (85.10, 85.11, 86.10)

## Categorie 10: Kind
**Endpoint:** `GET /ingeschrevenpersonen/{bsn}/kinderen`

Beschikbare velden (volledige persoongegevens van kind):
- `burgerservicenummer` (01.20)
- `aNummer` (01.10)
- `naam.voornamen` (02.10)
- `naam.geslachtsnaam` (02.40)
- `naam.voorvoegsel`
- `geboorte.datum.datum` (03.10)
- `geboorte.plaats` (03.20)
- `geboorte.land` (03.30)
- `geslachtsaanduiding` (04.10)
- Document velden (82.10, 82.20, 82.30)
- Geldigheid velden (85.10, 85.11, 86.10)

## Velden die mogelijk beschikbaar zijn maar niet via specifieke endpoints

### Categorie 08: Verblijfstitel
**Niet beschikbaar via Haal Centraal BRP Bevragen API**

### Categorie 09: Verblijf in het buitenland
**Niet beschikbaar via Haal Centraal BRP Bevragen API**

### Categorie 11: Overlijden
**Mogelijk beschikbaar in persoon data:**
- `overlijden.datum.datum`
- `overlijden.plaats`
- `overlijden.land`

### Categorie 12-16, 21: Overige categorieën
**Niet beschikbaar via Haal Centraal BRP Bevragen API**

