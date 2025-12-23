#!/usr/bin/env python3
"""
Script om automatisch een Bevax server toe te voegen aan pgAdmin
"""

import sqlite3
import os
import json
from datetime import datetime

# Pad naar pgAdmin configuratie database
PGADMIN_DB = os.path.expanduser("~/.pgadmin/pgadmin4.db")

def add_bevax_server():
    """Voeg Bevax server toe aan pgAdmin configuratie"""
    
    if not os.path.exists(PGADMIN_DB):
        print(f"❌ pgAdmin database niet gevonden op: {PGADMIN_DB}")
        print("   Zorg dat pgAdmin minimaal één keer is gestart.")
        return False
    
    try:
        conn = sqlite3.connect(PGADMIN_DB)
        cursor = conn.cursor()
        
        # Controleer of server al bestaat
        cursor.execute("""
            SELECT id FROM server 
            WHERE name = 'Bevax Database'
        """)
        
        existing = cursor.fetchone()
        if existing:
            print("⚠️  Server 'Bevax Database' bestaat al in pgAdmin")
            print(f"   Server ID: {existing[0]}")
            print("   Gebruik pgAdmin om de bestaande server te gebruiken of verwijder deze eerst.")
            conn.close()
            return False
        
        # Haal user ID op (meestal 1 voor de eerste gebruiker)
        cursor.execute("SELECT id FROM user ORDER BY id LIMIT 1")
        user_result = cursor.fetchone()
        if not user_result:
            print("❌ Geen gebruiker gevonden in pgAdmin database")
            conn.close()
            return False
        
        user_id = user_result[0]
        
        # Server configuratie
        server_config = {
            "host": "localhost",
            "port": 5432,
            "db": "bevax",  # Maintenance database
            "username": "postgres",
            "password": "postgres",
            "save_password": 1,  # 1 = True in SQLite
            "sslmode": "prefer"
        }
        
        # Connection params als JSON
        connection_params = json.dumps({
            "sslmode": server_config["sslmode"],
            "connect_timeout": 10
        })
        
        # Server groep (meestal 1 voor de standaard groep)
        cursor.execute("SELECT id FROM servergroup ORDER BY id LIMIT 1")
        servergroup_result = cursor.fetchone()
        servergroup_id = servergroup_result[0] if servergroup_result else 1
        
        # Voeg server toe
        cursor.execute("""
            INSERT INTO server (
                user_id,
                servergroup_id,
                name,
                host,
                port,
                maintenance_db,
                username,
                password,
                save_password,
                comment,
                connection_params
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """, (
            user_id,
            servergroup_id,
            "Bevax Database",
            server_config["host"],
            server_config["port"],
            server_config["db"],
            server_config["username"],
            server_config["password"],  # pgAdmin encrypteert dit zelf bij opslaan
            server_config["save_password"],
            "BRP database met probev schema",
            connection_params
        ))
        
        server_id = cursor.lastrowid
        
        conn.commit()
        conn.close()
        
        print("✅ Bevax server succesvol toegevoegd aan pgAdmin!")
        print(f"   Server ID: {server_id}")
        print(f"   Naam: Bevax Database")
        print(f"   Host: {server_config['host']}:{server_config['port']}")
        print(f"   Database: {server_config['db']}")
        print(f"   Username: {server_config['username']}")
        print("")
        print("🔄 Herstart pgAdmin om de nieuwe server te zien.")
        print("   Of klik op 'Refresh' in pgAdmin om de server lijst te vernieuwen.")
        
        return True
        
    except sqlite3.Error as e:
        print(f"❌ Database fout: {e}")
        return False
    except Exception as e:
        print(f"❌ Fout: {e}")
        return False

if __name__ == "__main__":
    print("🔧 Bevax Server toevoegen aan pgAdmin")
    print("=" * 50)
    print("")
    
    # Controleer of PostgreSQL draait (optioneel)
    print("📋 Controleer PostgreSQL verbinding...")
    import subprocess
    import shutil
    
    psql_path = shutil.which("psql")
    if psql_path:
        result = subprocess.run(
            [psql_path, "-h", "localhost", "-U", "postgres", "-d", "postgres", "-c", "SELECT 1;"],
            capture_output=True,
            env={"PGPASSWORD": "postgres"}
        )
        
        if result.returncode != 0:
            print("⚠️  Waarschuwing: Kan niet verbinden met PostgreSQL")
            print("   Zorg dat PostgreSQL draait: brew services start postgresql@17")
            print("   Of controleer of het wachtwoord 'postgres' correct is.")
            print("")
        else:
            # Controleer of bevax database bestaat
            result = subprocess.run(
                [psql_path, "-h", "localhost", "-U", "postgres", "-lqt"],
                capture_output=True,
                text=True,
                env={"PGPASSWORD": "postgres"}
            )
            
            if "bevax" not in result.stdout:
                print("⚠️  Waarschuwing: Database 'bevax' niet gevonden")
                print("   De server wordt toegevoegd, maar je moet eerst de database aanmaken:")
                print("   CREATE DATABASE bevax;")
                print("")
    else:
        print("⚠️  psql niet gevonden in PATH - PostgreSQL controle overgeslagen")
        print("")
    
    print("📝 Voeg server toe aan pgAdmin...")
    success = add_bevax_server()
    
    if success:
        print("")
        print("✅ Klaar! Open pgAdmin en je zou de 'Bevax Database' server moeten zien.")
    else:
        print("")
        print("❌ Server toevoegen mislukt. Zie bovenstaande foutmeldingen.")

