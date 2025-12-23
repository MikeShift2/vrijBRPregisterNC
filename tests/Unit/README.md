# Unit Tests voor Haal Centraal BRP Historie API Validator

## Overzicht

Deze unit tests valideren de `HaalCentraalHistorieValidator` service die responses valideert tegen de Haal Centraal BRP Historie API 2.0 specificatie.

## Test Bestand

- `Service/HaalCentraalHistorieValidatorTest.php` - Unit tests voor de validator

## Test Cases

### Valide Responses
- ✅ Valide response met één verblijfplaats
- ✅ Valide response met meerdere verblijfplaatsen
- ✅ Valide response met lege historie array
- ✅ Postcode met spatie (moet ook werken)
- ✅ Huisnummer als string (moet werken)
- ✅ Verblijfplaats zonder woonplaats maar met andere velden
- ✅ Alle datum velden aanwezig

### Invalide Responses
- ❌ Response zonder _embedded
- ❌ Response zonder verblijfplaatshistorie
- ❌ Verblijfplaats zonder adresvelden
- ❌ Ongeldig postcode formaat
- ❌ Ongeldig datum formaat
- ❌ Ongeldige datum (niet-bestaande datum)
- ❌ Datum te ver in de toekomst
- ❌ Lege string voor straatnaam
- ❌ Verkeerd type voor huisnummer
- ❌ Verkeerd type voor huisnummertoevoeging
- ❌ Ongeldige _links structuur
- ❌ Lege href in _links
- ❌ Datum object zonder datum veld
- ❌ Verblijfplaatshistorie is geen array

## PHPUnit Installatie

Voor Nextcloud apps wordt PHPUnit meestal via Composer geïnstalleerd:

```bash
cd /path/to/openregister/app
composer require --dev phpunit/phpunit
```

## Tests Draaien

### Via Composer (aanbevolen)
```bash
cd /path/to/openregister/app
composer test
```

### Direct via PHPUnit
```bash
cd /path/to/openregister/app
./vendor/bin/phpunit tests/Unit/Service/HaalCentraalHistorieValidatorTest.php
```

### Met coverage
```bash
./vendor/bin/phpunit --coverage-html coverage tests/Unit/Service/HaalCentraalHistorieValidatorTest.php
```

## PHPUnit Configuratie

Maak een `phpunit.xml` bestand in de app root:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheResultFile=".phpunit.cache/test-results"
         executionOrder="depends,defects"
         forceCoversAnnotation="false"
         beStrictAboutCoversAnnotation="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutTodoAnnotatedTests="true"
         convertDeprecationsToExceptions="true"
         failOnRisky="true"
         failOnWarning="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">lib</directory>
        </include>
        <exclude>
            <directory>lib/Controller</directory>
        </exclude>
    </coverage>
</phpunit>
```

## Test Resultaten

Na het draaien van de tests krijg je output zoals:

```
PHPUnit 9.5.27 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.1.0
Configuration: /path/to/phpunit.xml

....................                                20 / 20 (100%)

Time: 00:00.123, Memory: 8.00 MB

OK (20 tests, 40 assertions)
```

## Continuous Integration

Voor CI/CD pipelines kun je de tests automatisch draaien:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: composer test
```

## Troubleshooting

### Fout: Class not found
Zorg dat de autoloader correct is geconfigureerd:
```bash
composer dump-autoload
```

### Fout: TestCase not found
Zorg dat PHPUnit is geïnstalleerd:
```bash
composer require --dev phpunit/phpunit
```

### Fout: Namespace issues
Controleer dat de namespace in de test file overeenkomt met de directory structuur:
- `tests/Unit/Service/` → `namespace OCA\OpenRegister\Tests\Unit\Service;`







