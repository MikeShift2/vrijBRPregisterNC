# Tabs Zichtbaarheid Fix

## Probleem
De tabs (04. Nationaliteiten, 05. Partners, etc.) zijn niet zichtbaar in de interface.

## Oplossing

### CSS Wijzigingen

1. **details-categories container:**
   - `display: flex` en `flex-direction: column` toegevoegd
   - `gap: 4px` voor spacing tussen tabs
   - `overflow-x: visible` toegevoegd

2. **category-item (tabs):**
   - `margin-bottom: 0` (wordt nu geregeld door gap)
   - `display: block` en `width: 100%` toegevoegd
   - `white-space: nowrap` om tekst niet te laten wrappen

## Verwachte Resultaten

Na deze fix zouden alle tabs zichtbaar moeten zijn:
- ✅ 01. Persoon (actief, blauw)
- ✅ 04. Nationaliteiten
- ✅ 05. Partners
- ✅ 08. Verblijfplaats
- ✅ 09. Kinderen
- ✅ 09. Ouders

## Test

1. Ververs de pagina (Ctrl+F5)
2. Zoek op BSN: 168149291
3. Controleer of alle 6 tabs zichtbaar zijn in de linker kolom







