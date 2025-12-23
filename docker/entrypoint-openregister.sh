#!/bin/bash
set -e

echo "🚀 OpenRegister Auto-Setup Script Starting..."

# Wacht tot Nextcloud volledig geïnitialiseerd is
wait_for_nextcloud() {
    echo "⏳ Wachten tot Nextcloud klaar is..."
    max_attempts=60
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if php /var/www/html/occ status 2>/dev/null | grep -q "installed: true"; then
            echo "✅ Nextcloud is klaar!"
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 5
    done
    
    echo "⚠️  Timeout: Nextcloud is niet klaar na $max_attempts pogingen"
    return 1
}

# Installeer OpenRegister app
install_openregister() {
    echo "📦 OpenRegister installeren..."
    
    # Wacht tot Nextcloud klaar is
    wait_for_nextcloud
    
    # Check of OpenRegister al geïnstalleerd is
    if php /var/www/html/occ app:list 2>/dev/null | grep -q "openregister"; then
        echo "✅ OpenRegister is al geïnstalleerd"
        
        # Check of app enabled is
        if php /var/www/html/occ app:list 2>/dev/null | grep -q "openregister.*enabled"; then
            echo "✅ OpenRegister is al geactiveerd"
            return 0
        fi
    fi
    
    # Download en installeer OpenRegister van App Store
    echo "📥 OpenRegister downloaden van Nextcloud App Store..."
    php /var/www/html/occ app:install openregister 2>&1 || {
        echo "⚠️  App Store installatie gefaald, probeer handmatig via Nextcloud UI"
        echo "   Ga naar: Apps → Niet geïnstalleerd → Zoek 'openregister'"
        return 1
    }
    
    # Activeer de app
    echo "🔌 OpenRegister activeren..."
    php /var/www/html/occ app:enable openregister 2>&1 || {
        echo "⚠️  App activatie gefaald, probeer handmatig via Nextcloud UI"
        return 1
    }
    
    echo "✅ OpenRegister succesvol geïnstalleerd en geactiveerd!"
}

# Installeer dependencies als de app lokaal gemount is
install_dependencies() {
    APP_DIR="/var/www/html/custom_apps/openregister"
    
    if [ -d "$APP_DIR" ]; then
        echo "📚 Dependencies installeren voor OpenRegister..."
        
        # Composer dependencies
        if [ -f "$APP_DIR/composer.json" ]; then
            echo "📦 Composer dependencies installeren..."
            cd "$APP_DIR" && composer install --no-dev --optimize-autoloader 2>&1 || {
                echo "⚠️  Composer installatie gefaald, maar doorgaan..."
            }
        fi
        
        # NPM dependencies
        if [ -f "$APP_DIR/package.json" ]; then
            echo "📦 NPM dependencies installeren..."
            cd "$APP_DIR" && npm install 2>&1 || {
                echo "⚠️  NPM installatie gefaald, maar doorgaan..."
            }
            
            # Build frontend assets
            if [ -f "$APP_DIR/package.json" ]; then
                echo "🔨 Frontend assets bouwen..."
                cd "$APP_DIR" && npm run build 2>&1 || {
                    echo "⚠️  Build gefaald, maar doorgaan..."
                }
            fi
        fi
    fi
}

# Main execution
main() {
    # Wacht even zodat Nextcloud kan starten
    sleep 30
    
    # Installeer OpenRegister
    install_openregister || true
    
    # Installeer dependencies (voor het geval de app lokaal gemount is)
    install_dependencies || true
    
    echo "✅ OpenRegister setup voltooid!"
    echo "🌐 OpenRegister is beschikbaar op: http://localhost:8080"
    echo "📖 Configureer Solr (http://solr:8983) en Ollama (http://ollama:11434) via de Nextcloud admin interface"
}

# Run in background
main > /var/log/openregister-setup.log 2>&1 &

echo "✅ OpenRegister setup script gestart (draait op de achtergrond)"
echo "📋 Logs beschikbaar via: docker exec nextcloud tail -f /var/log/openregister-setup.log"
