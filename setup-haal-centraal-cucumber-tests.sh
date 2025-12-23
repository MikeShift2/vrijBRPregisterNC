#!/bin/bash
# Setup script voor Haal Centraal Cucumber tests

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_DIR="${SCRIPT_DIR}/haal-centraal-cucumber-tests"
API_URL="${API_URL:-http://localhost:8080/apps/openregister}"

echo "🚀 Setup Haal Centraal Cucumber Tests"
echo "======================================"
echo ""

# Check of Ruby geïnstalleerd is
if ! command -v ruby &> /dev/null; then
    echo "❌ Ruby is niet geïnstalleerd"
    echo "📦 Installeer Ruby via: brew install ruby (macOS) of apt-get install ruby (Linux)"
    exit 1
fi

echo "✅ Ruby gevonden: $(ruby --version)"
echo ""

# Check of Bundler geïnstalleerd is
if ! command -v bundle &> /dev/null; then
    echo "📦 Bundler niet gevonden, installeren..."
    gem install bundler
fi

echo "✅ Bundler gevonden: $(bundle --version)"
echo ""

# Clone of update Haal Centraal Cucumber tests
if [ -d "$TEST_DIR" ]; then
    echo "📁 Test directory bestaat al, updaten..."
    cd "$TEST_DIR"
    git pull || echo "⚠️  Git pull gefaald, doorgaan met bestaande versie"
else
    echo "📥 Clonen Haal Centraal Cucumber tests..."
    
    # Probeer verschillende mogelijke repositories
    if git clone https://github.com/VNG-Realisatie/Haal-Centraal-BRP-bevragen.git "$TEST_DIR" 2>/dev/null; then
        echo "✅ Repository gecloned"
    elif git clone https://github.com/VNG-Realisatie/haal-centraal-brp-bevragen.git "$TEST_DIR" 2>/dev/null; then
        echo "✅ Repository gecloned"
    elif git clone https://github.com/VNG-Realisatie/Haal-Centraal-common.git "$TEST_DIR" 2>/dev/null; then
        echo "✅ Repository gecloned"
    else
        echo "❌ Kon geen Haal Centraal Cucumber test repository vinden"
        echo "📝 Maak handmatig een test suite aan..."
        
        mkdir -p "$TEST_DIR"
        cd "$TEST_DIR"
        
        # Maak basis structuur
        mkdir -p features/step_definitions
        mkdir -p features/support
        
        echo "✅ Test directory aangemaakt"
    fi
fi

cd "$TEST_DIR"

# Check of Gemfile bestaat
if [ ! -f "Gemfile" ]; then
    echo "📝 Gemfile niet gevonden, aanmaken..."
    cat > Gemfile << 'EOF'
source 'https://rubygems.org'

gem 'cucumber', '~> 8.0'
gem 'rspec', '~> 3.12'
gem 'httparty', '~> 0.21'
gem 'json', '~> 2.6'
gem 'json-schema', '~> 4.0'
EOF
    echo "✅ Gemfile aangemaakt"
fi

# Install dependencies
echo "📦 Installeren dependencies..."
bundle install

# Maak configuratie bestand
echo "📝 Configuratie bestand aanmaken..."
cat > config.yml << EOF
api:
  base_url: ${API_URL}
  timeout: 30
  headers:
    Content-Type: application/json
    Accept: application/json

test_data:
  test_bsn: "168149291"
  test_bsn_not_found: "999999999"
EOF

echo ""
echo "✅ Setup voltooid!"
echo ""
echo "📋 Volgende stappen:"
echo "   1. Run tests: cd haal-centraal-cucumber-tests && bundle exec cucumber"
echo "   2. Of gebruik het test script: ./run-haal-centraal-tests.sh"
echo ""







