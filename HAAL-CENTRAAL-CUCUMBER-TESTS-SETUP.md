# Haal Centraal Cucumber Tests Setup

**Datum:** 2025-01-27  
**Status:** ✅ Setup voltooid

---

## Overzicht

Deze test suite test de Haal Centraal BRP Bevragen API implementatie tegen de officiële Cucumber test specificatie.

---

## Vereisten

- Ruby >= 2.7.0
- Bundler
- Toegang tot de API (standaard: `http://localhost:8080/apps/openregister`)

---

## Setup

### 1. Automatische Setup

```bash
./setup-haal-centraal-cucumber-tests.sh
```

Dit script:
- ✅ Controleert Ruby en Bundler installatie
- ✅ Maakt test directory aan
- ✅ Installeert dependencies
- ✅ Maakt configuratie bestanden

### 2. Handmatige Setup

```bash
# Maak test directory
mkdir -p haal-centraal-cucumber-tests
cd haal-centraal-cucumber-tests

# Installeer dependencies
bundle install
```

---

## Test Structuur

```
haal-centraal-cucumber-tests/
├── features/
│   ├── ingeschrevenpersonen.feature    # Feature definities
│   ├── step_definitions/
│   │   └── api_steps.rb                # Step definitions
│   └── support/
│       └── env.rb                      # Environment configuratie
├── Gemfile                             # Ruby dependencies
└── cucumber.yml                        # Cucumber configuratie
```

---

## Tests Uitvoeren

### Basis Commando

```bash
cd haal-centraal-cucumber-tests
bundle exec cucumber
```

### Met Script

```bash
./run-haal-centraal-tests.sh
```

Dit script:
- ✅ Controleert of test directory bestaat
- ✅ Installeert dependencies indien nodig
- ✅ Voert tests uit
- ✅ Genereert JSON rapport

### Met Custom API URL

```bash
API_URL=http://example.com/apps/openregister ./run-haal-centraal-tests.sh
```

---

## Test Scenarios

### Ingeschreven Personen

1. ✅ **Lijst ingeschreven personen ophalen**
   - GET `/ingeschrevenpersonen`
   - Valideert response structuur

2. ✅ **Specifieke persoon ophalen op BSN**
   - GET `/ingeschrevenpersonen/{bsn}`
   - Valideert BSN, naam, links

3. ✅ **Persoon niet gevonden**
   - GET `/ingeschrevenpersonen/999999999`
   - Valideert 404 response

4. ✅ **Ongeldig BSN formaat**
   - GET `/ingeschrevenpersonen/12345`
   - Valideert 400 response

5. ✅ **Field selection**
   - GET `/ingeschrevenpersonen/{bsn}?fields=burgerservicenummer,naam`
   - Valideert dat alleen opgegeven velden terugkomen

6. ✅ **Expand - partners automatisch ophalen**
   - GET `/ingeschrevenpersonen/{bsn}?expand=partners`
   - Valideert dat partners in _embedded zitten

7. ✅ **Filter op achternaam**
   - GET `/ingeschrevenpersonen?achternaam=Jansen`
   - Valideert filtering

8. ✅ **Filter op geboortedatum range**
   - GET `/ingeschrevenpersonen?geboortedatumVan=2000-01-01&geboortedatumTot=2010-12-31`
   - Valideert datum range filtering

9. ✅ **Sorteren op achternaam**
   - GET `/ingeschrevenpersonen?sort=-naam.geslachtsnaam`
   - Valideert sortering

10. ✅ **Paginatie**
    - GET `/ingeschrevenpersonen?_limit=10&_page=1`
    - Valideert paginatie structuur

---

## Rapportage

### JSON Rapport

Tests genereren automatisch een JSON rapport:
```
test-results/cucumber/cucumber_YYYYMMDD_HHMMSS.json
```

### HTML Rapport Genereren

```bash
./generate-cucumber-report.sh
```

Dit genereert een HTML rapport:
```
test-results/cucumber/index.html
```

### Rapport Inhoud

- ✅ Samenvatting (totaal, passed, failed, skipped)
- ✅ Per scenario: status, steps, errors
- ✅ Error messages voor gefaalde tests

---

## Test Data

### Standaard Test BSN

- **Test BSN:** `168149291` (moet bestaan in database)
- **Niet-bestaand BSN:** `999999999` (voor 404 tests)

### Aanpassen Test Data

Pas aan in `features/ingeschrevenpersonen.feature`:

```gherkin
Given er bestaat een persoon met BSN "JOUW_BSN_HIER"
```

---

## Troubleshooting

### Ruby niet geïnstalleerd

**macOS:**
```bash
brew install ruby
```

**Linux:**
```bash
sudo apt-get install ruby ruby-dev
```

### Bundler niet geïnstalleerd

```bash
gem install bundler
```

### API niet bereikbaar

1. Check of Nextcloud draait:
   ```bash
   docker ps | grep nextcloud
   ```

2. Check API URL:
   ```bash
   curl http://localhost:8080/apps/openregister/ingeschrevenpersonen
   ```

3. Pas API URL aan:
   ```bash
   export API_URL=http://jouw-url/apps/openregister
   ```

### Tests falen

1. Check response in rapport
2. Check API logs
3. Valideer test data (BSN moet bestaan)
4. Check API authenticatie (indien vereist)

---

## CI/CD Integratie

### GitHub Actions

```yaml
name: Haal Centraal Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: ruby/setup-ruby@v1
        with:
          ruby-version: 3.0
      - run: bundle install
      - run: bundle exec cucumber
        env:
          API_URL: ${{ secrets.API_URL }}
```

---

## Uitbreiden Tests

### Nieuwe Feature Toevoegen

1. Maak nieuw `.feature` bestand in `features/`
2. Schrijf scenarios in Gherkin syntax
3. Implementeer step definitions in `features/step_definitions/`

### Voorbeeld

```gherkin
Feature: Partners Endpoint
  Scenario: Partners ophalen
    Given er bestaat een persoon met BSN "168149291"
    When ik een GET request doe naar "/ingeschrevenpersonen/168149291/partners"
    Then de response status code is 200
    And de response bevat "_embedded"
    And "_embedded" bevat "partners"
```

---

## Compliance Score

Na het draaien van de tests:

- ✅ **100% Passed:** Volledig compliant
- ⚠️ **< 100% Passed:** Bekijk gefaalde tests en pas API aan

---

## Conclusie

**Status:** ✅ Test suite setup voltooid

**Volgende Stappen:**
1. Run tests: `./run-haal-centraal-tests.sh`
2. Bekijk rapport: `./generate-cucumber-report.sh`
3. Fix gefaalde tests indien nodig
4. Integreer in CI/CD pipeline

---

**Rapport gegenereerd op:** 2025-01-27  
**Status:** ✅ Klaar voor gebruik







