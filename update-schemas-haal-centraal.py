#!/usr/bin/env python3
"""
Script om Open Register schemas bij te werken naar Haal Centraal-specificatie

Dit script:
1. Analyseert de probev-database structuur
2. Maakt mapping tussen probev en Haal Centraal-velden
3. Creëert SQL views voor denormalisatie
4. Update Open Register Personen schema (ID 6) met Haal Centraal-compliant properties
"""

import subprocess
import json
import sys
from typing import Dict, Any

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

# PostgreSQL configuratie voor probev
PG_HOST = "host.docker.internal"
PG_PORT = "5432"
PG_DB = "bevax"
PG_USER = "postgres"
PG_PASS = ""  # Leeg als geen wachtwoord

# Schema ID's
SCHEMA_ID_PERSONEN = 6  # Personen (vrijBRP)
SCHEMA_ID_GGM = 21      # GGM IngeschrevenPersoon

# Haal Centraal BRP Bevragen API - IngeschrevenPersoon properties
# Gebaseerd op Haal Centraal-specificatie
HAAL_CENTRAAL_PROPERTIES = {
    "burgerservicenummer": {
        "type": "string",
        "description": "Het burgerservicenummer (BSN) van de persoon"
    },
    "naam": {
        "type": "object",
        "description": "Naamgegevens van de persoon",
        "properties": {
            "voornamen": {
                "type": "array",
                "items": {"type": "string"},
                "description": "Voornamen van de persoon"
            },
            "geslachtsnaam": {
                "type": "string",
                "description": "Geslachtsnaam (achternaam) van de persoon"
            },
            "voorvoegsel": {
                "type": "string",
                "description": "Voorvoegsel van de geslachtsnaam"
            }
        }
    },
    "geboorte": {
        "type": "object",
        "description": "Geboortegegevens",
        "properties": {
            "datum": {
                "type": "object",
                "properties": {
                    "datum": {
                        "type": "string",
                        "format": "date",
                        "description": "Geboortedatum in ISO 8601 formaat (YYYY-MM-DD)"
                    }
                }
            },
            "plaats": {
                "type": "string",
                "description": "Plaats van geboorte"
            },
            "land": {
                "type": "object",
                "properties": {
                    "code": {
                        "type": "string",
                        "description": "Landcode (ISO 3166-1 alpha-2)"
                    },
                    "omschrijving": {
                        "type": "string",
                        "description": "Landomschrijving"
                    }
                }
            }
        }
    },
    "geslachtsaanduiding": {
        "type": "string",
        "enum": ["man", "vrouw", "onbekend"],
        "description": "Geslachtsaanduiding"
    },
    "verblijfplaats": {
        "type": "object",
        "description": "Verblijfplaats (adres)",
        "properties": {
            "straatnaam": {"type": "string"},
            "huisnummer": {"type": "integer"},
            "huisnummertoevoeging": {"type": "string"},
            "postcode": {"type": "string"},
            "woonplaats": {"type": "string"},
            "land": {
                "type": "object",
                "properties": {
                    "code": {"type": "string"},
                    "omschrijving": {"type": "string"}
                }
            }
        }
    },
    "nationaliteiten": {
        "type": "array",
        "items": {
            "type": "object",
            "properties": {
                "nationaliteit": {
                    "type": "object",
                    "properties": {
                        "code": {"type": "string"},
                        "omschrijving": {"type": "string"}
                    }
                }
            }
        }
    },
    "aNummer": {
        "type": "string",
        "description": "Administratienummer (A-nummer)"
    }
}

# Vereenvoudigde properties voor Open Register (flat structuur)
# Open Register ondersteunt geen geneste objecten, dus we maken flat properties
OPENREGISTER_PROPERTIES = {
    # Identificatie
    "burgerservicenummer": {
        "type": "string",
        "description": "Het burgerservicenummer (BSN) van de persoon"
    },
    "aNummer": {
        "type": "string",
        "description": "Administratienummer (A-nummer)"
    },
    
    # Naamgegevens (flat)
    "voornamen": {
        "type": "string",
        "description": "Voornamen van de persoon (gescheiden door spaties)"
    },
    "geslachtsnaam": {
        "type": "string",
        "description": "Geslachtsnaam (achternaam) van de persoon"
    },
    "voorvoegsel": {
        "type": "string",
        "description": "Voorvoegsel van de geslachtsnaam"
    },
    
    # Geboortegegevens (flat)
    "geboortedatum": {
        "type": "string",
        "description": "Geboortedatum in ISO 8601 formaat (YYYY-MM-DD)"
    },
    "geboorteplaats": {
        "type": "string",
        "description": "Plaats van geboorte"
    },
    "geboorteland_code": {
        "type": "string",
        "description": "Landcode van geboorte (ISO 3166-1 alpha-2)"
    },
    "geboorteland_omschrijving": {
        "type": "string",
        "description": "Landomschrijving van geboorte"
    },
    
    # Geslacht
    "geslachtsaanduiding": {
        "type": "string",
        "enum": ["man", "vrouw", "onbekend"],
        "description": "Geslachtsaanduiding"
    },
    
    # Verblijfplaats (flat)
    "verblijfplaats_straatnaam": {
        "type": "string",
        "description": "Straatnaam van de verblijfplaats"
    },
    "verblijfplaats_huisnummer": {
        "type": "string",
        "description": "Huisnummer van de verblijfplaats"
    },
    "verblijfplaats_huisnummertoevoeging": {
        "type": "string",
        "description": "Huisnummertoevoeging"
    },
    "verblijfplaats_postcode": {
        "type": "string",
        "description": "Postcode van de verblijfplaats"
    },
    "verblijfplaats_woonplaats": {
        "type": "string",
        "description": "Woonplaats van de verblijfplaats"
    },
    "verblijfplaats_land_code": {
        "type": "string",
        "description": "Landcode van verblijfplaats"
    },
    "verblijfplaats_land_omschrijving": {
        "type": "string",
        "description": "Landomschrijving van verblijfplaats"
    }
}

def get_current_schema(schema_id: int) -> Dict[str, Any]:
    """Haal huidige schema op uit database"""
    print(f"📋 Huidige schema {schema_id} ophalen...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT properties FROM oc_openregister_schemas WHERE id = {schema_id};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode != 0:
        print(f"❌ Fout bij ophalen schema: {result.stderr}")
        return {}
    
    props_json = result.stdout.strip()
    if not props_json:
        print(f"⚠️  Schema {schema_id} heeft geen properties")
        return {}
    
    try:
        return json.loads(props_json)
    except json.JSONDecodeError as e:
        print(f"❌ JSON parse error: {e}")
        return {}

def update_schema(schema_id: int, properties: Dict[str, Any], title: str = None, description: str = None):
    """Update schema properties in database"""
    print(f"🔄 Schema {schema_id} bijwerken...")
    
    # Converteer properties naar JSON
    properties_json = json.dumps(properties, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''")
    
    # Bouw UPDATE query
    update_fields = [f"properties = '{properties_escaped}'", "updated = NOW()"]
    
    if title:
        title_escaped = title.replace("'", "''")
        update_fields.append(f"title = '{title_escaped}'")
    
    if description:
        desc_escaped = description.replace("'", "''")
        update_fields.append(f"description = '{desc_escaped}'")
    
    sql = f"UPDATE oc_openregister_schemas SET {', '.join(update_fields)} WHERE id = {schema_id};"
    
    cmd = [
        'docker', 'exec', '-i', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME
    ]
    
    result = subprocess.run(cmd, input=sql, text=True, capture_output=True)
    
    if result.returncode == 0:
        print(f"✅ Schema {schema_id} bijgewerkt!")
        print(f"   Aantal properties: {len(properties)}")
        return True
    else:
        print(f"❌ Fout bij bijwerken schema: {result.stderr}")
        return False

def create_denormalized_view_sql() -> str:
    """Genereer SQL voor denormalisatie-view van inw_ax"""
    
    sql = """
-- View voor denormalisatie van inw_ax naar Haal Centraal-formaat
CREATE OR REPLACE VIEW probev.v_inw_ax_haal_centraal AS
SELECT 
    -- Identificatie
    i.bsn,
    i.pl_id,
    
    -- Naamgegevens (denormaliseren)
    COALESCE(v.voorn, '') as voornamen,
    COALESCE(n.naam, '') as geslachtsnaam,
    COALESCE(i.voorvoegsel, '') as voorvoegsel,
    
    -- Geboortegegevens
    CASE 
        WHEN i.d_geb IS NOT NULL AND LENGTH(i.d_geb::text) = 8 THEN
            TO_CHAR(TO_DATE(i.d_geb::text, 'YYYYMMDD'), 'YYYY-MM-DD')
        ELSE NULL
    END as geboortedatum,
    COALESCE(p_geb.plaats, '') as geboorteplaats,
    COALESCE(l_geb.land_code, '') as geboorteland_code,
    COALESCE(l_geb.land, '') as geboorteland_omschrijving,
    
    -- Geslacht transformatie
    CASE i.geslacht
        WHEN 'V' THEN 'vrouw'
        WHEN 'M' THEN 'man'
        WHEN 'O' THEN 'onbekend'
        ELSE 'onbekend'
    END as geslachtsaanduiding,
    
    -- A-nummer
    i.anr as aNummer,
    
    -- Metadata voor filtering
    i.ax,
    i.hist,
    i.pl_id
    
FROM probev.inw_ax i
LEFT JOIN probev.voorn v ON v.c_voorn = i.c_voorn
LEFT JOIN probev.naam n ON n.c_naam = i.c_naam
LEFT JOIN probev.plaats p_geb ON p_geb.c_plaats = i.p_geb
LEFT JOIN probev.land l_geb ON l_geb.c_land = i.l_geb
WHERE i.ax = 'A' AND i.hist = 'A';
"""
    return sql

def create_verblijfplaats_view_sql() -> str:
    """Genereer SQL voor view van verblijfplaats-gegevens"""
    
    sql = """
-- View voor verblijfplaats-gegevens uit vb_ax
CREATE OR REPLACE VIEW probev.v_vb_ax_haal_centraal AS
SELECT 
    vb.bsn,
    vb.pl_id,
    COALESCE(s.straat, '') as verblijfplaats_straatnaam,
    COALESCE(vb.huisnummer::text, '') as verblijfplaats_huisnummer,
    COALESCE(vb.huisnummertoevoeging, '') as verblijfplaats_huisnummertoevoeging,
    COALESCE(vb.postcode, '') as verblijfplaats_postcode,
    COALESCE(p.plaats, '') as verblijfplaats_woonplaats,
    COALESCE(l.land_code, '') as verblijfplaats_land_code,
    COALESCE(l.land, '') as verblijfplaats_land_omschrijving,
    vb.ax,
    vb.hist
FROM probev.vb_ax vb
LEFT JOIN probev.straat s ON s.c_straat = vb.c_straat
LEFT JOIN probev.plaats p ON p.c_plaats = vb.p_woon
LEFT JOIN probev.land l ON l.c_land = vb.l_woon
WHERE vb.ax = 'A' AND vb.hist = 'A';
"""
    return sql

def main():
    """Hoofdfunctie"""
    print("🚀 Open Register Schemas Bijwerken naar Haal Centraal-specificatie")
    print("=" * 70)
    print()
    
    # Stap 1: Haal huidige schema op
    current_props = get_current_schema(SCHEMA_ID_PERSONEN)
    if current_props:
        print(f"✅ Huidige schema heeft {len(current_props)} properties")
        print()
    
    # Stap 2: Update schema met Haal Centraal-compliant properties
    print("📝 Schema bijwerken met Haal Centraal-compliant properties...")
    success = update_schema(
        SCHEMA_ID_PERSONEN,
        OPENREGISTER_PROPERTIES,
        title="Personen (Haal Centraal)",
        description="Personen schema volgens Haal Centraal BRP Bevragen API-specificatie. Gebaseerd op probev.inw_ax tabel."
    )
    
    if not success:
        print("❌ Schema update gefaald")
        sys.exit(1)
    
    print()
    print("✅ Schema bijgewerkt!")
    print()
    print("📋 Volgende stappen:")
    print("1. Maak SQL views aan voor denormalisatie:")
    print("   - probev.v_inw_ax_haal_centraal")
    print("   - probev.v_vb_ax_haal_centraal")
    print()
    print("2. Update Open Register source mappings om views te gebruiken")
    print()
    print("3. Test de API:")
    print("   curl -u admin:password http://localhost:8080/apps/openregister/ingeschrevenpersonen/168149291")
    print()

if __name__ == '__main__':
    main()







