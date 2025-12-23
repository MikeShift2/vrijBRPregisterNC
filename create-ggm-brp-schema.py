#!/usr/bin/env python3
"""
Script om GGM-gebaseerd BRP schema aan te maken in OpenRegister
Gebaseerd op Gemeentelijk Gegevensmodel (GGM) - IngeschrevenPersoon objecttype
"""

import subprocess
import json
import uuid

# Database configuratie
DB_USER = "nextcloud_user"
DB_PASS = "nextcloud_secure_pass_2024"
DB_NAME = "nextcloud"
DB_HOST = "nextcloud-db"

# Register ID (vrijBRPpersonen = 2)
REGISTER_ID = 2

# GGM-gebaseerd schema voor IngeschrevenPersoon
# Gebaseerd op RSGB model binnen GGM
GGM_SCHEMA = {
    "title": "GGM IngeschrevenPersoon",
    "description": "GGM-gebaseerd schema voor IngeschrevenPersoon volgens Gemeentelijk Gegevensmodel (RSGB). Dit schema volgt het GGM objecttype IngeschrevenPersoon dat zowel ingezetenen als niet-ingezetenen omvat.",
    "properties": {
        # Identificatie
        "bsn": {
            "type": "string",
            "description": "Burgerservicenummer (BSN) - unieke identificatie van de persoon"
        },
        "anummer": {
            "type": "string",
            "description": "Administratienummer (A-nummer) - unieke identificatie voor niet-ingezetenen"
        },
        
        # Naamgegevens
        "voornamen": {
            "type": "string",
            "description": "Voornamen van de persoon"
        },
        "geslachtsnaam": {
            "type": "string",
            "description": "Geslachtsnaam (achternaam) van de persoon"
        },
        "voorvoegsel": {
            "type": "string",
            "description": "Voorvoegsel van de geslachtsnaam (bijv. 'van', 'de')"
        },
        "aanschrijfwijze": {
            "type": "string",
            "description": "Aanschrijfwijze van de naam"
        },
        
        # Geboortegegevens
        "geboortedatum": {
            "type": "string",
            "description": "Geboortedatum in formaat JJJJ-MM-DD"
        },
        "geboorteplaats": {
            "type": "string",
            "description": "Plaats van geboorte"
        },
        "geboorteland": {
            "type": "string",
            "description": "Land van geboorte (ISO code)"
        },
        
        # Geslacht
        "geslachtsaanduiding": {
            "type": "string",
            "description": "Geslachtsaanduiding: man, vrouw, onbekend",
            "enum": ["man", "vrouw", "onbekend"]
        },
        
        # Nationaliteit
        "nationaliteit": {
            "type": "string",
            "description": "Nationaliteit (ISO code)"
        },
        
        # Verblijfplaats (adres)
        "verblijfplaats_straatnaam": {
            "type": "string",
            "description": "Straatnaam van de verblijfplaats"
        },
        "verblijfplaats_huisnummer": {
            "type": "string",
            "description": "Huisnummer van de verblijfplaats"
        },
        "verblijfplaats_huisnummertoevoeging": {
            "type": "string",
            "description": "Huisnummertoevoeging (bijv. 'A', 'bis')"
        },
        "verblijfplaats_postcode": {
            "type": "string",
            "description": "Postcode van de verblijfplaats"
        },
        "verblijfplaats_woonplaats": {
            "type": "string",
            "description": "Woonplaats van de verblijfplaats"
        },
        "verblijfplaats_land": {
            "type": "string",
            "description": "Land van verblijfplaats (ISO code)"
        },
        
        # Burgerlijke staat
        "burgerlijkeStaat": {
            "type": "string",
            "description": "Burgerlijke staat (ongehuwd, gehuwd, gescheiden, weduwe/weduwnaar, geregistreerd partnerschap, etc.)"
        },
        
        # Inschrijving
        "datumInschrijving": {
            "type": "string",
            "description": "Datum van inschrijving in de gemeente (JJJJ-MM-DD)"
        },
        "datumUitschrijving": {
            "type": "string",
            "description": "Datum van uitschrijving uit de gemeente (JJJJ-MM-DD)"
        },
        "ingeschrevenGemeente": {
            "type": "string",
            "description": "Gemeente waar de persoon is ingeschreven"
        },
        
        # Overlijden
        "overlijdensdatum": {
            "type": "string",
            "description": "Datum van overlijden (JJJJ-MM-DD)"
        },
        "overlijdensplaats": {
            "type": "string",
            "description": "Plaats van overlijden"
        },
        
        # GGM metadata
        "ggm_objecttype": {
            "type": "string",
            "description": "GGM objecttype: IngeschrevenPersoon",
            "default": "IngeschrevenPersoon"
        },
        "ggm_model": {
            "type": "string",
            "description": "GGM model: RSGB (Referentiemodel Stelsel van Gemeentelijke Basisgegevens)",
            "default": "RSGB"
        },
        "ggm_domein": {
            "type": "string",
            "description": "GGM domein: Kern",
            "default": "Kern"
        }
    }
}

def create_schema():
    """Maak GGM BRP schema aan"""
    print("📋 GGM BRP Schema aanmaken...")
    print("")
    
    # Genereer UUID
    schema_uuid = str(uuid.uuid4())
    
    # Maak properties JSON
    properties_json = json.dumps(GGM_SCHEMA["properties"], ensure_ascii=False, indent=2)
    properties_escaped = properties_json.replace("'", "''")
    
    # Maak SQL
    sql = f"""
    INSERT INTO oc_openregister_schemas 
    (uuid, version, title, description, properties, created, updated)
    VALUES 
    (
        '{schema_uuid}',
        '1.0.0',
        '{GGM_SCHEMA["title"]}',
        '{GGM_SCHEMA["description"].replace("'", "''")}',
        '{properties_escaped}',
        NOW(),
        NOW()
    );
    """
    
    # Voer SQL uit
    cmd = [
        'docker', 'exec', DB_HOST,
        'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
        DB_NAME, '-e', sql
    ]
    
    result = subprocess.run(cmd, capture_output=True, text=True)
    
    if result.returncode == 0:
        # Haal schema ID op
        cmd2 = [
            'docker', 'exec', DB_HOST,
            'mariadb', '-u', DB_USER, f'-p{DB_PASS}',
            DB_NAME, '-sN', '-e',
            f"SELECT id FROM oc_openregister_schemas WHERE uuid = '{schema_uuid}';"
        ]
        
        result2 = subprocess.run(cmd2, capture_output=True, text=True)
        schema_id = result2.stdout.strip()
        
        print(f"✅ GGM BRP Schema aangemaakt!")
        print(f"   Schema ID: {schema_id}")
        print(f"   UUID: {schema_uuid}")
        print(f"   Titel: {GGM_SCHEMA['title']}")
        print(f"   Aantal properties: {len(GGM_SCHEMA['properties'])}")
        print("")
        
        return schema_id
    else:
        print(f"❌ Fout bij aanmaken schema: {result.stderr}")
        return None

if __name__ == '__main__':
    schema_id = create_schema()
    if schema_id:
        print(f"✅ Schema aangemaakt met ID: {schema_id}")
        print("")
        print("📝 Volgende stap: Importeer 100 personen met:")
        print(f"   python3 import-ggm-personen.py {schema_id}")







