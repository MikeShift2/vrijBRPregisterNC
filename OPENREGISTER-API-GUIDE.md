# OpenRegister API Guide - Personen Schema

## Overzicht

OpenRegister biedt een REST API voor alle objecten in je registers. Deze guide beschrijft hoe je de API kunt gebruiken voor het "Personen" schema.

## Basis URL

```
http://localhost:8080/apps/openregister/api
```

## Authenticatie

De API gebruikt Nextcloud Basic Authentication:
- **Gebruikersnaam:** `admin` (of je Nextcloud gebruiker)
- **Wachtwoord:** Je Nextcloud wachtwoord

## API Endpoints voor Personen

### 1. Lijst alle personen ophalen

**GET** `/api/objects/{register}/{schema}`

**Voorbeeld:**
```bash
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/api/objects/2/6?_limit=10&_page=1"
```

**Parameters:**
- `register`: Register ID (2 = vrijBRPpersonen)
- `schema`: Schema ID (6 = Personen)
- `_limit`: Aantal resultaten per pagina (optioneel, default: 20)
- `_page`: Paginanummer (optioneel, default: 1)

**Response:**
```json
{
  "data": [
    {
      "@self": {
        "uuid": "5201cff6-755e-4919-a0cd-e1f695207916",
        "register": 2,
        "schema": 6,
        "created": "2025-11-26T17:00:00+00:00",
        "updated": "2025-11-26T17:00:00+00:00"
      },
      "id": "23",
      "bsn": "168149291",
      "voornamen": "Janne Malu Roelien Olive Tanneke",
      "geslachtsnaam": "Naiima Isman Adan",
      "geboortedatum": "19820308",
      "geslacht": "V",
      "anr": "101.8943.639"
    }
  ],
  "pagination": {
    "total": 100,
    "page": 1,
    "limit": 10
  }
}
```

### 2. Specifieke persoon ophalen

**GET** `/api/objects/{register}/{schema}/{uuid}`

**Voorbeeld:**
```bash
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/api/objects/2/6/5201cff6-755e-4919-a0cd-e1f695207916"
```

### 3. Nieuwe persoon aanmaken

**POST** `/api/objects/{register}/{schema}`

**Voorbeeld:**
```bash
curl -X POST -u admin:jouw_wachtwoord \
  -H "Content-Type: application/json" \
  -d '{
    "id": "999",
    "bsn": "123456789",
    "voornamen": "Jan",
    "geslachtsnaam": "Jansen",
    "geboortedatum": "19900101",
    "geslacht": "M",
    "anr": "999.9999.999"
  }' \
  "http://localhost:8080/apps/openregister/api/objects/2/6"
```

### 4. Persoon bijwerken

**PUT** `/api/objects/{register}/{schema}/{uuid}`

**Voorbeeld:**
```bash
curl -X PUT -u admin:jouw_wachtwoord \
  -H "Content-Type: application/json" \
  -d '{
    "id": "999",
    "bsn": "123456789",
    "voornamen": "Jan",
    "geslachtsnaam": "Jansen",
    "geboortedatum": "19900101",
    "geslacht": "M",
    "anr": "999.9999.999"
  }' \
  "http://localhost:8080/apps/openregister/api/objects/2/6/5201cff6-755e-4919-a0cd-e1f695207916"
```

### 5. Persoon verwijderen

**DELETE** `/api/objects/{register}/{schema}/{uuid}`

**Voorbeeld:**
```bash
curl -X DELETE -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/api/objects/2/6/5201cff6-755e-4919-a0cd-e1f695207916"
```

### 6. Zoeken in personen

**GET** `/api/objects/{register}/{schema}?search={query}`

**Voorbeeld:**
```bash
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/api/objects/2/6?search=Janne"
```

## OpenAPI Specificatie

OpenRegister genereert automatisch een OpenAPI (Swagger) specificatie:

**GET** `/apps/openregister/api/oas`

Dit geeft een volledige OpenAPI 3.0 specificatie met alle beschikbare endpoints.

## Schema Informatie

**GET** `/api/schemas/{schema_id}`

**Voorbeeld:**
```bash
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/api/schemas/6"
```

## Python Voorbeeld

```python
import requests
from requests.auth import HTTPBasicAuth

BASE_URL = "http://localhost:8080/apps/openregister/api"
REGISTER_ID = 2
SCHEMA_ID = 6
USERNAME = "admin"
PASSWORD = "jouw_wachtwoord"

auth = HTTPBasicAuth(USERNAME, PASSWORD)

# Lijst alle personen
response = requests.get(
    f"{BASE_URL}/objects/{REGISTER_ID}/{SCHEMA_ID}",
    auth=auth,
    params={"_limit": 10}
)
personen = response.json()["data"]

# Specifieke persoon ophalen
uuid = personen[0]["@self"]["uuid"]
response = requests.get(
    f"{BASE_URL}/objects/{REGISTER_ID}/{SCHEMA_ID}/{uuid}",
    auth=auth
)
persoon = response.json()

# Nieuwe persoon aanmaken
nieuwe_persoon = {
    "id": "999",
    "bsn": "123456789",
    "voornamen": "Jan",
    "geslachtsnaam": "Jansen",
    "geboortedatum": "19900101",
    "geslacht": "M",
    "anr": "999.9999.999"
}
response = requests.post(
    f"{BASE_URL}/objects/{REGISTER_ID}/{SCHEMA_ID}",
    auth=auth,
    json=nieuwe_persoon
)
```

## JavaScript/TypeScript Voorbeeld

```typescript
const BASE_URL = "http://localhost:8080/apps/openregister/api";
const REGISTER_ID = 2;
const SCHEMA_ID = 6;
const USERNAME = "admin";
const PASSWORD = "jouw_wachtwoord";

// Basic auth header
const authHeader = `Basic ${btoa(`${USERNAME}:${PASSWORD}`)}`;

// Lijst alle personen
async function getPersonen(limit = 20, page = 1) {
  const response = await fetch(
    `${BASE_URL}/objects/${REGISTER_ID}/${SCHEMA_ID}?_limit=${limit}&_page=${page}`,
    {
      headers: {
        Authorization: authHeader,
      },
    }
  );
  return await response.json();
}

// Specifieke persoon ophalen
async function getPersoon(uuid: string) {
  const response = await fetch(
    `${BASE_URL}/objects/${REGISTER_ID}/${SCHEMA_ID}/${uuid}`,
    {
      headers: {
        Authorization: authHeader,
      },
    }
  );
  return await response.json();
}

// Nieuwe persoon aanmaken
async function createPersoon(persoon: any) {
  const response = await fetch(
    `${BASE_URL}/objects/${REGISTER_ID}/${SCHEMA_ID}`,
    {
      method: "POST",
      headers: {
        Authorization: authHeader,
        "Content-Type": "application/json",
      },
      body: JSON.stringify(persoon),
    }
  );
  return await response.json();
}
```

## Belangrijke Notities

1. **Validatie:** Alle objecten worden automatisch gevalideerd tegen het schema voordat ze worden opgeslagen ✅
2. **Required velden:** Het Personen schema heeft geen required velden, alle velden zijn optioneel
3. **UUID:** Elke persoon krijgt automatisch een UUID toegewezen bij aanmaken
4. **Versiebeheer:** OpenRegister houdt versiegeschiedenis bij van alle wijzigingen
5. **Permissions:** Zorg dat je gebruiker de juiste rechten heeft in Nextcloud
6. **Data Source:** Gebruik `source=database` parameter om direct uit de database te lezen (SOLR is optioneel)

## Data Source Parameter

OpenRegister kan data ophalen uit verschillende bronnen:
- `source=database` - Direct uit de MariaDB database (aanbevolen voor nu)
- `source=auto` - Automatisch de beste bron kiezen
- `source=solr` - Uit SOLR index (vereist SOLR configuratie)

**Voorbeeld met database source:**
```bash
curl -u admin:jouw_wachtwoord \
  "http://localhost:8080/apps/openregister/api/objects/2/6?source=database&_limit=10"
```

## Volgende Stappen

1. Test de API endpoints met curl of Postman
2. Bekijk de OpenAPI specificatie voorbe `/api/oas`
3. Bouw je frontend of integratie tegen deze API
4. Overweeg API keys of OAuth voor productie gebruik

