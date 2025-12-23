#!/usr/bin/env python3
"""
Script om Schema ID 7 (Adressen) bij te werken naar v_vb_ax_haal_centraal view
"""

import subprocess
import json
import sys

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

SCHEMA_ID = 7
SOURCE_ID = 1
TABLE_NAME = "v_vb_ax_haal_centraal"

# Haal Centraal Adres properties volgens specificatie
HAAL_CENTRAAL_ADRES_PROPERTIES = {
    "pl_id": {
        "type": "integer",
        "description": "Persoonslijst ID"
    },
    "bsn": {
        "type": "string",
        "description": "Burgerservicenummer"
    },
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
        "description": "Huisnummertoevoeging van de verblijfplaats"
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
        "description": "Landcode van de verblijfplaats"
    },
    "verblijfplaats_land_omschrijving": {
        "type": "string",
        "description": "Landomschrijving van de verblijfplaats"
    },
    "ax": {
        "type": "string",
        "description": "Actueel/Archief indicator"
    },
    "hist": {
        "type": "string",
        "description": "Historie indicator"
    }
}

def check_schema_exists():
    """Check of schema ID 7 bestaat"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    count = int(result.stdout.strip())
    
    if count == 0:
        print(f"❌ Schema ID {SCHEMA_ID} bestaat niet!")
        return False
    
    print(f"✅ Schema ID {SCHEMA_ID} bestaat")
    return True

def get_current_schema():
    """Haal huidige schema configuratie op"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT title, description, properties, configuration FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    output = result.stdout.strip()
    
    if not output:
        print(f"❌ Kon schema ID {SCHEMA_ID} niet ophalen")
        return None
    
    parts = output.split('\t')
    if len(parts) < 4:
        print(f"❌ Onverwachte output van schema query")
        return None
    
    return {
        'title': parts[0],
        'description': parts[1],
        'properties': parts[2],
        'configuration': parts[3]
    }

def update_schema():
    """Update schema ID 7 naar v_vb_ax_haal_centraal"""
    
    print(f"\n📋 Schema ID {SCHEMA_ID} (Adressen) bijwerken...")
    print(f"   View: {TABLE_NAME}")
    print(f"   Source ID: {SOURCE_ID}")
    
    # Check of schema bestaat
    if not check_schema_exists():
        return False
    
    # Haal huidige schema op
    current = get_current_schema()
    if current:
        print(f"\n📊 Huidige configuratie:")
        print(f"   Titel: {current['title']}")
        print(f"   Configuratie: {current['configuration']}")
    
    # Maak nieuwe properties JSON
    properties_json = json.dumps(HAAL_CENTRAAL_ADRES_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''").replace('\n', '\\n')
    
    # Maak nieuwe configuration JSON
    configuration = {
        "table_name": TABLE_NAME,
        "source_id": SOURCE_ID
    }
    configuration_json = json.dumps(configuration, ensure_ascii=False)
    configuration_escaped = configuration_json.replace("'", "''")
    
    # Update schema
    sql = f"""
    UPDATE oc_openregister_schemas 
    SET 
        properties = '{properties_escaped}',
        configuration = '{configuration_escaped}',
        updated = NOW()
    WHERE id = {SCHEMA_ID};
    """
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    print(f"\n🔄 Schema bijwerken...")
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print(f"✅ Schema ID {SCHEMA_ID} succesvol bijgewerkt!")
        return True
    else:
        print(f"❌ Fout bij bijwerken schema:")
        print(result.stderr)
        return False

def verify_update():
    """Verifieer dat de update correct is"""
    print(f"\n🔍 Verificatie...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT configuration FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    config_json = result.stdout.strip()
    
    if config_json and config_json != 'NULL':
        try:
            config = json.loads(config_json)
            if config.get('table_name') == TABLE_NAME:
                print(f"✅ Configuratie correct: table_name = {TABLE_NAME}")
                return True
            else:
                print(f"⚠️  Configuratie onverwacht: {config}")
                return False
        except json.JSONDecodeError:
            print(f"⚠️  Kon configuratie niet parsen: {config_json}")
            return False
    else:
        print(f"⚠️  Geen configuratie gevonden")
        return False

def main():
    """Hoofdfunctie"""
    print("=" * 60)
    print("Schema ID 7 (Adressen) Bijwerken")
    print("=" * 60)
    
    # Update schema
    if not update_schema():
        print("\n❌ Update gefaald!")
        sys.exit(1)
    
    # Verifieer update
    if not verify_update():
        print("\n⚠️  Verificatie gefaald, maar update is uitgevoerd")
        sys.exit(1)
    
    print("\n" + "=" * 60)
    print("✅ Schema ID 7 succesvol bijgewerkt!")
    print("=" * 60)
    print("\n📝 Volgende stappen:")
    print("   1. Test adres-ophaling via Haal Centraal API")
    print("   2. Verifieer dat data correct wordt getransformeerd")
    print("   3. Test verblijfplaats endpoint")

if __name__ == '__main__':
    main()

