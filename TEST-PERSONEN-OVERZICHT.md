# Test Personen Overzicht

## Aanbevolen Test Persoon: BSN 168149291

Deze persoon heeft de meeste relaties beschikbaar:

### ✅ Beschikbaar:
- **Partners:** 1 partner (BSN: 164287061)
- **Kinderen:** 1 kind (BSN: 382651765)
- **Ouders:** 2 ouders (BSN: 73218832 en 73218327)
- **Nationaliteiten:** 1 nationaliteit (Nederlandse, code: 1)

### ⚠️ Niet beschikbaar:
- **Verblijfplaats:** Geen adresgegevens via endpoint (mogelijk in hoofdobject)

### Persoonlijke Gegevens:
- **BSN:** 168149291
- **Naam:** Naiima Isman Adan, Janne Malu Roelien Olive Tanneke
- **Geboortedatum:** 1982-03-08 (43 jaar)
- **Geslacht:** Vrouw
- **A-nummer:** 101.8943.639

## Test Commando's

### Volledige test van alle relaties:
```bash
# Persoon
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291?ggm=false"

# Partners
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners?ggm=false"

# Kinderen
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen?ggm=false"

# Ouders
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders?ggm=false"

# Nationaliteiten
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten?ggm=false"

# Verblijfplaats (geeft 404, maar kan in hoofdobject zitten)
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/verblijfplaats?ggm=false"
```

## Test in Browser

Open de testpagina:
```
http://localhost:8080/apps/openregister/haal-centraal-test
```

Zoek op BSN: **168149291**

Deze persoon toont:
- ✅ Partners
- ✅ Kinderen
- ✅ Ouders
- ✅ Nationaliteiten
- ⚠️ Verblijfplaats (mogelijk in hoofdobject)

## Alternatieve Test Personen

### BSN 113620330 (met adres)
- Heeft adresgegevens (Aalsterpad 12, 6629KH Noord)
- Test of deze ook relaties heeft

### Andere BSNs met adres
Zie `test-bsn-met-adres.md` voor een lijst van 1326 BSNs met adresgegevens.

## Opmerkingen

- De verblijfplaats endpoint geeft soms 404, maar de adresgegevens kunnen wel in het hoofdobject zitten
- Niet alle personen hebben alle relaties beschikbaar
- BSN 168149291 is de beste keuze voor het testen van relaties







