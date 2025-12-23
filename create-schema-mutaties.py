#!/usr/bin/env python3
"""
Script om Schema voor Mutaties aan te maken in Open Register
"""

import subprocess
import json
import sys
import uuid

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

SCHEMA_ID = 24  # Volgende beschikbare ID
REGISTER_ID = 7  # Nieuw register voor mutaties

# Mutaties properties
MUTATIES_PROPERTIES = {
    "id": {
        "type": "string",
        "description": "Unieke identificatie van de mutatie (dossier_id)"
    },
    "dossier_id": {
        "type": "string",
        "description": "Dossier ID (alias voor id)"
    },
    "mutation_type": {
        "type": "string",
        "enum": ["death", "relocation", "birth", "partnership"],
        "description": "Type mutatie"
    },
    "status": {
        "type": "string",
        "enum": ["ingediend", "in_behandeling", "afgehandeld", "afgewezen"],
        "description": "Status van de mutatie",
        "default": "ingediend"
    },
    "person_bsn": {
        "type": "string",
        "description": "BSN van de betrokken persoon",
        "nullable": True
    },
    "death_date": {
        "type": "string",
        "format": "date",
        "description": "Datum van overlijden (voor death mutaties)",
        "nullable": True
    },
    "death_place": {
        "type": "string",
        "description": "Plaats van overlijden (voor death mutaties)",
        "nullable": True
    },
    "birth_date": {
        "type": "string",
        "format": "date",
        "description": "Geboortedatum (voor birth mutaties)",
        "nullable": True
    },
    "birth_place": {
        "type": "string",
        "description": "Geboorteplaats (voor birth mutaties)",
        "nullable": True
    },
    "relocation_date": {
        "type": "string",
        "format": "date",
        "description": "Verhuisdatum (voor relocation mutaties)",
        "nullable": True
    },
    "partnership_date": {
        "type": "string",
        "format": "date",
        "description": "Datum partnerschap (voor partnership mutaties)",
        "nullable": True
    },
    "zaak_id": {
        "type": "string",
        "description": "Referentie naar zaak ID",
        "nullable": True
    },
    "reference_id": {
        "type": "string",
        "description": "Referentie ID (alias voor zaak_id)",
        "nullable": True
    },
    "documents": {
        "type": "array",
        "items": {
            "type": "object"
        },
        "description": "Array van gekoppelde documenten",
        "nullable": True
    },
    "payload_raw": {
        "type": "string",
        "description": "Ruwe payload van de mutatie request (JSON)",
        "nullable": True
    },
    "mutation_data": {
        "type": "object",
        "description": "Volledige mutatie data",
        "nullable": True
    },
    "persoon_status": {
        "type": "string",
        "description": "Status van de persoon na mutatie (bijv. 'overleden')",
        "nullable": True
    },
    "created_at": {
        "type": "string",
        "format": "date-time",
        "description": "Aanmaakdatum/tijd"
    },
    "updated_at": {
        "type": "string",
        "format": "date-time",
        "description": "Laatste wijzigingsdatum/tijd"
    }
}

def check_schema_exists():
    """Check of schema ID bestaat"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    count = int(result.stdout.strip())
    
    if count > 0:
        print(f"⚠️  Schema ID {SCHEMA_ID} bestaat al!")
        return True
    
    return False

def create_schema():
    """Maak schema aan"""
    print(f"\n📝 Schema ID {SCHEMA_ID} (Mutaties) aanmaken...")
    
    properties_json = json.dumps(MUTATIES_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''").replace('\n', '\\n')
    
    schema_uuid = str(uuid.uuid4())
    
    sql = f"""
    INSERT INTO oc_openregister_schemas 
    (id, uuid, version, title, description, properties, created, updated)
    VALUES (
        {SCHEMA_ID},
        '{schema_uuid}',
        '1.0.0',
        'Mutaties (vrijBRP)',
        'Schema voor BRP mutaties: overlijden, verhuizingen, geboorten en partnerschappen',
        '{properties_escaped}',
        NOW(),
        NOW()
    );
    """
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print(f"✅ Schema ID {SCHEMA_ID} aangemaakt!")
        return True
    else:
        if "Duplicate entry" in result.stderr:
            print(f"⚠️  Schema ID {SCHEMA_ID} bestaat al, wordt overgeslagen")
            return True
        print(f"❌ Fout bij aanmaken schema: {result.stderr}")
        return False

def check_register_exists():
    """Check of register bestaat"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_registers WHERE id = {REGISTER_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    count = int(result.stdout.strip())
    return count > 0

def create_register():
    """Maak register aan"""
    print(f"\n📝 Register ID {REGISTER_ID} (Mutaties) aanmaken...")
    
    register_uuid = str(uuid.uuid4())
    
    sql = f"""
    INSERT INTO oc_openregister_registers 
    (id, uuid, version, title, slug, description, created, updated)
    VALUES (
        {REGISTER_ID},
        '{register_uuid}',
        '1.0.0',
        'Mutaties',
        'mutaties',
        'Register voor BRP mutaties (overlijden, verhuizingen, geboorten, partnerschappen)',
        NOW(),
        NOW()
    );
    """
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print(f"✅ Register ID {REGISTER_ID} aangemaakt!")
        return True
    else:
        if "Duplicate entry" in result.stderr:
            print(f"⚠️  Register ID {REGISTER_ID} bestaat al, wordt overgeslagen")
            return True
        print(f"❌ Fout bij aanmaken register: {result.stderr}")
        return False

def link_schema_to_register():
    """Koppel schema aan register"""
    print(f"\n🔗 Schema ID {SCHEMA_ID} koppelen aan Register ID {REGISTER_ID}...")
    
    # Haal huidige schemas op
    cmd_get = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT `schemas` FROM oc_openregister_registers WHERE id = {REGISTER_ID};"
    ]
    
    result_get = subprocess.run(cmd_get, capture_output=True, text=True)
    schemas_json = result_get.stdout.strip()
    
    # Parse schemas
    if schemas_json and schemas_json != 'NULL':
        try:
            schemas = json.loads(schemas_json)
            if not isinstance(schemas, list):
                schemas = []
        except:
            schemas = []
    else:
        schemas = []
    
    # Check of schema al gekoppeld is
    if SCHEMA_ID in schemas:
        print(f"✅ Schema ID {SCHEMA_ID} is al gekoppeld aan Register ID {REGISTER_ID}")
        return True
    
    # Voeg schema toe
    schemas.append(SCHEMA_ID)
    schemas_json_new = json.dumps(schemas, ensure_ascii=False)
    schemas_escaped = schemas_json_new.replace("'", "''")
    
    # Update register
    sql = f"""
    UPDATE oc_openregister_registers 
    SET `schemas` = '{schemas_escaped}', updated = NOW()
    WHERE id = {REGISTER_ID};
    """
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print(f"✅ Schema ID {SCHEMA_ID} gekoppeld aan Register ID {REGISTER_ID}")
        return True
    else:
        print(f"❌ Fout bij koppelen schema: {result.stderr}")
        return False

def verify_create():
    """Verifieer dat het schema correct is aangemaakt"""
    print(f"\n🔍 Verificatie...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT title, properties FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    output = result.stdout.strip()
    
    if output:
        parts = output.split('\t')
        if len(parts) >= 2:
            title = parts[0]
            try:
                props = json.loads(parts[1])
                if 'mutation_type' in props and 'status' in props:
                    print(f"✅ Schema correct aangemaakt: {len(props)} properties")
                    print(f"   Titel: {title}")
                    return True
            except json.JSONDecodeError as e:
                print(f"⚠️  JSON parse error: {e}")
    
    print(f"⚠️  Verificatie gefaald")
    return False

def main():
    """Hoofdfunctie"""
    print("=" * 60)
    print("Schema Mutaties Aanmaken")
    print("=" * 60)
    
    # Check of schema al bestaat
    if check_schema_exists():
        print(f"\n⚠️  Schema ID {SCHEMA_ID} bestaat al!")
        response = input("Wil je het overschrijven? (j/N): ")
        if response.lower() != 'j':
            print("❌ Geannuleerd")
            sys.exit(0)
    
    # Maak schema aan
    if not create_schema():
        print("\n❌ Aanmaken schema gefaald!")
        sys.exit(1)
    
    # Maak register aan (als het niet bestaat)
    if not check_register_exists():
        if not create_register():
            print("\n⚠️  Aanmaken register gefaald, maar schema is aangemaakt")
    
    # Koppel schema aan register
    if not link_schema_to_register():
        print("\n⚠️  Koppelen schema gefaald")
    
    # Verifieer aanmaak
    if not verify_create():
        print("\n⚠️  Verificatie gefaald, maar schema is aangemaakt")
        sys.exit(1)
    
    print("\n" + "=" * 60)
    print("✅ Schema Mutaties succesvol aangemaakt!")
    print("=" * 60)
    print(f"\n📝 Schema ID: {SCHEMA_ID}")
    print(f"📝 Register ID: {REGISTER_ID}")
    print("\n📝 Volgende stappen:")
    print("   1. Migreer bestaande mutaties uit oc_openregister_mutaties")
    print("   2. Update VrijBrpDossiersController om ObjectService te gebruiken")
    print("   3. Test mutatie endpoints")

if __name__ == '__main__':
    main()





