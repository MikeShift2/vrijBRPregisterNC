#!/usr/bin/env python3
"""
Script om alle personen uit probev database te importeren naar OpenRegister
"""

import subprocess
import json
import uuid
import sys

SCHEMA_ID = 6
REGISTER_ID = 2
BATCH_SIZE = 500  # Kleinere batches voor betrouwbaarheid

def get_count():
    """Haal totaal aantal personen op"""
    cmd = [
        'docker', 'exec', 'mvpvrijbrp2025-db-1',
        'psql', '-U', 'postgres', '-d', 'bevax', '-t', '-A',
        '-c', 'SELECT COUNT(*) FROM probev."Personen";'
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    count = int(result.stdout.strip().split('\n')[-1])
    return count

def get_batch(offset, limit):
    """Haal batch personen op als JSON"""
    cmd = [
        'docker', 'exec', 'mvpvrijbrp2025-db-1',
        'psql', '-U', 'postgres', '-d', 'bevax', '-t', '-A',
        '-c', f'SELECT json_agg(row_to_json(t)) FROM (SELECT * FROM probev."Personen" ORDER BY id LIMIT {limit} OFFSET {offset}) t;'
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    # Zoek JSON array in output
    output = result.stdout.strip()
    # Verwijder "SET" en andere noise
    lines = [line for line in output.split('\n') if line.strip() and not line.strip().startswith('SET')]
    
    if not lines:
        return None
    
    # Pak de laatste regel die eruit ziet als JSON
    json_str = lines[-1]
    
    try:
        data = json.loads(json_str)
        return data if isinstance(data, list) else None
    except json.JSONDecodeError:
        # Probeer eerste regel die begint met [
        for line in lines:
            if line.strip().startswith('['):
                try:
                    data = json.loads(line.strip())
                    return data if isinstance(data, list) else None
                except:
                    continue
        return None

def check_exists(bsn):
    """Check of BSN al bestaat"""
    if not bsn:
        return False
    
    cmd = [
        'docker', 'exec', 'nextcloud-db',
        'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
        'nextcloud', '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID} AND JSON_EXTRACT(object, '$.bsn') = '{bsn}';"
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    return result.stdout.strip() and int(result.stdout.strip()) > 0

def get_adres_voor_persoon(persoon_id):
    """Haal adresgegevens op voor een persoon"""
    try:
        # Parse persoon ID om a1, a2, a3 te krijgen
        # ID kan verschillende formaten hebben
        if not persoon_id or len(str(persoon_id)) < 3:
            return None
        
        id_str = str(persoon_id)
        
        # Probeer verschillende formaten
        if len(id_str) >= 10:
            # Formaat: a1a2a3 (bijv. "101549823")
            a1 = int(id_str[0:3])
            a2 = int(id_str[3:7])
            a3 = int(id_str[7:10])
        else:
            # Korte ID, probeer als a1
            a1 = int(id_str)
            a2 = None
            a3 = None
        
        # Haal adres op
        if a2 and a3:
            cmd = [
                'docker', 'exec', 'mvpvrijbrp2025-db-1',
                'psql', '-U', 'postgres', '-d', 'bevax', '-t', '-A',
                '-c', f'SELECT vb.pc, vb.hnr::text, COALESCE(NULLIF(vb.hnr_t, \'\'), \'\'), COALESCE(NULLIF(s.straat, \'\'), \'\'), COALESCE(NULLIF(w.wpl, \'\'), \'\') FROM probev.vb vb LEFT JOIN probev.straat s ON vb.c_straat = s.c_straat LEFT JOIN probev.wpl w ON vb.c_wpl = w.c_wpl WHERE vb.a1 = {a1} AND vb.a2 = {a2} AND vb.a3 = {a3} AND (vb.d_geld = -1 OR vb.d_geld > 20200000) ORDER BY vb.d_geld DESC LIMIT 1;'
            ]
        else:
            cmd = [
                'docker', 'exec', 'mvpvrijbrp2025-db-1',
                'psql', '-U', 'postgres', '-d', 'bevax', '-t', '-A',
                '-c', f'SELECT vb.pc, vb.hnr::text, COALESCE(NULLIF(vb.hnr_t, \'\'), \'\'), COALESCE(NULLIF(s.straat, \'\'), \'\'), COALESCE(NULLIF(w.wpl, \'\'), \'\') FROM probev.vb vb LEFT JOIN probev.straat s ON vb.c_straat = s.c_straat LEFT JOIN probev.wpl w ON vb.c_wpl = w.c_wpl WHERE vb.a1 = {a1} AND (vb.d_geld = -1 OR vb.d_geld > 20200000) ORDER BY vb.d_geld DESC LIMIT 1;'
            ]
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        output = result.stdout.strip()
        
        # Verwijder "SET" output
        output = output.replace('SET\n', '').strip()
        
        if output and '|' in output:
            parts = output.split('|')
            if len(parts) >= 5:
                postcode = parts[0].strip()
                huisnummer = parts[1].strip()
                huisnummertoevoeging = parts[2].strip()
                straatnaam = parts[3].strip()
                woonplaats = parts[4].strip()
                
                adres = {}
                if postcode:
                    adres['postcode'] = postcode
                if huisnummer:
                    adres['huisnummer'] = huisnummer
                if huisnummertoevoeging:
                    adres['huisnummertoevoeging'] = huisnummertoevoeging
                if straatnaam:
                    adres['straatnaam'] = straatnaam
                if woonplaats:
                    adres['woonplaats'] = woonplaats
                
                return adres if adres else None
    except Exception as e:
        print(f"Fout bij ophalen adres: {e}")
    
    return None

def import_person(persoon):
    """Importeer een persoon"""
    bsn = persoon.get('bsn')
    
    # Check of al bestaat
    if bsn and check_exists(bsn):
        return False, 'exists'
    
    # Haal adresgegevens op
    persoon_id = persoon.get('id')
    adres = get_adres_voor_persoon(persoon_id)
    
    # Voeg adres toe aan persoon object
    if adres:
        persoon['adres'] = adres
    
    # Genereer UUID
    obj_uuid = str(uuid.uuid4())
    
    # Maak object JSON
    object_json = json.dumps(persoon, ensure_ascii=False)
    
    # Escapen voor MySQL
    object_json_escaped = object_json.replace("'", "''")
    
    # Maak SQL
    sql = f"INSERT INTO oc_openregister_objects (uuid, version, register, schema, object, created, updated) VALUES ('{obj_uuid}', '0.0.1', '{REGISTER_ID}', '{SCHEMA_ID}', '{object_json_escaped}', NOW(), NOW());"
    
    # Voer SQL uit
    cmd = [
        'docker', 'exec', 'nextcloud-db',
        'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
        'nextcloud', '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        return True, None
    else:
        return False, result.stderr

def main():
    print("📊 Personen importeren van probev database naar OpenRegister...")
    print("")
    
    # Haal totaal aantal op
    print("⏳ Totaal aantal personen ophalen...")
    total_count = get_count()
    print(f"✅ Totaal aantal personen: {total_count}")
    print("")
    
    # Check hoeveel er al zijn
    cmd = [
        'docker', 'exec', 'nextcloud-db',
        'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
        'nextcloud', '-sN', '-e',
        f'SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID};'
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    existing = int(result.stdout.strip()) if result.stdout.strip() else 0
    print(f"Aantal personen al in OpenRegister: {existing}")
    print("")
    
    imported = 0
    skipped = 0
    errors = 0
    offset = 0
    
    while offset < total_count:
        print(f"📥 Batch ophalen: offset {offset}, limit {BATCH_SIZE}...")
        
        batch = get_batch(offset, BATCH_SIZE)
        
        if not batch or len(batch) == 0:
            print("Geen data meer gevonden, stoppen.")
            break
        
        print(f"📋 Batch grootte: {len(batch)} personen")
        
        batch_imported = 0
        batch_skipped = 0
        batch_errors = 0
        
        for persoon in batch:
            success, error = import_person(persoon)
            
            if success:
                batch_imported += 1
                imported += 1
                if imported % 100 == 0:
                    print(f"✅ {imported} personen geïmporteerd...")
            elif error == 'exists':
                batch_skipped += 1
                skipped += 1
            else:
                batch_errors += 1
                errors += 1
                if errors <= 10:
                    print(f"❌ Fout: {error[:200]}")
        
        print(f"✅ Batch voltooid: {batch_imported} geïmporteerd, {batch_skipped} overgeslagen, {batch_errors} fouten")
        print("")
        
        offset += BATCH_SIZE
    
    print("✅ Import voltooid!")
    print(f"Totaal geïmporteerd: {imported}")
    print(f"Overgeslagen (al bestaand): {skipped}")
    print(f"Fouten: {errors}")
    print("")
    
    # Controleer eindresultaat
    cmd = [
        'docker', 'exec', 'nextcloud-db',
        'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
        'nextcloud', '-sN', '-e',
        f'SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID};'
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    final_count = int(result.stdout.strip()) if result.stdout.strip() else 0
    print(f"Totaal personen in OpenRegister: {final_count}")

if __name__ == '__main__':
    main()

