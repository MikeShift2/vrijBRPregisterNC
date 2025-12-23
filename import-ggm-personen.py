#!/usr/bin/env python3
"""
Script om 100 personen uit probev database te importeren naar GGM BRP schema
"""

import subprocess
import json
import sys
import uuid
from datetime import datetime

# Database configuratie
NEXTCLOUD_DB_USER = "nextcloud_user"
NEXTCLOUD_DB_PASS = "nextcloud_secure_pass_2024"
NEXTCLOUD_DB_NAME = "nextcloud"
NEXTCLOUD_DB_HOST = "nextcloud-db"

POSTGRES_DB_CONTAINER = "mvpvrijbrp2025-db-1"
POSTGRES_USER = "postgres"
POSTGRES_DB = "bevax"
POSTGRES_SCHEMA = "probev"

REGISTER_ID = 2  # vrijBRPpersonen

def get_schema_id():
    """Haal schema ID op van command line of zoek GGM schema"""
    if len(sys.argv) > 1:
        return int(sys.argv[1])
    
    # Zoek GGM schema
    cmd = [
        'docker', 'exec', NEXTCLOUD_DB_HOST,
        'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
        NEXTCLOUD_DB_NAME, '-sN', '-e',
        "SELECT id FROM oc_openregister_schemas WHERE title LIKE 'GGM%IngeschrevenPersoon' LIMIT 1;"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    schema_id = result.stdout.strip()
    
    if schema_id:
        return int(schema_id)
    
    print("❌ Geen GGM schema gevonden. Maak eerst schema aan met:")
    print("   python3 create-ggm-brp-schema.py")
    sys.exit(1)

def format_datum(datum_str):
    """Format datum van JJJJMMDD naar JJJJ-MM-DD"""
    if not datum_str or len(str(datum_str)) != 8:
        return None
    datum_str = str(datum_str)
    return f"{datum_str[0:4]}-{datum_str[4:6]}-{datum_str[6:8]}"

def map_geslacht(geslacht):
    """Map geslacht code naar GGM waarde"""
    mapping = {
        'M': 'man',
        'V': 'vrouw',
        'O': 'onbekend'
    }
    return mapping.get(geslacht, 'onbekend')

def get_adres_via_bsn(bsn):
    """Haal adres op via BSN uit probev database"""
    try:
        cmd = [
            'docker', 'exec', POSTGRES_DB_CONTAINER,
            'psql', '-U', POSTGRES_USER, '-d', POSTGRES_DB, '-t', '-A',
            '-c', f"SELECT vb.pc, vb.hnr::text, COALESCE(NULLIF(vb.hnr_t, ''), ''), COALESCE(NULLIF(s.straat, ''), ''), COALESCE(NULLIF(w.wpl, ''), '') FROM probev.\"Personen\" p LEFT JOIN probev.vb vb ON CAST(SUBSTRING(p.id::text FROM 1 FOR 3) AS INTEGER) = vb.a1 AND CAST(SUBSTRING(p.id::text FROM 4 FOR 4) AS INTEGER) = vb.a2 AND CAST(SUBSTRING(p.id::text FROM 8 FOR 3) AS INTEGER) = vb.a3 LEFT JOIN probev.straat s ON vb.c_straat = s.c_straat LEFT JOIN probev.wpl w ON vb.c_wpl = w.c_wpl WHERE p.bsn = '{bsn}' AND (vb.d_geld = -1 OR vb.d_geld > 20200000) ORDER BY vb.d_geld DESC LIMIT 1;"
        ]
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        output = result.stdout.strip().replace('SET\n', '').strip()
        
        if output and '|' in output:
            parts = output.split('|')
            if len(parts) >= 5 and parts[0].strip():
                return {
                    'postcode': parts[0].strip(),
                    'huisnummer': parts[1].strip(),
                    'huisnummertoevoeging': parts[2].strip(),
                    'straatnaam': parts[3].strip(),
                    'woonplaats': parts[4].strip()
                }
    except Exception as e:
        print(f"Fout bij ophalen adres voor BSN {bsn}: {e}")
    
    return None

def transform_to_ggm(persoon, adres=None):
    """Transformeer probev persoon naar GGM formaat"""
    ggm_object = {
        # GGM metadata
        'ggm_objecttype': 'IngeschrevenPersoon',
        'ggm_model': 'RSGB',
        'ggm_domein': 'Kern',
        
        # Identificatie
        'bsn': persoon.get('bsn'),
        'anummer': persoon.get('anr'),
        
        # Naamgegevens
        'voornamen': persoon.get('voornamen'),
        'geslachtsnaam': persoon.get('geslachtsnaam'),
        'voorvoegsel': persoon.get('voorvoegsel'),
        
        # Geboortegegevens
        'geboortedatum': format_datum(persoon.get('geboortedatum')),
        
        # Geslacht
        'geslachtsaanduiding': map_geslacht(persoon.get('geslacht')),
        
        # Burgerlijke staat
        'burgerlijkeStaat': persoon.get('burgerlijkeStaat'),
    }
    
    # Voeg adres toe als beschikbaar
    if adres:
        ggm_object['verblijfplaats_straatnaam'] = adres.get('straatnaam')
        ggm_object['verblijfplaats_huisnummer'] = adres.get('huisnummer')
        if adres.get('huisnummertoevoeging'):
            ggm_object['verblijfplaats_huisnummertoevoeging'] = adres.get('huisnummertoevoeging')
        ggm_object['verblijfplaats_postcode'] = adres.get('postcode')
        ggm_object['verblijfplaats_woonplaats'] = adres.get('woonplaats')
        ggm_object['verblijfplaats_land'] = 'NL'  # Default Nederland
    
    # Verwijder None/null waarden
    return {k: v for k, v in ggm_object.items() if v is not None and v != ''}

def import_personen(schema_id, limit=100):
    """Importeer personen uit probev naar GGM schema"""
    print(f"📊 {limit} personen importeren naar GGM schema (ID: {schema_id})...")
    print("")
    
    # Haal personen op uit probev
    cmd = [
        'docker', 'exec', POSTGRES_DB_CONTAINER,
        'psql', '-U', POSTGRES_USER, '-d', POSTGRES_DB, '-t', '-A',
        '-c', f"SELECT json_agg(row_to_json(t)) FROM (SELECT * FROM probev.\"Personen\" ORDER BY RANDOM() LIMIT {limit}) t;"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    output = result.stdout.strip().replace('SET\n', '').strip()
    
    if not output or output == '(0 rows)':
        print("❌ Geen personen gevonden in probev database")
        return
    
    try:
        personen = json.loads(output)
    except json.JSONDecodeError as e:
        print(f"❌ JSON parse error: {e}")
        print(f"Raw output: {output[:200]}")
        return
    
    if not isinstance(personen, list):
        print("❌ Geen array ontvangen van PostgreSQL")
        return
    
    imported = 0
    errors = 0
    
    for i, persoon in enumerate(personen, 1):
        bsn = persoon.get('bsn')
        if not bsn:
            continue
        
        print(f"[{i}/{len(personen)}] BSN {bsn}...", end=' ')
        
        # Check of al bestaat
        check_cmd = [
            'docker', 'exec', NEXTCLOUD_DB_HOST,
            'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
            NEXTCLOUD_DB_NAME, '-sN', '-e',
            f"SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {schema_id} AND JSON_EXTRACT(object, '$.bsn') = '{bsn}';"
        ]
        
        check_result = subprocess.run(check_cmd, capture_output=True, text=True)
        if check_result.stdout.strip() and int(check_result.stdout.strip()) > 0:
            print("⚠️  bestaat al")
            continue
        
        # Haal adres op
        adres = get_adres_via_bsn(bsn)
        
        # Transformeer naar GGM formaat
        ggm_object = transform_to_ggm(persoon, adres)
        
        # Genereer UUID
        obj_uuid = str(uuid.uuid4())
        
        # Maak object JSON
        object_json = json.dumps(ggm_object, ensure_ascii=False)
        object_json_escaped = object_json.replace("'", "''")
        
        # Insert in database
        sql = f"""
        INSERT INTO oc_openregister_objects 
        (uuid, version, register, schema, object, created, updated)
        VALUES 
        ('{obj_uuid}', '1.0.0', {REGISTER_ID}, {schema_id}, '{object_json_escaped}', NOW(), NOW());
        """
        
        insert_cmd = [
            'docker', 'exec', NEXTCLOUD_DB_HOST,
            'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
            NEXTCLOUD_DB_NAME, '-e', sql
        ]
        
        insert_result = subprocess.run(insert_cmd, capture_output=True, text=True)
        
        if insert_result.returncode == 0:
            imported += 1
            print("✅")
        else:
            errors += 1
            print(f"❌ {insert_result.stderr[:50]}")
        
        if i % 10 == 0:
            print(f"   Voortgang: {imported} geïmporteerd, {errors} fouten")
    
    print("")
    print("✅ Import voltooid!")
    print(f"   Geïmporteerd: {imported}")
    print(f"   Fouten: {errors}")
    print(f"   Overgeslagen (bestond al): {len(personen) - imported - errors}")

if __name__ == '__main__':
    schema_id = get_schema_id()
    limit = int(sys.argv[2]) if len(sys.argv) > 2 else 100
    import_personen(schema_id, limit)







