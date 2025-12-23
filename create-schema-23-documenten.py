#!/usr/bin/env python3
"""
Script om Schema ID 23 (Documenten) aan te maken voor ZGW document management
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

SCHEMA_ID = 23
SOURCE_ID = 1

# Documenten properties voor ZGW document management
DOCUMENTEN_PROPERTIES = {
    "document_id": {
        "type": "string",
        "description": "Unieke identificatie van het document"
    },
    "zaak_id": {
        "type": "string",
        "description": "UUID van de bijbehorende zaak",
        "nullable": True
    },
    "zaak_identificatie": {
        "type": "string",
        "description": "Identificatie van de bijbehorende zaak",
        "nullable": True
    },
    "document_type": {
        "type": "string",
        "description": "Type document (bijv. 'bijlage', 'besluit', 'notitie')"
    },
    "titel": {
        "type": "string",
        "description": "Titel van het document"
    },
    "beschrijving": {
        "type": "string",
        "description": "Beschrijving van het document",
        "nullable": True
    },
    "bestandsnaam": {
        "type": "string",
        "description": "Naam van het bestand in Nextcloud"
    },
    "bestandspad": {
        "type": "string",
        "description": "Pad naar het bestand in Nextcloud Files"
    },
    "bestandsgrootte": {
        "type": "integer",
        "description": "Grootte van het bestand in bytes"
    },
    "mime_type": {
        "type": "string",
        "description": "MIME type van het bestand (bijv. 'application/pdf')"
    },
    "auteur": {
        "type": "string",
        "description": "Auteur van het document",
        "nullable": True
    },
    "creatiedatum": {
        "type": "string",
        "format": "date-time",
        "description": "Datum/tijd waarop het document is aangemaakt"
    },
    "versie": {
        "type": "string",
        "description": "Versie van het document",
        "default": "1.0"
    }
}

def check_schema_exists():
    """Check of schema ID 23 bestaat"""
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
    """Maak schema ID 23 aan"""
    print(f"\n📝 Schema ID {SCHEMA_ID} (Documenten) aanmaken...")
    
    properties_json = json.dumps(DOCUMENTEN_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''").replace('\n', '\\n')
    
    schema_uuid = str(uuid.uuid4())
    
    sql = f"""
    INSERT INTO oc_openregister_schemas 
    (id, uuid, version, title, description, properties, created, updated)
    VALUES (
        {SCHEMA_ID},
        '{schema_uuid}',
        '1.0.0',
        'Documenten (ZGW)',
        'Documenten schema voor ZGW document management. Documenten worden opgeslagen in Nextcloud Files.',
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
                if 'document_id' in props and 'bestandspad' in props:
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
    print("Schema ID 23 (Documenten) Aanmaken")
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
    print("✅ Schema ID 23 succesvol aangemaakt!")
    print("=" * 60)
    print("\n📝 Volgende stappen:")
    print("   1. Maak register aan voor documenten")
    print("   2. Koppel schema ID 23 aan register")
    print("   3. Bouw ZgwDocumentController voor document endpoints")
    print("   4. Implementeer Nextcloud Files integratie")

if __name__ == '__main__':
    main()







