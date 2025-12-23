#!/usr/bin/env python3
"""
Script om een nieuw register aan te maken voor Adressen in OpenRegister
"""

import subprocess
import json
import uuid

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

def create_adressen_register():
    """Maak een nieuw register aan voor Adressen"""
    
    # Genereer UUID voor register
    register_uuid = str(uuid.uuid4())
    
    # Register data
    register_title = "Adressen"
    register_description = "Register voor adresgegevens gekoppeld aan personen via BSN"
    
    # Maak SQL query
    sql = f"""
    INSERT INTO oc_openregister_registers 
    (uuid, version, title, description, source, created, updated)
    VALUES 
    ('{register_uuid}', '1.0.0', '{register_title}', '{register_description}', 'internal', NOW(), NOW());
    """
    
    # Voer SQL uit
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        # Haal het nieuwe register ID op
        get_id_cmd = [
            'docker', 'exec', DB_HOST,
            'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
            DB_NAME, '-sN', '-e',
            f"SELECT id FROM oc_openregister_registers WHERE uuid = '{register_uuid}';"
        ]
        
        id_result = subprocess.run(get_id_cmd, capture_output=True, text=True)
        register_id = id_result.stdout.strip()
        
        print(f"✅ Register '{register_title}' aangemaakt!")
        print(f"   UUID: {register_uuid}")
        print(f"   ID: {register_id}")
        return int(register_id)
    else:
        print(f"❌ Fout bij aanmaken register: {result.stderr}")
        return None

def main():
    print("📊 Nieuw register aanmaken voor Adressen...")
    print("")
    
    register_id = create_adressen_register()
    
    if register_id:
        print("")
        print("✅ Register aangemaakt!")
        print(f"   Register ID: {register_id}")
        print("")
        print("⚠️  Let op: Bestaande adressen staan nog in register 2")
        print("   Je moet deze mogelijk verplaatsen naar het nieuwe register")
        print("   of de HaalCentraalBrpController aanpassen om register {register_id} te gebruiken")

if __name__ == '__main__':
    main()







