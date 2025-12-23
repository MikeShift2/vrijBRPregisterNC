#!/usr/bin/env python3
"""
Update Personen schema (ID 6) naar nested object structuur
Volgens Haal Centraal BRP Bevragen API specificatie
"""

import subprocess
import json
from datetime import datetime

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"
SCHEMA_ID = 6

# Nested object schema (Haal Centraal compliant)
NESTED_SCHEMA_PROPERTIES = {
    "burgerservicenummer": {
        "type": "string",
        "pattern": "^[0-9]{9}$",
        "description": "Het burgerservicenummer (BSN) van de persoon"
    },
    "aNummer": {
        "type": "string",
        "pattern": "^[0-9]{10}$",
        "description": "Administratienummer (A-nummer) van de persoon"
    },
    "naam": {
        "type": "object",
        "description": "Naamgegevens van de persoon",
        "properties": {
            "voornamen": {
                "type": "string",
                "description": "Voornamen van de persoon"
            },
            "voorvoegsel": {
                "type": "string",
                "description": "Voorvoegsel van de geslachtsnaam"
            },
            "geslachtsnaam": {
                "type": "string",
                "description": "Geslachtsnaam (achternaam) van de persoon"
            }
        }
    },
    "geboorte": {
        "type": "object",
        "description": "Geboortegegevens van de persoon",
        "properties": {
            "datum": {
                "type": "object",
                "description": "Geboortedatum",
                "properties": {
                    "datum": {
                        "type": "string",
                        "format": "date",
                        "description": "Geboortedatum in ISO 8601 formaat (YYYY-MM-DD)"
                    },
                    "jaar": {
                        "type": "integer",
                        "description": "Geboortejaar"
                    },
                    "maand": {
                        "type": "integer",
                        "minimum": 1,
                        "maximum": 12,
                        "description": "Geboortemaand"
                    },
                    "dag": {
                        "type": "integer",
                        "minimum": 1,
                        "maximum": 31,
                        "description": "Geboortedag"
                    }
                }
            },
            "plaats": {
                "type": "string",
                "description": "Geboorteplaats"
            },
            "land": {
                "type": "object",
                "description": "Geboorteland",
                "properties": {
                    "code": {
                        "type": "string",
                        "description": "ISO 3166-1 alpha-2 landcode"
                    },
                    "omschrijving": {
                        "type": "string",
                        "description": "Naam van het land"
                    }
                }
            }
        }
    },
    "geslacht": {
        "type": "object",
        "description": "Geslachtsaanduiding van de persoon",
        "properties": {
            "code": {
                "type": "string",
                "enum": ["M", "V", "O"],
                "description": "Geslachtscode: M (man), V (vrouw), O (onbekend)"
            },
            "omschrijving": {
                "type": "string",
                "enum": ["man", "vrouw", "onbekend"],
                "description": "Geslachtsomschrijving"
            }
        }
    },
    "verblijfplaats": {
        "type": "object",
        "description": "Gegevens over de verblijfplaats van de persoon",
        "properties": {
            "straatnaam": {
                "type": "string",
                "description": "Naam van de straat"
            },
            "huisnummer": {
                "type": "integer",
                "description": "Huisnummer"
            },
            "huisletter": {
                "type": "string",
                "description": "Huisletter"
            },
            "huisnummertoevoeging": {
                "type": "string",
                "description": "Huisnummertoevoeging"
            },
            "postcode": {
                "type": "string",
                "pattern": "^[1-9][0-9]{3}[A-Z]{2}$",
                "description": "Postcode in formaat 1234AB"
            },
            "woonplaats": {
                "type": "string",
                "description": "Naam van de woonplaats"
            },
            "land": {
                "type": "object",
                "description": "Land van verblijfplaats",
                "properties": {
                    "code": {
                        "type": "string",
                        "description": "ISO 3166-1 alpha-2 landcode"
                    },
                    "omschrijving": {
                        "type": "string",
                        "description": "Naam van het land"
                    }
                }
            }
        }
    },
    "_embedded": {
        "type": "object",
        "description": "Embedded relaties volgens Haal Centraal BRP Bevragen API",
        "properties": {
            "partners": {
                "type": "array",
                "description": "Partners/echtgenoten van de persoon",
                "items": {
                    "type": "object",
                    "properties": {
                        "burgerservicenummer": {"type": "string"},
                        "naam": {"type": "object"}
                    }
                }
            },
            "kinderen": {
                "type": "array",
                "description": "Kinderen van de persoon",
                "items": {
                    "type": "object",
                    "properties": {
                        "burgerservicenummer": {"type": "string"},
                        "naam": {"type": "object"}
                    }
                }
            },
            "ouders": {
                "type": "array",
                "description": "Ouders van de persoon",
                "items": {
                    "type": "object",
                    "properties": {
                        "burgerservicenummer": {"type": "string"},
                        "naam": {"type": "object"}
                    }
                }
            },
            "nationaliteiten": {
                "type": "array",
                "description": "Nationaliteiten van de persoon",
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
            }
        }
    },
    "_metadata": {
        "type": "object",
        "description": "Interne metadata voor Open Register",
        "properties": {
            "pl_id": {
                "type": "integer",
                "description": "Persoonslijst ID uit probev database"
            },
            "ax": {
                "type": "string",
                "enum": ["A", "X"],
                "description": "Actueel (A) of Archief (X)"
            },
            "hist": {
                "type": "string",
                "enum": ["A", "Z"],
                "description": "Actueel record (A) of Historie (Z)"
            }
        }
    }
}

def backup_schema():
    """Maak backup van huidig schema"""
    print("📦 Backup maken van huidig schema...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT properties FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        backup_file = f'schema-backup-{SCHEMA_ID}-{timestamp}.json'
        
        with open(backup_file, 'w') as f:
            f.write(result.stdout.strip())
        
        print(f"✅ Backup opgeslagen: {backup_file}")
        return True
    else:
        print(f"❌ Backup mislukt: {result.stderr}")
        return False

def update_schema():
    """Update schema met nested structure"""
    print(f"\n🔄 Updaten schema ID {SCHEMA_ID} naar nested objects...")
    
    # Converteer properties naar JSON string en escape voor SQL
    properties_json = json.dumps(NESTED_SCHEMA_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''")
    
    sql = f"""
    UPDATE oc_openregister_schemas 
    SET 
        properties = '{properties_escaped}',
        description = 'Ingeschreven Persoon volgens Haal Centraal BRP Bevragen API met nested objects',
        updated = NOW()
    WHERE id = {SCHEMA_ID};
    """
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print("✅ Schema succesvol bijgewerkt naar nested objects!")
        return True
    else:
        print(f"❌ Schema update mislukt: {result.stderr}")
        return False

def verify_schema():
    """Verifieer dat schema correct is bijgewerkt"""
    print("\n🔍 Verificatie van bijgewerkt schema...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT JSON_EXTRACT(properties, '$.naam.type') FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0 and 'object' in result.stdout.strip():
        print("✅ Verificatie geslaagd: 'naam' is nu een nested object")
        
        # Toon structuur
        cmd2 = [
            'docker', 'exec', DB_HOST,
            'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
            DB_NAME, '-sN', '-e',
            f"SELECT JSON_KEYS(JSON_EXTRACT(properties, '$.naam.properties')) FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
        ]
        
        result2 = subprocess.run(cmd2, capture_output=True, text=True)
        if result2.returncode == 0:
            print(f"   naam.properties bevat: {result2.stdout.strip()}")
        
        return True
    else:
        print("❌ Verificatie mislukt: schema niet correct bijgewerkt")
        return False

def main():
    print("=" * 60)
    print("🔄 Schema Update: Plat → Nested Objects")
    print("=" * 60)
    print(f"\nSchema ID: {SCHEMA_ID}")
    print(f"Database: {DB_NAME}@{DB_HOST}")
    print("\nWijzigingen:")
    print("  • Flat velden → Nested objects")
    print("  • naam: voornamen, voorvoegsel, geslachtsnaam")
    print("  • geboorte: datum, plaats, land")
    print("  • geslacht: code, omschrijving")
    print("  • verblijfplaats: straatnaam, huisnummer, postcode, etc.")
    print("  • _metadata toegevoegd: pl_id, ax, hist")
    print("\n" + "=" * 60)
    
    # Stap 1: Backup
    if not backup_schema():
        print("\n❌ Gestopt: backup mislukt")
        return 1
    
    # Stap 2: Update
    if not update_schema():
        print("\n❌ Gestopt: update mislukt")
        return 1
    
    # Stap 3: Verify
    if not verify_schema():
        print("\n⚠️  Waarschuwing: verificatie mislukt")
        return 1
    
    print("\n" + "=" * 60)
    print("✅ Schema succesvol bijgewerkt naar nested objects!")
    print("=" * 60)
    print("\nVolgende stap:")
    print("  python3 update-import-script-nested.py")
    print("  - Past import scripts aan voor nested objects")
    
    return 0

if __name__ == '__main__':
    exit(main())
