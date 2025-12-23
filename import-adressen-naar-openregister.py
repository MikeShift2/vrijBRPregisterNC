#!/usr/bin/env python3
"""
Script om adressen te importeren vanuit probev database naar OpenRegister Adressen schema
"""

import subprocess
import json
import uuid
import sys

# Database configuratie
REGISTER_ID = 2  # vrijBRPpersonen
SCHEMA_ID_ADRESSEN = 7  # Adressen schema
SCHEMA_ID_PERSONEN = 6  # Personen schema (vrijBRP)
SCHEMA_ID_GGM = 21  # GGM IngeschrevenPersoon schema

NEXTCLOUD_DB_HOST = "nextcloud-db"
NEXTCLOUD_DB_USER = "nextcloud_user"
NEXTCLOUD_DB_PASS = "nextcloud_secure_pass_2024"
NEXTCLOUD_DB_NAME = "nextcloud"

POSTGRES_DB_CONTAINER = "mvpvrijbrp2025-db-1"
POSTGRES_USER = "postgres"
POSTGRES_DB = "bevax"


def get_adres_via_bsn(bsn):
    """Haal adres op via BSN uit probev database"""
    try:
        cmd = [
            'docker', 'exec', POSTGRES_DB_CONTAINER,
            'psql', '-U', POSTGRES_USER, '-d', POSTGRES_DB, '-t', '-A',
            '-c', f"""
            SET search_path = probev;
            SELECT 
                COALESCE(vb.pc::text, ''), 
                COALESCE(vb.hnr::text, ''), 
                COALESCE(vb.hnr_t::text, ''), 
                COALESCE(s.straat::text, ''), 
                COALESCE(w.wpl::text, '')
            FROM probev.pl p
            JOIN probev.vb vb ON p.a1 = vb.a1 AND p.a2 = vb.a2 AND p.a3 = vb.a3
            LEFT JOIN probev.straat s ON vb.c_straat = s.c_straat
            LEFT JOIN probev.wpl w ON vb.c_wpl = w.c_wpl
            WHERE p.bsn = '{bsn}' 
            AND (vb.d_geld = -1 OR vb.d_geld > 20200000)
            ORDER BY vb.d_geld DESC
            LIMIT 1;
            """
        ]
        
        result = subprocess.run(cmd, capture_output=True, text=True)
        output = result.stdout.strip()
        
        # Verwijder "SET" output en lege regels
        output = output.replace('SET\n', '').replace('SET', '').strip()
        
        if output and '|' in output:
            parts = output.split('|')
            if len(parts) >= 5:
                postcode = parts[0].strip()
                huisnummer = parts[1].strip()
                huisnummertoevoeging = parts[2].strip()
                straatnaam = parts[3].strip()
                woonplaats = parts[4].strip()
                
                # Alleen retourneren als er daadwerkelijk adresgegevens zijn
                if postcode or straatnaam or woonplaats:
                    adres = {}
                    if postcode:
                        adres['postcode'] = postcode
                    if huisnummer:
                        # Probeer als integer, anders als string
                        try:
                            adres['huisnummer'] = int(huisnummer)
                        except ValueError:
                            adres['huisnummer'] = huisnummer
                    if huisnummertoevoeging:
                        adres['huisnummertoevoeging'] = huisnummertoevoeging
                    if straatnaam:
                        adres['straatnaam'] = straatnaam
                    if woonplaats:
                        adres['woonplaats'] = woonplaats
                    
                    return adres if adres else None
    except Exception as e:
        print(f"Fout bij ophalen adres voor BSN {bsn}: {e}", file=sys.stderr)
    
    return None


def check_adres_exists(bsn):
    """Check of adres al bestaat voor dit BSN"""
    cmd = [
        'docker', 'exec', NEXTCLOUD_DB_HOST,
        'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
        NEXTCLOUD_DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID_ADRESSEN} AND JSON_EXTRACT(object, '$.bsn') = '{bsn}';"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    return result.stdout.strip() and int(result.stdout.strip()) > 0


def get_personen_met_bsn(limit=None):
    """Haal alle personen op met BSN uit OpenRegister"""
    limit_clause = f"LIMIT {limit}" if limit else ""
    
    cmd = [
        'docker', 'exec', NEXTCLOUD_DB_HOST,
        'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
        NEXTCLOUD_DB_NAME, '-sN', '-e',
        f"""
        SELECT DISTINCT JSON_EXTRACT(object, '$.bsn') as bsn
        FROM oc_openregister_objects
        WHERE register = {REGISTER_ID}
        AND schema IN ({SCHEMA_ID_PERSONEN}, {SCHEMA_ID_GGM})
        AND JSON_EXTRACT(object, '$.bsn') IS NOT NULL
        {limit_clause};
        """
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    bsn_list = []
    
    for line in result.stdout.strip().split('\n'):
        bsn = line.strip().strip('"').strip("'")
        if bsn and bsn.isdigit():
            bsn_list.append(bsn)
    
    return bsn_list


def import_adres(bsn, adres):
    """Importeer een adres naar OpenRegister Adressen schema"""
    try:
        # Check of al bestaat
        if check_adres_exists(bsn):
            return False, 'exists'
        
        # Voeg BSN toe aan adres object voor relatie
        adres_object = {
            'bsn': bsn,
            **adres
        }
        
        # Genereer UUID
        obj_uuid = str(uuid.uuid4())
        
        # Maak object JSON
        object_json = json.dumps(adres_object, ensure_ascii=False)
        object_json_escaped = object_json.replace("'", "''")
        
        # Insert in database
        sql = f"""
        INSERT INTO oc_openregister_objects 
        (uuid, version, register, schema, object, created, updated)
        VALUES 
        ('{obj_uuid}', '1.0.0', {REGISTER_ID}, {SCHEMA_ID_ADRESSEN}, '{object_json_escaped}', NOW(), NOW());
        """
        
        insert_cmd = [
            'docker', 'exec', NEXTCLOUD_DB_HOST,
            'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
            NEXTCLOUD_DB_NAME, '-e', sql
        ]
        
        insert_result = subprocess.run(insert_cmd, capture_output=True, text=True)
        
        if insert_result.returncode == 0:
            return True, None
        else:
            return False, insert_result.stderr
    except Exception as e:
        return False, str(e)


def main():
    print("📊 Adressen importeren vanuit probev naar OpenRegister Adressen schema...")
    print("")
    
    # Haal alle personen op met BSN
    print("🔍 Personen ophalen uit OpenRegister...")
    personen_bsn = get_personen_met_bsn()
    print(f"✅ {len(personen_bsn)} personen gevonden met BSN")
    print("")
    
    imported = 0
    skipped = 0
    errors = 0
    no_address = 0
    
    for i, bsn in enumerate(personen_bsn, 1):
        print(f"[{i}/{len(personen_bsn)}] BSN {bsn}...", end=' ')
        
        # Check of adres al bestaat
        if check_adres_exists(bsn):
            print("⚠️  bestaat al")
            skipped += 1
            continue
        
        # Haal adres op uit probev
        adres = get_adres_via_bsn(bsn)
        
        if not adres:
            print("⚠️  geen adres gevonden")
            no_address += 1
            continue
        
        # Importeer adres
        success, error = import_adres(bsn, adres)
        
        if success:
            print("✅ geïmporteerd")
            imported += 1
        else:
            if error == 'exists':
                print("⚠️  bestaat al")
                skipped += 1
            else:
                print(f"❌ fout: {error}")
                errors += 1
        
        # Progress update elke 100 personen
        if i % 100 == 0:
            print("")
            print(f"📊 Progress: {imported} geïmporteerd, {skipped} overgeslagen, {no_address} zonder adres, {errors} fouten")
            print("")
    
    print("")
    print("=" * 60)
    print("✅ Import voltooid!")
    print("")
    print(f"📊 Resultaten:")
    print(f"   ✅ Geïmporteerd: {imported}")
    print(f"   ⚠️  Overgeslagen (bestaat al): {skipped}")
    print(f"   ⚠️  Geen adres gevonden: {no_address}")
    print(f"   ❌ Fouten: {errors}")
    print(f"   📋 Totaal verwerkt: {len(personen_bsn)}")
    print("")


if __name__ == '__main__':
    main()

