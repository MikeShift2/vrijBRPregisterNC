#!/usr/bin/env python3
"""
Script om schema ID 7 (Adressen) te koppelen aan register ID 3 (Adressen)
"""

import subprocess
import json

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

REGISTER_ID = 3
SCHEMA_ID = 7

def link_schema_to_register():
    """Koppel schema 7 aan register 3"""
    
    # Haal huidige schemas op
    get_schemas_cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        f"SELECT schemas FROM oc_openregister_registers WHERE id = {REGISTER_ID};"
    ]
    
    result = subprocess.run(get_schemas_cmd, capture_output=True, text=True)
    current_schemas_json = result.stdout.strip()
    
    # Parse huidige schemas (kan NULL, leeg, of JSON array zijn)
    if current_schemas_json and current_schemas_json != 'NULL':
        try:
            current_schemas = json.loads(current_schemas_json)
            if not isinstance(current_schemas, list):
                current_schemas = []
        except:
            current_schemas = []
    else:
        current_schemas = []
    
    # Voeg schema 7 toe als het er nog niet in zit
    if SCHEMA_ID not in current_schemas:
        current_schemas.append(SCHEMA_ID)
        schemas_json = json.dumps(current_schemas)
        
        # Update register
        update_cmd = [
            'docker', 'exec', DB_HOST,
            'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
            DB_NAME, '-e',
            f"UPDATE oc_openregister_registers SET schemas = '{schemas_json}' WHERE id = {REGISTER_ID};"
        ]
        
        update_result = subprocess.run(update_cmd, capture_output=True, text=True)
        
        if update_result.returncode == 0:
            print(f"✅ Schema {SCHEMA_ID} gekoppeld aan register {REGISTER_ID}")
            print(f"   Schemas in register: {current_schemas}")
            return True
        else:
            print(f"❌ Fout bij koppelen schema: {update_result.stderr}")
            return False
    else:
        print(f"✅ Schema {SCHEMA_ID} is al gekoppeld aan register {REGISTER_ID}")
        print(f"   Schemas in register: {current_schemas}")
        return True

if __name__ == '__main__':
    print("📊 Schema koppelen aan register...")
    print("")
    link_schema_to_register()







