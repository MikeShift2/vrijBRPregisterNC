# Scroll Fix en Relaties Zichtbaarheid

## Probleem
De gebruiker kan de hele pagina niet lezen, waardoor relaties mogelijk niet zichtbaar zijn.

## Oplossingen

### 1. Results Wrapper Scroll Fix
- Verwijderd `max-height` beperking op `.results-wrapper`
- `overflow-y: visible` toegevoegd zodat content volledig zichtbaar is
- `min-height: auto` toegevoegd

### 2. Relaties Container Verbetering
- Border toegevoegd boven relaties container voor betere visuele scheiding
- Padding toegevoegd voor betere leesbaarheid

### 3. Relatie Secties Visueel Verbeterd
- Elke relatie sectie heeft nu:
  - Eigen achtergrondkleur (`var(--bg-secondary)`)
  - Border en border-radius voor duidelijkheid
  - Grotere titels (18px) met accent kleur
  - Meer padding voor leesbaarheid

## Test Instructies

1. **Open test pagina:** `http://localhost:8080/apps/openregister/haal-centraal-test`
2. **Zoek op BSN:** `168149291` (heeft relaties)
3. **Scroll naar beneden** in de results sectie
4. **Controleer of relaties zichtbaar zijn:**
   - Partners sectie (blauwe titel)
   - Kinderen sectie (blauwe titel)
   - Ouders sectie (blauwe titel)
   - Nationaliteiten sectie (blauwe titel)

## Verwachte Resultaten

Voor BSN 168149291 zou je moeten zien:
- **05. Partners (1)** - Partner met BSN 164287061
- **09. Kinderen (1)** - Kind met BSN 382651765
- **09. Ouders (2)** - Ouders met BSN 73218832 en 73218327
- **04. Nationaliteiten (1)** - Nederlandse (code: 1)

## CSS Wijzigingen

```css
.results-wrapper {
    max-height: none !important;
    overflow-y: visible !important;
    min-height: auto;
}
```

Elke relatie sectie heeft nu:
- Duidelijke visuele scheiding
- Grotere, gekleurde titels
- Betere padding en spacing







