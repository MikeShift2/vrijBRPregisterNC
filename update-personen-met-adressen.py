#!/usr/bin/env python3
"""
Script om bestaande personen in OpenRegister bij te werken met adresgegevens
"""

import subprocess
import json
import sys

SCHEMA_ID = 6
REGISTER_ID = 2

def get_personen_zonder_adres():
    """Haal personen op die nog geen adres hebben"""
    cmd = [
        'docker', 'exec', 'nextcloud-db',
        'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
        'nextcloud', '-sN', '-e',
        f"SELECT uuid, object FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID} AND (JSON_EXTRACT(object, '$.adres') IS NULL OR JSON_EXTRACT(object, '$.adres') = 'null');"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    personen = []
    
    for line in result.stdout.strip().split('\n'):
        if not line or '\t' not in line:
            continue
        
        parts = line.split('\t', 1)
        if len(parts) == 2:
            uuid = parts[0]
            object_json = parts[1]
            try:
                obj_data = json.loads(object_json)
                personen.append({'uuid': uuid, 'object': obj_data})
            except:
                continue
    
    return personen

def get_adres_voor_persoon(persoon_id):
    """Haal adresgegevens op voor een persoon uit probev database"""
    try:
        if not persoon_id:
            return None
        
        id_str = str(persoon_id)
        
        # Probeer verschillende formaten
        if len(id_str) >= 10:
            a1 = int(id_str[0:3])
            a2 = int(id_str[3:7])
            a3 = int(id_str[7:10])
            
            cmd = [
                'docker', 'exec', 'mvpvrijbrp2025-db-1',
                'psql', '-U', 'postgres', '-d', 'bevax', '-t', '-A',
                '-c', f'SELECT vb.pc, vb.hnr::text, COALESCE(NULLIF(vb.hnr_t, \'\'), \'\'), COALESCE(NULLIF(s.straat, \'\'), \'\'), COALES(NULLIF(w.wpl, \'\'), \'\') FROM probev.vb vb LEFT JOIN probev.straat s ON vb.c_straat = s.c_straat LEFT JOIN probev.wpl w ON vb.c_wpl = w.c_wpl WHERE vb.a1 = {a1} AND vb.a2 = {a2} AND vb.a3 = {a3} AND (vb.d_geld = -1 OR vb.d_geld > 20200000) ORDER BY vb.d_geld DESC LIMIT 1;'
            ]
        else:
            # Korte ID - probeer direct op BSN
            return None
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        output = result.stdout.strip().replace('SET\n', '').strip()
        
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
        print(f"Fout bij ophalen adres voor ID {persoon_id}: {e}")
    
    return None

def get_adres_via_bsn(bsn):
    """Haal adres op via BSN uit probev database"""
    try:
        cmd = [
            'docker', 'exec', 'mvpvrijbrp2025-db-1',
            'psql', '-U', 'postgres', '-d', 'bevax', '-t', '-A',
            '-c', f"SELECT vb.pc, vb.hnr::text, COALESCE(NULLIF(vb.hnr_t, ''), ''), COALESCE(NULLIF(s.straat, ''), ''), COALESCE(NULLIF(w.wpl, ''), '') FROM probev.\"Personen\" p LEFT JOIN probev.vb vb ON CAST(SUBSTRING(p.id::text FROM 1 FOR 3) AS INTEGER) = vb.a1 AND CAST(SUBSTRING(p.id::text FROM 4 FOR 4) AS INTEGER) = vb.a2 AND CAST(SUBSTRING(p.id::text FROM 8 FOR 3) AS INTEGER) = vb.a3 LEFT JOIN probev.straat s ON vb.c_straat = s.c_straat LEFT JOIN probev.wpl w ON vb.c_wpl = w.c_wpl WHERE p.bsn = '{bsn}' AND (vb.d_geld = -1 OR vb.d_geld > 20200000) ORDER BY vb.d_geld DESC LIMIT 1;"
        ]
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        output = result.stdout.strip().replace('SET\n', '').strip()
        
        if output and '|' in output:
            parts = output.split('|')
            if len(parts) >= 5 and parts[0].strip():
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
        print(f"Fout bij ophalen adres voor BSN {bsn}: {e}")
    
    return None

def update_persoon_met_adres(uuid, object_data, adres):
    """Update een persoon met adresgegevens"""
    try:
        # Voeg adres toe aan object
        object_data['adres'] = adres
        object_json = json.dumps(object_data, ensure_ascii=False)
        object_json_escaped = object_json.replace("'", "''")
        
        sql = f"UPDATE oc_openregister_objects SET object = '{object_json_escaped}', updated = NOW() WHERE uuid = '{uuid}';"
        
        cmd = [
            'docker', 'exec', 'nextcloud-db',
            'mariadb', '-u', 'nextcloud_user', '-pnextcloud_secure_pass_2024',
            'nextcloud', '-e', sql
        ]
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        return result.returncode == 0
    except Exception as e:
        print(f"Fout bij updaten persoon {uuid}: {e}")
        return False

def main():
    print("📊 Personen bijwerken met adresgegevens...")
    print("")
    
    # Haal personen op zonder adres
    print("⏳ Personen zonder adres ophalen...")
    personen = get_personen_zonder_adres()
    print(f"✅ {len(personen)} personen gevonden zonder adres")
    print("")
    
    if len(personen) == 0:
        print("✅ Alle personen hebben al adresgegevens!")
        return
    
    updated = 0
    errors = 0
    
    for i, persoon in enumerate(personen, 1):
        uuid = persoon['uuid']
        obj_data = persoon['object']
        bsn = obj_data.get('bsn')
        persoon_id = obj_data.get('id')
        
        if not bsn:
            continue
        
        print(f"[{i}/{len(personen)}] BSN {bsn}...", end=' ')
        
        # Probeer adres op te halen
        adres = None
        
        # Eerst via persoon ID
        if persoon_id:
            adres = get_adres_voor_persoon(persoon_id)
        
        # Als dat niet werkt, probeer via BSN
        if not adres:
            adres = get_adres_via_bsn(bsn)
        
        if adres:
            if update_persoon_met_adres(uuid, obj_data, adres):
                updated += 1
                print(f"✅ Adres toegevoegd")
            else:
                errors += 1
                print(f"❌ Update gefaald")
        else:
            print(f"⚠️  Geen adres gevonden")
        
        if i % 100 == 0:
            print(f"\n📊 Voortgang: {updated} bijgewerkt, {errors} fouten\n")
    
    print("")
    print("✅ Update voltooid!")
    print(f"Bijgewerkt: {updated}")
    print(f"Fouten: {errors}")
    print(f"Geen adres gevonden: {len(personen) - updated - errors}")

if __name__ == '__main__':
    main()







