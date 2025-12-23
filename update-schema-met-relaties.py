#!/usr/bin/env python3
"""
Script om Personen schema (ID 6) bij te werken met _embedded veld voor relaties
Volgens Haal Centraal BRP Bevragen API-specificatie
"""

import subprocess
import json
import sys

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

SCHEMA_ID_PERSONEN = 6

def get_current_schema(schema_id: int) -> dict:
    """Haal huidige schema properties op"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f'SELECT properties FROM oc_openregister_schemas WHERE id = {schema_id};'
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        print(f"❌ Fout bij ophalen schema: {result.stderr}")
        return None
    
    props_json = result.stdout.strip()
    if not props_json:
        print("❌ Geen properties gevonden")
        return None
    
    try:
        return json.loads(props_json)
    except json.JSONDecodeError as e:
        print(f"❌ JSON parse error: {e}")
        return None

def update_schema_with_relations(schema_id: int, current_props: dict) -> bool:
    """Update schema met _embedded veld voor relaties"""
    
    # Haal Centraal IngeschrevenPersoon structuur voor embedded relaties
    embedded_properties = {
        "_embedded": {
            "type": "object",
            "description": "Embedded relaties volgens Haal Centraal BRP Bevragen API-specificatie",
            "properties": {
                "partners": {
                    "type": "array",
                    "description": "Partners van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Partner object volgens Haal Centraal specificatie"
                    }
                },
                "kinderen": {
                    "type": "array",
                    "description": "Kinderen van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Kind object volgens Haal Centraal specificatie"
                    }
                },
                "ouders": {
                    "type": "array",
                    "description": "Ouders van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Ouder object volgens Haal Centraal specificatie"
                    }
                },
                "nationaliteiten": {
                    "type": "array",
                    "description": "Nationaliteiten van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Nationaliteit object volgens Haal Centraal specificatie"
                    }
                }
            }
        }
    }
    
    # Voeg _embedded toe aan bestaande properties
    new_props = current_props.copy()
    new_props.update(embedded_properties)
    
    # Converteer naar JSON en escape voor MySQL
    new_props_json = json.dumps(new_props, ensure_ascii=False, indent=2)
    new_props_escaped = new_props_json.replace("'", "''").replace("\\", "\\\\")
    
    # Update schema in database
    update_sql = f"""
    UPDATE oc_openregister_schemas 
    SET properties = '{new_props_escaped}',
        updated = NOW()
    WHERE id = {schema_id};
    """
    
    cmd = [
        'docker', 'exec', '-i', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME
    ]
    
    result = subprocess.run(cmd, input=update_sql, text=True, capture_output=True)
    
    if result.returncode == 0:
        print(f"✅ Schema {schema_id} bijgewerkt met _embedded veld")
        return True
    else:
        print(f"❌ Fout bij updaten schema: {result.stderr}")
        return False

def main():
    """Hoofdfunctie"""
    print("🚀 Personen Schema Bijwerken met Relaties")
    print("=" * 60)
    print()
    
    # Stap 1: Haal huidige schema op
    print("📋 Huidige schema ophalen...")
    current_props = get_current_schema(SCHEMA_ID_PERSONEN)
    
    if not current_props:
        print("❌ Kon huidige schema niet ophalen")
        sys.exit(1)
    
    print(f"✅ Huidige schema heeft {len(current_props)} properties")
    
    # Check of _embedded al bestaat
    if "_embedded" in current_props:
        print("⚠️  _embedded veld bestaat al in schema")
        response = input("Overschrijven? (j/n): ")
        if response.lower() != 'j':
            print("❌ Geannuleerd")
            sys.exit(0)
    
    print()
    
    # Stap 2: Update schema
    print("📝 Schema bijwerken met _embedded veld voor relaties...")
    success = update_schema_with_relations(SCHEMA_ID_PERSONEN, current_props)
    
    if not success:
        print("❌ Schema update gefaald")
        sys.exit(1)
    
    print()
    print("✅ Schema bijgewerkt!")
    print()
    print("📋 Volgende stappen:")
    print("1. Update import script om relaties op te halen")
    print("2. Update Haal Centraal controller om uit Open Register te halen")
    print("3. Test met BSN 168149291")
    print()

if __name__ == '__main__':
    main()







