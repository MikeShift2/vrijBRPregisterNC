#!/usr/bin/env python3
"""
Script om bestaande mutaties uit oc_openregister_mutaties te migreren naar Open Register schema
"""

import subprocess
import json
import sys
import uuid
from datetime import datetime

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

REGISTER_ID = 7
SCHEMA_ID = 24

def get_existing_mutaties():
    """Haal alle bestaande mutaties op uit oc_openregister_mutaties"""
    print("\n📊 Bestaande mutaties ophalen...")
    
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        "SELECT * FROM oc_openregister_mutaties ORDER BY created_at;"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode != 0:
        print(f"❌ Fout bij ophalen mutaties: {result.stderr}")
        return []
    
    # Parse resultaten (MariaDB output is tab-separated)
    lines = result.stdout.strip().split('\n')
    if not lines or lines[0] == '':
        print("✅ Geen bestaande mutaties gevonden")
        return []
    
    # Haal kolomnamen op
    cmd_cols = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-sN', '-e',
        "SHOW COLUMNS FROM oc_openregister_mutaties;"
    ]
    
    result_cols = subprocess.run(cmd_cols, capture_output=True, text=True)
    columns = []
    if result_cols.returncode == 0:
        for line in result_cols.stdout.strip().split('\n'):
            if line:
                cols = line.split('\t')
                if cols:
                    columns.append(cols[0])
    
    # Parse rijen
    mutaties = []
    for line in lines:
        if not line:
            continue
        values = line.split('\t')
        mutatie = {}
        for i, col in enumerate(columns):
            if i < len(values):
                mutatie[col] = values[i] if values[i] != 'NULL' else None
        if mutatie:
            mutaties.append(mutatie)
    
    print(f"✅ {len(mutaties)} mutaties gevonden")
    return mutaties

def transform_mutatie_to_openregister(mutatie):
    """Transformeer mutatie van database formaat naar Open Register formaat"""
    mutatie_data = {
        'id': mutatie.get('dossier_id') or f"DOSSIER-{datetime.now().strftime('%Y%m%d')}-{uuid.uuid4().hex[:8]}",
        'dossier_id': mutatie.get('dossier_id'),
        'mutation_type': mutatie.get('mutation_type', 'unknown'),
        'status': mutatie.get('status', 'ingediend'),
        'created_at': mutatie.get('created_at') or datetime.now().isoformat(),
        'updated_at': mutatie.get('updated_at') or datetime.now().isoformat()
    }
    
    # Type-specifieke velden
    if mutatie.get('mutation_type') == 'death':
        mutatie_data['person_bsn'] = mutatie.get('person_bsn')
        mutatie_data['death_date'] = mutatie.get('death_date')
        mutatie_data['death_place'] = mutatie.get('death_place')
        mutatie_data['persoon_status'] = 'overleden'
    elif mutatie.get('mutation_type') == 'birth':
        mutatie_data['birth_date'] = mutatie.get('birth_date')
        mutatie_data['birth_place'] = mutatie.get('birth_place')
    elif mutatie.get('mutation_type') == 'relocation':
        mutatie_data['relocation_date'] = mutatie.get('relocation_date')
    elif mutatie.get('mutation_type') == 'partnership':
        mutatie_data['partnership_date'] = mutatie.get('partnership_date')
    
    # Referenties
    if mutatie.get('reference_id'):
        mutatie_data['reference_id'] = mutatie.get('reference_id')
        mutatie_data['zaak_id'] = mutatie.get('reference_id')
    
    # Documenten
    if mutatie.get('documents'):
        try:
            docs = json.loads(mutatie['documents']) if isinstance(mutatie['documents'], str) else mutatie['documents']
            mutatie_data['documents'] = docs if isinstance(docs, list) else []
        except:
            mutatie_data['documents'] = []
    
    # Payload
    if mutatie.get('mutation_data'):
        try:
            payload = json.loads(mutatie['mutation_data']) if isinstance(mutatie['mutation_data'], str) else mutatie['mutation_data']
            mutatie_data['mutation_data'] = payload
            mutatie_data['payload_raw'] = json.dumps(payload) if isinstance(payload, dict) else mutatie['mutation_data']
        except:
            mutatie_data['payload_raw'] = str(mutatie.get('mutation_data'))
    
    return mutatie_data

def create_object_in_openregister(mutatie_data):
    """Maak object aan in Open Register via SQL (directe insert)"""
    object_uuid = str(uuid.uuid4())
    object_json = json.dumps(mutatie_data, ensure_ascii=False)
    object_json_escaped = object_json.replace("'", "''")
    
    # Genereer dossier_id als ID voor lookup
    dossier_id = mutatie_data.get('dossier_id') or mutatie_data.get('id')
    
    sql = f"""
    INSERT INTO openregister_objects 
    (uuid, register, schema, object, version, created, updated)
    VALUES (
        '{object_uuid}',
        {REGISTER_ID},
        {SCHEMA_ID},
        '{object_json_escaped}',
        1,
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
        return object_uuid
    else:
        if "Duplicate entry" in result.stderr:
            print(f"⚠️  Mutatie {dossier_id} bestaat al in Open Register")
            return None
        print(f"❌ Fout bij aanmaken object voor {dossier_id}: {result.stderr}")
        return None

def main():
    """Hoofdfunctie"""
    print("=" * 60)
    print("Mutaties Migreren naar Open Register")
    print("=" * 60)
    
    # Haal bestaande mutaties op
    mutaties = get_existing_mutaties()
    
    if not mutaties:
        print("\n✅ Geen mutaties om te migreren")
        sys.exit(0)
    
    # Vraag bevestiging
    print(f"\n⚠️  {len(mutaties)} mutaties gevonden om te migreren")
    response = input("Wil je deze migreren naar Open Register? (j/N): ")
    if response.lower() != 'j':
        print("❌ Geannuleerd")
        sys.exit(0)
    
    # Migreer mutaties
    success_count = 0
    error_count = 0
    
    for i, mutatie in enumerate(mutaties, 1):
        dossier_id = mutatie.get('dossier_id', 'unknown')
        print(f"\n[{i}/{len(mutaties)}] Migreren mutatie {dossier_id}...")
        
        try:
            # Transformeer naar Open Register formaat
            mutatie_data = transform_mutatie_to_openregister(mutatie)
            
            # Maak object aan
            object_uuid = create_object_in_openregister(mutatie_data)
            
            if object_uuid:
                print(f"   ✅ Gemigreerd (UUID: {object_uuid})")
                success_count += 1
            else:
                error_count += 1
        except Exception as e:
            print(f"   ❌ Fout: {e}")
            error_count += 1
    
    print("\n" + "=" * 60)
    print("✅ Migratie voltooid!")
    print("=" * 60)
    print(f"   Succesvol: {success_count}")
    print(f"   Fouten: {error_count}")
    print(f"   Totaal: {len(mutaties)}")

if __name__ == '__main__':
    main()





