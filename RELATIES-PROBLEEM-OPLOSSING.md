# Relaties Probleem - Oplossing

## Probleem
Het testscherm toont "Geen relaties gevonden" terwijl er wel relaties zijn in de database voor BSN 168149291:
- 1 partner (BSN: 164287061)
- 1 kind
- 2 ouders
- 1 nationaliteit

## Oorzaak
Nextcloud heeft de nieuwe endpoints nog niet geladen door caching. De methodes staan wel in het bestand, maar Nextcloud ziet ze nog niet.

## Oplossing Uitgevoerd

1. ✅ **Endpoints geïmplementeerd** in `HaalCentraalBrpController.php`:
   - `getPartners()`
   - `getKinderen()`
   - `getOuders()`
   - `getVerblijfplaats()`
   - `getNationaliteiten()`

2. ✅ **Routes geregistreerd** in `routes.php`

3. ✅ **JavaScript code toegevoegd** in `haalcentraaltest.php`:
   - `loadRelaties()` functie
   - Automatisch ophalen van relaties wanneer persoon wordt getoond

4. ✅ **Cache geleegd**:
   - OPcache gereset
   - App cache geleegd
   - App opnieuw geladen

## Testen

1. **Hard refresh** de pagina in je browser: `Cmd+Shift+R` (Mac) of `Ctrl+Shift+R` (Windows)
2. Zoek op BSN: `168149291`
3. Open de browser console (F12) en kijk naar:
   - Network tab: zie je requests naar `/partners`, `/kinderen`, etc.?
   - Console tab: zijn er JavaScript errors?
   - Krijg je 404 of 500 errors?

## Als het nog steeds niet werkt

Als je nog steeds "Geen relaties gevonden" ziet:

1. **Check browser console** voor errors
2. **Check Network tab** - worden de endpoints aangeroepen?
3. **Check Nextcloud logs**: 
   ```bash
   docker exec nextcloud tail -20 /var/www/html/data/nextcloud.log | grep -i "partners\|kinderen\|error"
   ```

4. **Test endpoints direct**:
   ```bash
   # In browser console (F12):
   fetch('/apps/openregister/ingeschrevenpersonen/168149291/partners', {
     credentials: 'include'
   }).then(r => r.json()).then(console.log)
   ```

## Status
- ✅ Code geïmplementeerd
- ✅ Routes geregistreerd  
- ✅ Cache geleegd
- ⏳ Wachten op browser test

Test nu opnieuw in je browser met een hard refresh!







