# Debug: Relaties Testscherm

## Status
✅ **Endpoints werken perfect via curl!**

### Testresultaten voor BSN 168149291:

#### Partners endpoint:
```bash
curl -u admin:admin_secure_pass_2024 "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/partners"
```
**Resultaat:** ✅ 1 partner gevonden (BSN: 164287061)

#### Kinderen endpoint:
```bash
curl -u admin:admin_secure_pass_2024 "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/kinderen"
```
**Resultaat:** ✅ 1 kind gevonden (BSN: 382651765)

#### Ouders endpoint:
```bash
curl -u admin:admin_secure_pass_2024 "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/ouders"
```
**Resultaat:** ✅ 2 ouders gevonden (BSN: 73218832 en 73218327)

#### Nationaliteiten endpoint:
```bash
curl -u admin:admin_secure_pass_2024 "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291/nationaliteiten"
```
**Resultaat:** ✅ 1 nationaliteit gevonden (Nederlandse, code: 1)

## Probleem
Het testscherm toont "Geen relaties gevonden" terwijl de endpoints wel werken.

## Oplossing
Console logging toegevoegd aan `loadRelaties()` functie in `haalcentraaltest.php`:

1. **Logging bij start:**
   - BSN controle
   - Container element controle
   - baseUrl en ggmParam logging

2. **Logging per endpoint:**
   - Response status voor elk endpoint (partners, kinderen, ouders, verblijfplaats, nationaliteiten)
   - Error logging bij fetch failures

3. **Logging bij resultaten:**
   - Aantal gevonden relaties per type
   - HTML lengte
   - Success/error messages

## Test instructies
1. Open browser console (F12)
2. Zoek op BSN 168149291
3. Bekijk console logs voor:
   - `loadRelaties: Start ophalen relaties voor BSN: 168149291`
   - `loadRelaties: baseUrl: /apps/openregister/ingeschrevenpersonen/168149291`
   - Response status voor elk endpoint
   - Aantal gevonden relaties

## Mogelijke oorzaken
1. **CORS/Authenticatie:** Browser gebruikt cookies, curl gebruikt basic auth
2. **JavaScript errors:** Check console voor errors
3. **Response format:** Mogelijk verschil tussen curl response en browser response
4. **Timing issue:** Relaties worden opgehaald voordat container element bestaat

## Volgende stappen
1. Test in browser met console open
2. Controleer Network tab voor HTTP responses
3. Controleer of endpoints worden aangeroepen
4. Controleer response status codes







