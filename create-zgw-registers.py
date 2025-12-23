#!/usr/bin/env python3
"""
Script om Registers aan te maken voor ZGW Zaken en Tasks
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

REGISTER_ID_ZAKEN = 5
REGISTER_ID_TASKS = 4
REGISTER_ID_DOCUMENTEN = 6
SCHEMA_ID_ZAKEN = 20
SCHEMA_ID_TASKS = 22
SCHEMA_ID_DOCUMENTEN = 23

def check_register_exists(register_id):
    """Check of register bestaat"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_registers WHERE id = {register_id};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    count = int(result.stdout.strip())
    return count > 0

def create_register(register_id, title, slug, description, schema_id):
    """Maak register aan"""
    print(f"\n📝 Register ID {register_id} ({title}) aanmaken...")
    
    register_uuid = str(uuid.uuid4())
    
    sql = f"""
    INSERT INTO oc_openregister_registers 
    (id, uuid, version, title, slug, description, created, updated)
    VALUES (
        {register_id},
        '{register_uuid}',
        '1.0.0',
        '{title}',
        '{slug}',
        '{description}',
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
        print(f"✅ Register ID {register_id} aangemaakt!")
        
        # Koppel schema aan register
        if schema_id:
            link_schema_to_register(register_id, schema_id)
        
        return True
    else:
        if "Duplicate entry" in result.stderr:
            print(f"⚠️  Register ID {register_id} bestaat al, wordt overgeslagen")
            return True
        print(f"❌ Fout bij aanmaken register: {result.stderr}")
        return False

def link_schema_to_register(register_id, schema_id):
    """Koppel schema aan register via schemas JSON veld"""
    print(f"   🔗 Schema ID {schema_id} koppelen aan Register ID {register_id}...")
    
    # Haal huidige schemas op
    cmd_get = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT `schemas` FROM oc_openregister_registers WHERE id = {register_id};"
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
    if schema_id in schemas:
        print(f"   ✅ Schema ID {schema_id} is al gekoppeld aan Register ID {register_id}")
        return True
    
    # Voeg schema toe
    schemas.append(schema_id)
    schemas_json_new = json.dumps(schemas, ensure_ascii=False)
    schemas_escaped = schemas_json_new.replace("'", "''")
    
    # Update register
    sql = f"""
    UPDATE oc_openregister_registers 
    SET `schemas` = '{schemas_escaped}', updated = NOW()
    WHERE id = {register_id};
    """
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print(f"   ✅ Schema ID {schema_id} gekoppeld aan Register ID {register_id}")
        return True
    else:
        print(f"   ⚠️  Fout bij koppelen schema: {result.stderr}")
        return False

def verify_registers():
    """Verifieer dat registers correct zijn aangemaakt"""
    print(f"\n🔍 Verificatie...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e',
        f"SELECT id, title, slug, `schemas` FROM oc_openregister_registers WHERE id IN ({REGISTER_ID_ZAKEN}, {REGISTER_ID_TASKS}, {REGISTER_ID_DOCUMENTEN}) ORDER BY id;"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        print(result.stdout)
        return True
    else:
        print(f"⚠️  Verificatie gefaald: {result.stderr}")
        return False

def main():
    """Hoofdfunctie"""
    print("=" * 60)
    print("ZGW Registers Aanmaken")
    print("=" * 60)
    
    # Check of registers al bestaan
    zaken_exists = check_register_exists(REGISTER_ID_ZAKEN)
    tasks_exists = check_register_exists(REGISTER_ID_TASKS)
    documenten_exists = check_register_exists(REGISTER_ID_DOCUMENTEN)
    
    if zaken_exists and tasks_exists and documenten_exists:
        print(f"\n⚠️  Registers bestaan al!")
        response = input("Wil je ze opnieuw aanmaken? (j/N): ")
        if response.lower() != 'j':
            print("❌ Geannuleerd")
            sys.exit(0)
    
    # Maak Register ID 3 (Zaken) aan
    if not zaken_exists:
        if not create_register(
            REGISTER_ID_ZAKEN,
            "Zaken (ZGW)",
            "zaken-zgw",
            "Register voor zaken volgens ZGW (Zaakgericht Werken) API specificatie",
            SCHEMA_ID_ZAKEN
        ):
            print("\n❌ Aanmaken Register Zaken gefaald!")
            sys.exit(1)
    else:
        print(f"\n✅ Register ID {REGISTER_ID_ZAKEN} bestaat al")
        link_schema_to_register(REGISTER_ID_ZAKEN, SCHEMA_ID_ZAKEN)
    
    # Maak Register ID 4 (Tasks) aan
    if not tasks_exists:
        if not create_register(
            REGISTER_ID_TASKS,
            "Tasks (ZGW)",
            "tasks-zgw",
            "Register voor tasks volgens ZGW (Zaakgericht Werken) workflow management",
            SCHEMA_ID_TASKS
        ):
            print("\n❌ Aanmaken Register Tasks gefaald!")
            sys.exit(1)
    else:
        print(f"\n✅ Register ID {REGISTER_ID_TASKS} bestaat al")
        link_schema_to_register(REGISTER_ID_TASKS, SCHEMA_ID_TASKS)
    
    # Maak Register ID 6 (Documenten) aan
    if not documenten_exists:
        if not create_register(
            REGISTER_ID_DOCUMENTEN,
            "Documenten (ZGW)",
            "documenten-zgw",
            "Register voor documenten volgens ZGW (Zaakgericht Werken). Documenten worden opgeslagen in Nextcloud Files.",
            SCHEMA_ID_DOCUMENTEN
        ):
            print("\n❌ Aanmaken Register Documenten gefaald!")
            sys.exit(1)
    else:
        print(f"\n✅ Register ID {REGISTER_ID_DOCUMENTEN} bestaat al")
        link_schema_to_register(REGISTER_ID_DOCUMENTEN, SCHEMA_ID_DOCUMENTEN)
    
    # Verifieer
    if not verify_registers():
        print("\n⚠️  Verificatie gefaald, maar registers zijn aangemaakt")
        sys.exit(1)
    
    print("\n" + "=" * 60)
    print("✅ Registers succesvol aangemaakt!")
    print("=" * 60)
    print("\n📝 Volgende stappen:")
    print("   1. Herstart Nextcloud: docker restart nextcloud")
    print("   2. Test ZGW API endpoints")
    print("   3. Maak test zaak aan via POST /apps/openregister/zgw/zaken")

if __name__ == '__main__':
    main()

