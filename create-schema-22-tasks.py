#!/usr/bin/env python3
"""
Script om Schema ID 22 (Tasks) aan te maken voor ZGW task management
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

SCHEMA_ID = 22
SOURCE_ID = 1

# Tasks properties voor workflow management
TASKS_PROPERTIES = {
    "task_id": {
        "type": "string",
        "description": "Unieke identificatie van de task"
    },
    "zaak_id": {
        "type": "string",
        "description": "UUID van de bijbehorende zaak in Open Register",
        "nullable": True
    },
    "zaak_identificatie": {
        "type": "string",
        "description": "Identificatie van de bijbehorende zaak",
        "nullable": True
    },
    "task_type": {
        "type": "string",
        "description": "Type van de task (relocation_consent, birth_acknowledgement, document_upload, review, etc.)"
    },
    "status": {
        "type": "string",
        "enum": ["planned", "in_progress", "done"],
        "description": "Status van de task"
    },
    "bsn": {
        "type": "string",
        "description": "BSN van de betrokkene",
        "nullable": True
    },
    "description": {
        "type": "string",
        "description": "Beschrijving van de task"
    },
    "created_at": {
        "type": "string",
        "format": "date-time",
        "description": "Datum/tijd waarop de task is aangemaakt"
    },
    "due_date": {
        "type": "string",
        "format": "date-time",
        "description": "Datum/tijd waarop de task moet zijn voltooid",
        "nullable": True
    },
    "completed_at": {
        "type": "string",
        "format": "date-time",
        "description": "Datum/tijd waarop de task is voltooid",
        "nullable": True
    }
}

def check_schema_exists():
    """Check of schema ID 22 bestaat"""
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
    """Maak schema ID 22 aan"""
    print(f"\n📝 Schema ID {SCHEMA_ID} (Tasks) aanmaken...")
    
    properties_json = json.dumps(TASKS_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''").replace('\n', '\\n')
    
    schema_uuid = str(uuid.uuid4())
    
    sql = f"""
    INSERT INTO oc_openregister_schemas 
    (id, uuid, version, title, description, properties, created, updated)
    VALUES (
        {SCHEMA_ID},
        '{schema_uuid}',
        '1.0.0',
        'Tasks (ZGW)',
        'Tasks schema voor workflow management volgens ZGW (Zaakgericht Werken)',
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
        print(f"❌ Fout bij aanmaken schema: {result.stderr}")
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
                if 'task_id' in props and 'task_type' in props and 'status' in props:
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
    print("Schema ID 22 (Tasks) Aanmaken")
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
        print("\n❌ Aanmaken gefaald!")
        sys.exit(1)
    
    # Verifieer aanmaak
    if not verify_create():
        print("\n⚠️  Verificatie gefaald, maar schema is aangemaakt")
        sys.exit(1)
    
    print("\n" + "=" * 60)
    print("✅ Schema ID 22 succesvol aangemaakt!")
    print("=" * 60)
    print("\n📝 Volgende stappen:")
    print("   1. Maak register aan voor tasks (of gebruik bestaand register)")
    print("   2. Koppel schema ID 22 aan register")
    print("   3. Test task-aanmaak via Open Register API")
    print("   4. Bouw ZgwTaskController voor Tasks API endpoints")

if __name__ == '__main__':
    main()

