#!/bin/bash
# Run Haal Centraal Cucumber tests tegen onze API

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEST_DIR="${SCRIPT_DIR}/haal-centraal-cucumber-tests"
API_URL="${API_URL:-http://localhost:8080/apps/openregister}"
REPORT_DIR="${SCRIPT_DIR}/test-results/cucumber"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "🧪 Haal Centraal Cucumber Tests"
echo "================================"
echo "API URL: ${API_URL}"
echo ""

# Check of test directory bestaat
if [ ! -d "$TEST_DIR" ]; then
    echo "❌ Test directory niet gevonden: ${TEST_DIR}"
    echo "📦 Run eerst: ./setup-haal-centraal-cucumber-tests.sh"
    exit 1
fi

cd "$TEST_DIR"

# Check of dependencies geïnstalleerd zijn
if [ ! -d "vendor" ]; then
    echo "📦 Dependencies installeren..."
    bundle install
fi

# Maak report directory
mkdir -p "$REPORT_DIR"

# Set API URL environment variable
export API_URL

# Run Cucumber tests
echo "🚀 Tests uitvoeren..."
echo ""

if bundle exec cucumber --format json --out "${REPORT_DIR}/cucumber_${TIMESTAMP}.json" --format pretty; then
    echo ""
    echo "✅ Tests voltooid!"
    echo "📊 Rapport: ${REPORT_DIR}/cucumber_${TIMESTAMP}.json"
else
    echo ""
    echo "❌ Sommige tests zijn gefaald"
    echo "📊 Rapport: ${REPORT_DIR}/cucumber_${TIMESTAMP}.json"
    exit 1
fi







