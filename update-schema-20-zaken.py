#!/usr/bin/env python3
"""
Script om Schema ID 20 (Zaken) bij te werken naar ZGW-compliant configuratie
"""

import subprocess
import json
import sys

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

SCHEMA_ID = 20
SOURCE_ID = 1

# ZGW-compliant Zaak properties volgens VNG Realisatie ZGW API specificatie
ZGW_ZAAK_PROPERTIES = {
    "identificatie": {
        "type": "string",
        "description": "Unieke identificatie van de zaak"
    },
    "bronorganisatie": {
        "type": "string",
        "description": "RSIN van de organisatie die de zaak heeft gecreëerd"
    },
    "zaaktype": {
        "type": "string",
        "description": "URL naar het zaaktype"
    },
    "registratiedatum": {
        "type": "string",
        "format": "date-time",
        "description": "Datum waarop de zaak is geregistreerd"
    },
    "startdatum": {
        "type": "string",
        "format": "date",
        "description": "Datum waarop de zaak is gestart"
    },
    "einddatum": {
        "type": "string",
        "format": "date",
        "description": "Datum waarop de zaak is afgerond",
        "nullable": True
    },
    "status": {
        "type": "string",
        "description": "URL naar de status"
    },
    "omschrijving": {
        "type": "string",
        "description": "Omschrijving van de zaak"
    },
    "toelichting": {
        "type": "string",
        "description": "Toelichting op de zaak",
        "nullable": True
    },
    "verantwoordelijkeOrganisatie": {
        "type": "string",
        "description": "RSIN van de verantwoordelijke organisatie"
    },
    "betrokkeneIdentificaties": {
        "type": "string",
        "description": "JSON array met betrokkene identificaties (BSN's)",
        "nullable": True
    }
}

def check_schema_exists():
    """Check of schema ID 20 bestaat"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    count = int(result.stdout.strip())
    
    if count == 0:
        print(f"⚠️  Schema ID {SCHEMA_ID} bestaat niet, wordt aangemaakt...")
        return False
    
    print(f"✅ Schema ID {SCHEMA_ID} bestaat")
    return True

def create_schema():
    """Maak schema ID 20 aan als het niet bestaat"""
    print(f"\n📝 Schema ID {SCHEMA_ID} aanmaken...")
    
    properties_json = json.dumps(ZGW_ZAAK_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''").replace('\n', '\\n')
    
    sql = f"""
    INSERT INTO oc_openregister_schemas 
    (id, title, description, properties, source_id, created, updated)
    VALUES (
        {SCHEMA_ID},
        'Zaken (ZGW)',
        'Zaken schema volgens ZGW (Zaakgericht Werken) API specificatie van VNG Realisatie',
        '{properties_escaped}',
        {SOURCE_ID},
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

def get_current_schema():
    """Haal huidige schema configuratie op"""
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT title, description, properties FROM oc_openregister_schemas WHERE id = {SCHEMA_ID};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    output = result.stdout.strip()
    
    if not output:
        return None
    
    parts = output.split('\t')
    if len(parts) < 3:
        return None
    
    return {
        'title': parts[0],
        'description': parts[1],
        'properties': parts[2]
    }

def update_schema():
    """Update schema ID 20 naar ZGW-compliant configuratie"""
    
    print(f"\n📋 Schema ID {SCHEMA_ID} (Zaken) bijwerken...")
    print(f"   ZGW-compliant properties")
    
    # Check of schema bestaat
    if not check_schema_exists():
        if not create_schema():
            return False
    else:
        # Haal huidige schema op
        current = get_current_schema()
        if current:
            print(f"\n📊 Huidige configuratie:")
            print(f"   Titel: {current['title']}")
    
    # Maak nieuwe properties JSON
    properties_json = json.dumps(ZGW_ZAAK_PROPERTIES, ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''").replace('\n', '\\n')
    
    # Update schema
    sql = f"""
    UPDATE oc_openregister_schemas 
    SET 
        title = 'Zaken (ZGW)',
        description = 'Zaken schema volgens ZGW (Zaakgericht Werken) API specificatie van VNG Realisatie',
        properties = '{properties_escaped}',
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
                if 'identificatie' in props and 'bronorganisatie' in props:
                    print(f"✅ Configuratie correct: {len(props)} properties")
                    print(f"   Titel: {title}")
                    return True
            except json.JSONDecodeError:
                pass
    
    print(f"⚠️  Verificatie gefaald")
    return False

def main():
    """Hoofdfunctie"""
    print("=" * 60)
    print("Schema ID 20 (Zaken) ZGW Configuratie")
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
    print("✅ Schema ID 20 succesvol geconfigureerd!")
    print("=" * 60)
    print("\n📝 Volgende stappen:")
    print("   1. Maak register aan voor zaken (of gebruik bestaand register)")
    print("   2. Koppel schema ID 20 aan register")
    print("   3. Test zaak-aanmaak via Open Register API")
    print("   4. Bouw ZgwZaakController voor ZGW-compliant endpoints")

if __name__ == '__main__':
    main()







