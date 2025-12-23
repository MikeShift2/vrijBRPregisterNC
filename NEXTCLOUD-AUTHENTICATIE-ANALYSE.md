# Nextcloud Authenticatie Analyse - Wat Zit Er Al In?

## Huidige Situatie

### ✅ Wat Nextcloud Al Biedt

#### 1. Basic Authentication (HTTP Basic Auth)
**Status:** ✅ **Werkt al**

**Hoe het werkt:**
- Nextcloud ondersteunt standaard HTTP Basic Authentication
- Gebruikersnaam + wachtwoord via `Authorization: Basic` header
- Werkt met alle Nextcloud endpoints

**Voorbeeld:**
```bash
curl -u admin:password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
```

**Voordelen:**
- ✅ Werkt out-of-the-box
- ✅ Geen extra implementatie nodig
- ✅ Standaard HTTP authenticatie

**Nadelen:**
- ⚠️ Niet Haal Centraal-compliant (vereist Bearer token)
- ⚠️ Gebruikers moeten hoofdwachtwoord gebruiken
- ⚠️ Minder veilig voor externe systemen

---

#### 2. Nextcloud App Passwords
**Status:** ✅ **Beschikbaar in Nextcloud**

**Hoe het werkt:**
- Nextcloud heeft ingebouwde "App Passwords" functionaliteit
- Gebruikers kunnen specifieke wachtwoorden genereren voor externe apps
- Deze kunnen worden gebruikt voor API-toegang zonder hoofdwachtwoord

**Hoe te gebruiken:**
1. Ga naar Nextcloud instellingen → Security → Devices & sessions
2. Genereer een App Password
3. Gebruik dit wachtwoord voor API-toegang

**Voorbeeld:**
```bash
# Gebruik App Password in plaats van hoofdwachtwoord
curl -u admin:app_password_here \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
```

**Voordelen:**
- ✅ Veiliger dan hoofdwachtwoord
- ✅ Kan per app worden beheerd
- ✅ Kan worden ingetrokken zonder hoofdwachtwoord te wijzigen
- ✅ Werkt met Basic Auth (geen extra code nodig)

**Nadelen:**
- ⚠️ Nog steeds Basic Auth (niet Bearer token)
- ⚠️ Niet Haal Centraal-compliant
- ⚠️ Vereist Nextcloud gebruiker account

---

#### 3. Nextcloud OAuth2 (indien geïnstalleerd)
**Status:** ⚠️ **Mogelijk beschikbaar**

**Hoe het werkt:**
- Nextcloud heeft OAuth2 support via apps
- Kan worden gebruikt voor externe authenticatie
- Vereist OAuth2 app installatie

**Voordelen:**
- ✅ OAuth2 standaard
- ✅ Bearer token support mogelijk
- ✅ Geschikt voor externe systemen

**Nadelen:**
- ⚠️ Vereist extra app installatie
- ⚠️ Complexere setup
- ⚠️ Mogelijk niet Haal Centraal-compliant

---

## Wat Haal Centraal Vereist

### Haal Centraal BRP Bevragen Specificatie

**Authenticatie Methode:**
- Bearer Token (JWT) via `Authorization: Bearer {token}` header
- OAuth2 Client Credentials Flow
- API keys voor externe systemen

**Voorbeeld:**
```http
Authorization: Bearer eyJhbGciOiJSUzI1NiJ9...
```

**Verschil met Nextcloud:**
- Haal Centraal vereist **Bearer token** (niet Basic Auth)
- Haal Centraal vereist **JWT** (niet App Password)
- Haal Centraal vereist **OAuth2 flow** (niet direct wachtwoord)

---

## Conclusie: Wat Moet Er Nog Gebeuren?

### Optie 1: Nextcloud App Passwords Gebruiken (Quick Fix)

**Wat werkt:**
- ✅ Nextcloud App Passwords kunnen worden gebruikt voor externe toegang
- ✅ Veiliger dan hoofdwachtwoord
- ✅ Geen extra code nodig

**Wat ontbreekt:**
- ❌ Nog steeds Basic Auth (niet Bearer token)
- ❌ Niet Haal Centraal-compliant
- ❌ Vereist Nextcloud gebruiker account

**Geschiktheid:**
- ✅ **Voor interne systemen:** Goed genoeg
- ❌ **Voor externe systemen:** Niet Haal Centraal-compliant
- ❌ **Voor PoC met gemeente Utrecht:** Mogelijk niet acceptabel

---

### Optie 2: JWT/Bearer Token Implementeren (Volledige Compliance)

**Wat moet worden gebouwd:**
- ✅ JWT token generatie
- ✅ Bearer token validatie
- ✅ OAuth2 Client Credentials Flow
- ✅ API key management systeem

**Geschiktheid:**
- ✅ **Voor externe systemen:** Volledig Haal Centraal-compliant
- ✅ **Voor PoC met gemeente Utrecht:** Vereist voor compliance
- ✅ **Voor productie:** Vereist voor standaard compliance

**Tijd:** 2-3 weken ontwikkeling

---

## Aanbeveling

### Korte Termijn (Quick Win)

**Gebruik Nextcloud App Passwords:**
- ✅ Veiliger dan hoofdwachtwoord
- ✅ Werkt direct zonder code wijzigingen
- ✅ Geschikt voor interne systemen en testen

**Implementatie:**
1. Maak App Password aan in Nextcloud
2. Gebruik App Password voor API-toegang
3. Documenteer gebruik voor externe systemen

**Voorbeeld:**
```bash
# Genereer App Password in Nextcloud UI
# Gebruik voor API-toegang
curl -u admin:app_password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
```

---

### Middellange Termijn (Voor PoC)

**Implementeer JWT/Bearer Token:**
- ✅ Volledig Haal Centraal-compliant
- ✅ Geschikt voor externe systemen
- ✅ Vereist voor PoC met gemeente Utrecht

**Implementatie:**
1. Installeer JWT library (`firebase/php-jwt`)
2. Implementeer token generatie endpoint
3. Implementeer Bearer token validatie
4. Voeg API key management toe

**Tijd:** 2-3 weken

---

## Praktische Oplossing

### Voor Nu (Testen & Interne Systemen)

**Gebruik Nextcloud App Passwords:**
- ✅ Werkt direct
- ✅ Veiliger dan hoofdwachtwoord
- ✅ Geen code wijzigingen nodig

**Stappen:**
1. Ga naar Nextcloud → Instellingen → Security
2. Genereer App Password
3. Gebruik voor API-toegang

**Voorbeeld code:**
```bash
# Test met App Password
curl -u admin:your_app_password \
  "http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291"
```

---

### Voor PoC (Gemeente Utrecht)

**Implementeer JWT/Bearer Token:**
- ✅ Vereist voor Haal Centraal compliance
- ✅ Geschikt voor externe systemen
- ✅ Standaard voor overheids-API's

**Stappen:**
1. Volg `HAAL-CENTRAAL-API-COMPLETIE-PLAN.md`
2. Implementeer JWT authenticatie
3. Test tegen Haal Centraal specificatie

---

## Samenvatting

| Authenticatie Methode | Status | Geschikt Voor | Haal Centraal Compliant |
|----------------------|--------|---------------|-------------------------|
| **Nextcloud Basic Auth** | ✅ Werkt | Interne testen | ❌ Nee |
| **Nextcloud App Passwords** | ✅ Beschikbaar | Interne systemen | ❌ Nee |
| **JWT/Bearer Token** | ❌ Niet geïmplementeerd | Externe systemen | ✅ Ja |

**Conclusie:**
- ✅ **Voor nu:** Gebruik Nextcloud App Passwords (werkt direct)
- ✅ **Voor PoC:** Implementeer JWT/Bearer Token (vereist voor compliance)

**Tijd besparing:**
- Nextcloud App Passwords: 0 weken (werkt nu)
- JWT/Bearer Token: 2-3 weken (vereist voor PoC)

---

**Document versie:** 1.0  
**Laatste update:** 2025-01-27  
**Status:** Analyse compleet







