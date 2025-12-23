#!/usr/bin/env python3
"""
Nextcloud PostgreSQL Database Setup Script
Maakt automatisch de database en gebruiker aan
"""

import getpass
import psycopg2
import sys
import os
from psycopg2 import sql

# Database configuratie uit .env lezen
def load_env():
    env_vars = {}
    if os.path.exists('.env'):
        with open('.env', 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    key, value = line.split('=', 1)
                    env_vars[key.strip()] = value.strip()
    return env_vars

def main():
    print("🔧 Nextcloud PostgreSQL Database Setup")
    print("=" * 40)
    print()
    
    # Laad configuratie
    env = load_env()
    db_name = env.get('POSTGRES_DB', 'nextcloud')
    db_user = env.get('POSTGRES_USER', 'nextcloud_user')
    db_password = env.get('POSTGRES_PASSWORD', 'nextcloud_secure_pass_2024')
    
    print(f"📋 Configuratie:")
    print(f"   Database: {db_name}")
    print(f"   Gebruiker: {db_user}")
    print()
    
    # Vraag om PostgreSQL admin credentials
    print("🔐 PostgreSQL Admin Credentials:")
    # Gebruik environment variables als beschikbaar, anders vraag interactief
    if os.getenv('PGUSER') and os.getenv('PGPASSWORD'):
        admin_user = os.getenv('PGUSER', 'postgres')
        admin_password = os.getenv('PGPASSWORD')
        print(f"   Gebruiker: {admin_user} (uit environment)")
        print(f"   Wachtwoord: {'*' * len(admin_password)} (uit environment)")
    else:
        try:
            admin_user = os.getenv('PGUSER', input("   Gebruiker (standaard: postgres): ").strip() or "postgres")
            admin_password = os.getenv('PGPASSWORD', getpass.getpass("   Wachtwoord: "))
        except (EOFError, KeyboardInterrupt):
            print("\n❌ Interactieve invoer niet beschikbaar. Gebruik environment variables:")
            print("   PGUSER=postgres PGPASSWORD=wachtwoord python3 setup-db.py")
            sys.exit(1)
    print()
    
    try:
        # Verbind met PostgreSQL
        print("🔄 Verbinden met PostgreSQL...")
        conn = psycopg2.connect(
            host="localhost",
            port=5432,
            user=admin_user,
            password=admin_password,
            database="postgres"
        )
        conn.autocommit = True
        cur = conn.cursor()
        print("✅ Verbonden met PostgreSQL")
        print()
        
        # Controleer of database al bestaat
        cur.execute("SELECT 1 FROM pg_database WHERE datname = %s", (db_name,))
        if cur.fetchone():
            print(f"⚠️  Database '{db_name}' bestaat al. Overslaan...")
        else:
            print(f"🗄️  Database '{db_name}' aanmaken...")
            cur.execute(sql.SQL("CREATE DATABASE {}").format(sql.Identifier(db_name)))
            print(f"✅ Database '{db_name}' aangemaakt")
        print()
        
        # Controleer of gebruiker al bestaat
        cur.execute("SELECT 1 FROM pg_roles WHERE rolname = %s", (db_user,))
        if cur.fetchone():
            print(f"⚠️  Gebruiker '{db_user}' bestaat al. Wachtwoord bijwerken...")
            cur.execute(
                sql.SQL("ALTER USER {} WITH PASSWORD %s").format(sql.Identifier(db_user)),
                (db_password,)
            )
        else:
            print(f"👤 Gebruiker '{db_user}' aanmaken...")
            cur.execute(
                sql.SQL("CREATE USER {} WITH PASSWORD %s").format(sql.Identifier(db_user)),
                (db_password,)
            )
            print(f"✅ Gebruiker '{db_user}' aangemaakt")
        print()
        
        # Rechten toekennen
        print("🔑 Rechten toekennen...")
        cur.execute(
            sql.SQL("GRANT ALL PRIVILEGES ON DATABASE {} TO {}").format(
                sql.Identifier(db_name),
                sql.Identifier(db_user)
            )
        )
        cur.execute(
            sql.SQL("ALTER DATABASE {} OWNER TO {}").format(
                sql.Identifier(db_name),
                sql.Identifier(db_user)
            )
        )
        print("✅ Rechten toegekend")
        print()
        
        cur.close()
        conn.close()
        
        print("✨ Database setup voltooid!")
        print()
        print("Nextcloud zou nu moeten kunnen verbinden.")
        print("Controleer de status met: docker-compose logs -f nextcloud")
        print()
        
    except psycopg2.OperationalError as e:
        print(f"❌ Fout bij verbinden met PostgreSQL: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Onverwachte fout: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()

