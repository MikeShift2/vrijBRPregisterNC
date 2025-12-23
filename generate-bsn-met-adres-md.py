#!/usr/bin/env python3
"""
Script om een markdown bestand te genereren met BSNs die adresgegevens hebben
"""

import subprocess
import json
import sys

REGISTER_ID = 2
SCHEMA_ID_ADRESSEN = 7

NEXTCLOUD_DB_HOST = "nextcloud-db"
NEXTCLOUD_DB_USER = "nextcloud_user"
NEXTCLOUD_DB_PASS = "nextcloud_secure_pass_2024"
NEXTCLOUD_DB_NAME = "nextcloud"


def get_bsns_met_adres(limit=100):
    """Haal BSNs op met adresgegevens"""
    cmd = [
        'docker', 'exec', NEXTCLOUD_DB_HOST,
        'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
        NEXTCLOUD_DB_NAME, '-sN', '-e',
        f"""
        SELECT object 
        FROM oc_openregister_objects 
        WHERE register = {REGISTER_ID} 
        AND schema = {SCHEMA_ID_ADRESSEN}
        ORDER BY JSON_EXTRACT(object, '$.bsn')
        LIMIT {limit};
        """
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    bsns = []
    
    for line in result.stdout.strip().split('\n'):
        line = line.strip()
        if not line:
            continue
        
        try:
            obj = json.loads(line)
            bsn = obj.get('bsn', '')
            if bsn and len(str(bsn)) == 9:
                bsns.append({
                    'bsn': str(bsn),
                    'straatnaam': obj.get('straatnaam', ''),
                    'huisnummer': str(obj.get('huisnummer', '')) if obj.get('huisnummer') else '',
                    'huisnummertoevoeging': obj.get('huisnummertoevoeging', ''),
                    'postcode': obj.get('postcode', ''),
                    'woonplaats': obj.get('woonplaats', '')
                })
        except json.JSONDecodeError:
            continue
    
    return bsns


def get_total_count():
    """Haal totaal aantal adressen op"""
    cmd = [
        'docker', 'exec', NEXTCLOUD_DB_HOST,
        'mariadb', '-u', NEXTCLOUD_DB_USER, f'-p{NEXTCLOUD_DB_PASS}',
        NEXTCLOUD_DB_NAME, '-sN', '-e',
        f"SELECT COUNT(*) FROM oc_openregister_objects WHERE register = {REGISTER_ID} AND schema = {SCHEMA_ID_ADRESSEN};"
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    return int(result.stdout.strip()) if result.stdout.strip() else 0


def main():
    print("📊 BSNs met adresgegevens ophalen...")
    
    total_count = get_total_count()
    print(f"✅ Totaal aantal adressen: {total_count}")
    
    # Haal eerste 100 op voor het markdown bestand
    bsns = get_bsns_met_adres(limit=100)
    print(f"✅ {len(bsns)} BSNs opgehaald voor markdown bestand")
    
    # Genereer markdown
    md_content = []
    md_content.append("# Test BSNs met Adresgegevens")
    md_content.append("")
    md_content.append(f"**Totaal aantal BSNs met adres:** {total_count}")
    md_content.append("")
    md_content.append("Deze BSNs hebben adresgegevens in het OpenRegister Adressen schema (schema ID 7).")
    md_content.append("Ze kunnen gebruikt worden om te testen of de Haal Centraal API correct adresgegevens ophaalt.")
    md_content.append("")
    md_content.append("## Lijst van BSNs met adres (eerste 100)")
    md_content.append("")
    md_content.append("| BSN | Straatnaam | Huisnummer | Postcode | Woonplaats |")
    md_content.append("|-----|------------|------------|----------|------------|")
    
    for bsn_data in bsns:
        huisnr = bsn_data['huisnummer']
        if bsn_data.get('huisnummertoevoeging'):
            huisnr += bsn_data['huisnummertoevoeging']
        
        md_content.append(
            f"| {bsn_data['bsn']} | {bsn_data['straatnaam']} | {huisnr} | "
            f"{bsn_data['postcode']} | {bsn_data['woonplaats']} |"
        )
    
    md_content.append("")
    md_content.append("## Alleen BSNs (voor snelle copy-paste)")
    md_content.append("")
    
    for bsn_data in bsns:
        md_content.append(f"- `{bsn_data['bsn']}`")
    
    # Schrijf naar bestand
    output_file = 'test-bsn-met-adres.md'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(md_content))
    
    print(f"✅ Markdown bestand gegenereerd: {output_file}")
    print(f"   - {len(bsns)} BSNs met adresgegevens")
    print(f"   - Totaal {total_count} adressen in database")


if __name__ == '__main__':
    main()







