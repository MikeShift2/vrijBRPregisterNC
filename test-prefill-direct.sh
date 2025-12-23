#!/bin/bash

echo "=== Prefill Test Direct Check ==="
echo ""

echo "1. Check database voor BSN 216007574..."
docker exec nextcloud php -r "
\$db = new PDO('mysql:host=localhost;dbname=openregister', 'openregister', 'openregister_secure_2024');
\$stmt = \$db->query(\"SELECT JSON_EXTRACT(object, '$.bsn') as bsn_old, JSON_EXTRACT(object, '$.burgerservicenummer') as bsn_new, JSON_EXTRACT(object, '$.naam.geslachtsnaam') as naam FROM oc_openregister_objects WHERE register='openregister' AND schema=6 LIMIT 5\");
\$rows = \$stmt->fetchAll(PDO::FETCH_ASSOC);
foreach (\$rows as \$row) {
    echo 'BSN (oud): ' . \$row['bsn_old'] . ' | BSN (nieuw): ' . \$row['bsn_new'] . ' | Naam: ' . \$row['naam'] . PHP_EOL;
}
"

echo ""
echo "2. Test API met BSN 216007574..."
curl -s -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=216007574&_limit=1"

echo ""
echo ""
echo "3. Test API met BSN 168149291..."
curl -s -u admin:admin "http://localhost:8080/apps/openregister/ingeschrevenpersonen?bsn=168149291&_limit=1"

echo ""
echo ""
echo "Done!"
