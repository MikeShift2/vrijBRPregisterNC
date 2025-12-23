Feature: Haal Centraal BRP Bevragen API - Ingeschreven Personen
  Als een API consumer
  Wil ik ingeschreven personen kunnen bevragen
  Zodat ik BRP gegevens kan ophalen

  Background:
    Given de API is beschikbaar op "http://localhost:8080/apps/openregister"

  Scenario: Lijst ingeschreven personen ophalen
    When ik een GET request doe naar "/ingeschrevenpersonen"
    Then de response status code is 200
    And de response bevat "_embedded"
    And de response bevat "ingeschrevenpersonen"
    And "ingeschrevenpersonen" is een array

  Scenario: Specifieke persoon ophalen op BSN
    Given er bestaat een persoon met BSN "168149291"
    When ik een GET request doe naar "/ingeschrevenpersonen/168149291"
    Then de response status code is 200
    And de response bevat "burgerservicenummer"
    And de waarde van "burgerservicenummer" is "168149291"
    And de response bevat "naam"
    And de response bevat "_links"

  Scenario: Persoon niet gevonden
    When ik een GET request doe naar "/ingeschrevenpersonen/999999999"
    Then de response status code is 404
    And de response bevat "status"
    And de waarde van "status" is 404
    And de response bevat "title"
    And de response bevat "detail"

  Scenario: Ongeldig BSN formaat
    When ik een GET request doe naar "/ingeschrevenpersonen/12345"
    Then de response status code is 400
    And de response bevat "status"
    And de waarde van "status" is 400
    And de response bevat "detail"

  Scenario: Field selection - alleen BSN en naam
    Given er bestaat een persoon met BSN "168149291"
    When ik een GET request doe naar "/ingeschrevenpersonen/168149291?fields=burgerservicenummer,naam"
    Then de response status code is 200
    And de response bevat "burgerservicenummer"
    And de response bevat "naam"
    And de response bevat niet "geboorte"

  Scenario: Expand - partners automatisch ophalen
    Given er bestaat een persoon met BSN "168149291"
    When ik een GET request doe naar "/ingeschrevenpersonen/168149291?expand=partners"
    Then de response status code is 200
    And de response bevat "_embedded"
    And "_embedded" bevat "partners"

  Scenario: Filter op achternaam
    When ik een GET request doe naar "/ingeschrevenpersonen?achternaam=Jansen"
    Then de response status code is 200
    And de response bevat "_embedded"
    And de response bevat "ingeschrevenpersonen"

  Scenario: Filter op geboortedatum range
    When ik een GET request doe naar "/ingeschrevenpersonen?geboortedatumVan=2000-01-01&geboortedatumTot=2010-12-31"
    Then de response status code is 200
    And de response bevat "_embedded"
    And de response bevat "ingeschrevenpersonen"

  Scenario: Sorteren op achternaam
    When ik een GET request doe naar "/ingeschrevenpersonen?sort=-naam.geslachtsnaam"
    Then de response status code is 200
    And de response bevat "_embedded"
    And de response bevat "ingeschrevenpersonen"

  Scenario: Paginatie
    When ik een GET request doe naar "/ingeschrevenpersonen?_limit=10&_page=1"
    Then de response status code is 200
    And de response bevat "_links"
    And de response bevat "page"
    And "page" bevat "number"
    And "page" bevat "size"
    And "page" bevat "totalElements"







