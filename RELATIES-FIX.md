# Fix: Relaties worden niet getoond

## Probleem
Relatie-informatie (partners, kinderen, ouders, nationaliteiten) wordt niet getoond in het testscherm, terwijl de endpoints wel werken.

## Oorzaak
Timing probleem: `loadRelaties()` wordt aangeroepen voordat de DOM is geüpdatet met de `relaties-container` div.

## Oplossing
1. **setTimeout toegevoegd** om ervoor te zorgen dat de DOM eerst wordt geüpdatet voordat `loadRelaties()` wordt aangeroepen
2. **Retry mechanisme** toegevoegd in `loadRelaties()` om te wachten tot de container bestaat

## Wijzigingen

### 1. setTimeout in displayPerson()
```javascript
// Haal relaties op als BSN beschikbaar is
// Gebruik setTimeout om ervoor te zorgen dat de DOM is geüpdatet
if (person.burgerservicenummer) {
    setTimeout(function() {
        console.log('Aanroepen loadRelaties voor BSN:', person.burgerservicenummer);
        loadRelaties(person.burgerservicenummer);
    }, 100);
}
```

### 2. Retry mechanisme in loadRelaties()
```javascript
var relatiesContainer = document.getElementById('relaties-container');
if (!relatiesContainer) {
    console.log('loadRelaties: relaties-container niet gevonden, retry over 200ms...');
    setTimeout(function() {
        loadRelaties(bsn);
    }, 200);
    return;
}
```

## Test
1. Open browser console (F12)
2. Zoek op BSN 168149291
3. Controleer console logs:
   - `Aanroepen loadRelaties voor BSN: 168149291`
   - `loadRelaties: Start ophalen relaties voor BSN: 168149291`
   - `loadRelaties: Container gevonden: true`
   - Response status voor elk endpoint
   - Aantal gevonden relaties

## Verwachte resultaten
Na deze fix zouden de relaties moeten verschijnen:
- Partners: 1 partner
- Kinderen: 1 kind
- Ouders: 2 ouders
- Nationaliteiten: 1 nationaliteit

## Opmerking
De verblijfplaats wordt wel getoond omdat deze in het hoofdobject zit (regel 1020-1048), niet via een apart endpoint.







