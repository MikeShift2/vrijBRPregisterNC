# Test Persoon: BSN 168149291

## Overzicht
Deze persoon heeft alle relaties beschikbaar voor testdoeleinden:
- ✅ Adres (verblijfplaats)
- ✅ Partners
- ✅ Kinderen
- ✅ Ouders
- ✅ Nationaliteiten

## Persoonlijke Gegevens

**BSN:** 168149291

**Naam:**
- Voornamen: Janne Malu Roelien Olive Tanneke
- Geslachtsnaam: Naiima Isman Adan
- Voorvoegsel: (geen)

**Geboorte:**
- Geboortedatum: 08-03-1982 (1982-03-08)
- Leeftijd: 43 jaar

**Geslacht:** Vrouw

## Relaties

### Partners
- **BSN:** 164287061
- **Naam:** Zaikina, Constatin-Cristinel Bassam
- **Geboortedatum:** 1983-10-30
- **Geslacht:** Man
- **A-nummer:** 936.7521.641

### Kinderen
- **BSN:** 382651765
- **Naam:** Zaikina, Jussi Huiberdina Biea Rifadije
- **Geboortedatum:** 2014-01-02
- **Geslacht:** Vrouw
- **A-nummer:** 129.7286.207

### Ouders
- **Ouder 1 - BSN:** 73218832
  - **Naam:** Zonnenberg, Wendelina Sixta
  - **Geboortedatum:** 1953-01-26
  - **Geslacht:** Vrouw
  - **A-nummer:** 672.6930.308

- **Ouder 2 - BSN:** 73218327
  - (Details beschikbaar via API)

### Nationaliteiten
- **Nationaliteit:** Nederlandse
- **Code:** 1

### Verblijfplaats
- **Straatnaam:** (via endpoint beschikbaar)
- **Huisnummer:** (via endpoint beschikbaar)
- **Postcode:** (via endpoint beschikbaar)
- **Woonplaats:** (via endpoint beschikbaar)

## API Endpoints voor Testen

### Persoon ophalen
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291?ggm=false"
```

### Partners ophalen
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners?ggm=false"
```

### Kinderen ophalen
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen?ggm=false"
```

### Ouders ophalen
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders?ggm=false"
```

### Nationaliteiten ophalen
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten?ggm=false"
```

### Verblijfplaats ophalen
```bash
curl -u admin:admin_secure_pass_2024 \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/verblijfplaats?ggm=false"
```

## Test in Browser

Open de testpagina en zoek op BSN:
```
http://localhost:8080/apps/openregister/haal-centraal-test
```

Zoek op: **168149291**

Deze persoon toont alle relaties:
- Partners
- Kinderen  
- Ouders
- Nationaliteiten
- Verblijfplaats







