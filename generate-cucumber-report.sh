#!/bin/bash
# Genereer HTML rapport van Cucumber test resultaten

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPORT_DIR="${SCRIPT_DIR}/test-results/cucumber"
HTML_REPORT="${REPORT_DIR}/index.html"

echo "📊 Cucumber Test Rapport Generator"
echo "=================================="
echo ""

# Find latest JSON report
LATEST_JSON=$(ls -t "${REPORT_DIR}"/cucumber_*.json 2>/dev/null | head -1)

if [ -z "$LATEST_JSON" ]; then
    echo "❌ Geen test resultaten gevonden in ${REPORT_DIR}"
    echo "📦 Run eerst: ./run-haal-centraal-tests.sh"
    exit 1
fi

echo "📄 Laatste rapport: $(basename $LATEST_JSON)"
echo ""

# Simple HTML report generator
cat > "$HTML_REPORT" << 'HTMLHEAD'
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Haal Centraal Cucumber Test Rapport</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #0066cc;
            padding-bottom: 10px;
        }
        .summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat {
            display: inline-block;
            margin-right: 20px;
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 4px;
        }
        .stat.passed { background: #d4edda; color: #155724; }
        .stat.failed { background: #f8d7da; color: #721c24; }
        .stat.skipped { background: #fff3cd; color: #856404; }
        .scenario {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            border-left: 4px solid #ccc;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .scenario.passed { border-left-color: #28a745; }
        .scenario.failed { border-left-color: #dc3545; }
        .scenario.skipped { border-left-color: #ffc107; }
        .scenario h3 {
            margin-top: 0;
            color: #333;
        }
        .steps {
            margin-left: 20px;
            margin-top: 10px;
        }
        .step {
            padding: 5px 10px;
            margin: 5px 0;
            border-radius: 3px;
        }
        .step.passed { background: #d4edda; }
        .step.failed { background: #f8d7da; }
        .step.skipped { background: #fff3cd; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>🧪 Haal Centraal Cucumber Test Rapport</h1>
    <div class="summary">
        <h2>Samenvatting</h2>
        <div id="stats"></div>
    </div>
    <div id="scenarios"></div>
    <script>
HTMLHEAD

# Parse JSON and generate JavaScript
echo "        const data = " >> "$HTML_REPORT"
cat "$LATEST_JSON" >> "$HTML_REPORT"

cat >> "$HTML_REPORT" << 'HTMLTAIL'
        ;
        
        // Calculate stats
        let total = 0, passed = 0, failed = 0, skipped = 0;
        
        data.forEach(feature => {
            feature.elements.forEach(element => {
                if (element.type === 'scenario') {
                    total++;
                    const steps = element.steps || [];
                    const hasFailed = steps.some(s => s.result && s.result.status === 'failed');
                    const hasSkipped = steps.some(s => s.result && s.result.status === 'skipped');
                    
                    if (hasFailed) failed++;
                    else if (hasSkipped) skipped++;
                    else passed++;
                }
            });
        });
        
        // Display stats
        document.getElementById('stats').innerHTML = `
            <div class="stat passed">✅ Passed: ${passed}</div>
            <div class="stat failed">❌ Failed: ${failed}</div>
            <div class="stat skipped">⏭️ Skipped: ${skipped}</div>
            <div class="stat">📊 Total: ${total}</div>
        `;
        
        // Display scenarios
        const scenariosDiv = document.getElementById('scenarios');
        data.forEach(feature => {
            feature.elements.forEach(element => {
                if (element.type === 'scenario') {
                    const steps = element.steps || [];
                    const hasFailed = steps.some(s => s.result && s.result.status === 'failed');
                    const hasSkipped = steps.some(s => s.result && s.result.status === 'skipped');
                    const status = hasFailed ? 'failed' : (hasSkipped ? 'skipped' : 'passed');
                    
                    let stepsHtml = '<div class="steps">';
                    steps.forEach(step => {
                        const stepStatus = step.result ? step.result.status : 'skipped';
                        const duration = step.result && step.result.duration ? 
                            ` (${(step.result.duration / 1000000000).toFixed(2)}s)` : '';
                        stepsHtml += `<div class="step ${stepStatus}">${step.keyword} ${step.name}${duration}</div>`;
                        
                        if (step.result && step.result.error_message) {
                            stepsHtml += `<div class="error">${step.result.error_message}</div>`;
                        }
                    });
                    stepsHtml += '</div>';
                    
                    scenariosDiv.innerHTML += `
                        <div class="scenario ${status}">
                            <h3>${element.name}</h3>
                            <p><strong>Feature:</strong> ${feature.name}</p>
                            ${stepsHtml}
                        </div>
                    `;
                }
            });
        });
    </script>
</body>
</html>
HTMLTAIL

echo "✅ HTML rapport gegenereerd: ${HTML_REPORT}"
echo "🌐 Open in browser: open ${HTML_REPORT}"







