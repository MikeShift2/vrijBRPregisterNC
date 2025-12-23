# Personen met Relaties - Overzicht

**Datum:** 2025-12-23  
**Totaal aantal personen met relaties:** 906

## Statistieken

- **Personen met partner(s):** 535
- **Personen met kinderen:** 512  
- **Personen met ouders:** 454

## Top 50 Personen met Meeste Relaties

Deze personen hebben de meeste relaties (partner, kinderen, ouders gecombineerd):

1. **BSN: 167943698** - Akhiat, Thobias
   - 5 kind(eren)
   - 2 ouder(s)

2. **BSN: 167941872** - de 't Hof, Maichel-Raymon Denisz
   - 1 partner(s)
   - 3 kind(eren)
   - 2 ouder(s)

3. **BSN: 167879352** - Abdul Khalegh Majid, Rogier Christiana Sanela
   - 2 partner(s)
   - 2 kind(eren)
   - 2 ouder(s)

4. **BSN: 547503866** - Ahout, Ahunisa Iulia Eefke Carlotte Marijn
   - 1 partner(s)
   - 5 kind(eren)

5. **BSN: 451476669** - Ahout, Odin Danego
   - 1 partner(s)
   - 5 kind(eren)

6. **BSN: 168151558** - Akhiat, Adonay M'Hamed Seriozjaievich Marcelis Solke
   - 1 partner(s)
   - 3 kind(eren)
   - 2 ouder(s)

7. **BSN: 26693288** - Al-Karawani, Elvis Daley Henrico
   - 1 partner(s)
   - 3 kind(eren)
   - 2 ouder(s)

8. **BSN: 111616177** - Alofs, Emy Umit Ayub Elfi
   - 1 partner(s)
   - 4 kind(eren)
   - 1 ouder(s)

9. **BSN: 167892769** - van Amen, Girbe Cornee Adrien Anas
   - 1 partner(s)
   - 3 kind(eren)
   - 2 ouder(s)

10. **BSN: 167936633** - Arnts, Emilia Moon
    - 1 partner(s)
    - 3 kind(eren)
    - 2 ouder(s)

## Gebruik

### Via Haal Centraal API

Je kunt deze personen opvragen via de Haal Centraal BRP API met expand parameter:

```bash
# Persoon met alle relaties
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/167943698?expand=*"

# Alleen partners en kinderen
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/167941872?expand=partners,kinderen"

# Alleen ouders
curl "http://localhost:8080/apps/openregister/ingeschrevenpersonen/167943698?expand=ouders"
```

### Via Prefill Test Pagina

Test deze personen op:
- http://localhost:8080/apps/openregister/prefill-test

Voer een BSN in (bijv. `167943698`) en het formulier wordt automatisch gevuld met alle relaties.

## Data Export

Volledige lijst beschikbaar in: `personen-met-relaties.json`

## Opmerkingen

- De data is gebaseerd op actuele records (ax='A' en hist='A')
- Personen kunnen meerdere partners hebben (bijv. bij scheiding/hertrouwen)
- Sommige personen hebben alleen ouders of alleen kinderen
- Totaal aantal personen in database: 20.631
- Percentage met relaties: ~4.4%

