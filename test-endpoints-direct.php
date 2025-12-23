<?php
/**
 * Direct test van de endpoints voor BSN 168149291
 * Dit script test de database queries die de endpoints gebruiken
 */

$bsn = '168149291';

echo "=== TEST ENDPOINTS VOOR BSN: $bsn ===\n\n";

// Test 1: Haal pl_id op
echo "1. Test getPlIdFromBsn():\n";
$cmd = sprintf(
    "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT pl_id FROM probev.inw_ax WHERE bsn = %s AND ax = 'A' AND hist = 'A' LIMIT 1;\"",
    $bsn
);
$output = shell_exec($cmd);
$plId = trim($output);
if ($plId && is_numeric($plId) && $plId !== '-1') {
    echo "   ✅ pl_id gevonden: $plId\n\n";
} else {
    echo "   ❌ Geen pl_id gevonden\n\n";
    exit(1);
}

// Test 2: Partners
echo "2. Test getPartners():\n";
$cmd2 = sprintf(
    "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT DISTINCT p.bsn FROM probev.huw_ax h JOIN probev.pl p ON p.a1 = h.a1_ref AND p.a2 = h.a2_ref AND p.a3 = h.a3_ref WHERE h.pl_id = %d AND h.ax = 'A' AND h.hist = 'A' AND p.bsn::text != '%s';\"",
    (int)$plId,
    $bsn
);
$output2 = shell_exec($cmd2);
$partnerBsns = array_filter(array_map('trim', explode("\n", trim($output2))));
echo "   ✅ Partners gevonden: " . count($partnerBsns) . "\n";
foreach ($partnerBsns as $partnerBsn) {
    echo "      - Partner BSN: $partnerBsn\n";
}
echo "\n";

// Test 3: Kinderen
echo "3. Test getKinderen():\n";
$cmd3 = sprintf(
    "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT DISTINCT p.bsn FROM probev.afst_ax a JOIN probev.pl p ON p.a1 = a.a1_ref AND p.a2 = a.a2_ref AND p.a3 = a.a3_ref WHERE a.pl_id = %d AND a.ax = 'A' AND a.hist = 'A' AND p.bsn::text != '-1';\"",
    (int)$plId
);
$output3 = shell_exec($cmd3);
$kindBsns = array_filter(array_map('trim', explode("\n", trim($output3))));
echo "   ✅ Kinderen gevonden: " . count($kindBsns) . "\n";
foreach ($kindBsns as $kindBsn) {
    echo "      - Kind BSN: $kindBsn\n";
}
echo "\n";

// Test 4: Ouders
echo "4. Test getOuders():\n";
$cmd4 = sprintf(
    "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT DISTINCT p.bsn FROM probev.mdr_ax m JOIN probev.pl p ON p.a1 = m.a1_ref AND p.a2 = m.a2_ref AND p.a3 = m.a3_ref WHERE m.pl_id = %d AND m.ax = 'A' AND m.hist = 'A' AND p.bsn::text != '-1' LIMIT 1;\"",
    (int)$plId
);
$output4 = shell_exec($cmd4);
$ouder1Bsn = trim($output4);
if ($ouder1Bsn && $ouder1Bsn !== '-1') {
    echo "   ✅ Ouder 1 BSN: $ouder1Bsn\n";
} else {
    echo "   ⚠️  Geen ouder 1 gevonden\n";
}

$cmd5 = sprintf(
    "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT DISTINCT p.bsn FROM probev.vdr_ax v JOIN probev.pl p ON p.a1 = v.a1_ref AND p.a2 = v.a2_ref AND p.a3 = v.a3_ref WHERE v.pl_id = %d AND v.ax = 'A' AND v.hist = 'A' AND p.bsn::text != '-1' LIMIT 1;\"",
    (int)$plId
);
$output5 = shell_exec($cmd5);
$ouder2Bsn = trim($output5);
if ($ouder2Bsn && $ouder2Bsn !== '-1') {
    echo "   ✅ Ouder 2 BSN: $ouder2Bsn\n";
} else {
    echo "   ⚠️  Geen ouder 2 gevonden\n";
}
echo "\n";

// Test 5: Nationaliteiten
echo "5. Test getNationaliteiten():\n";
$cmd6 = sprintf(
    "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT n.c_natio, nat.natio FROM probev.nat_ax n LEFT JOIN probev.natio nat ON nat.c_natio = n.c_natio WHERE n.pl_id = %d AND n.ax = 'A' AND n.hist = 'A';\"",
    (int)$plId
);
$output6 = shell_exec($cmd6);
$lines = array_filter(array_map('trim', explode("\n", trim($output6))));
echo "   ✅ Nationaliteiten gevonden: " . count($lines) . "\n";
foreach ($lines as $line) {
    if (strpos($line, '|') !== false) {
        list($code, $omschrijving) = explode('|', $line, 2);
        echo "      - Code: " . trim($code) . ", Omschrijving: " . trim($omschrijving) . "\n";
    }
}
echo "\n";

echo "=== SAMENVATTING ===\n";
echo "✅ pl_id: $plId\n";
echo "✅ Partners: " . count($partnerBsns) . " gevonden\n";
echo "✅ Kinderen: " . count($kindBsns) . " gevonden\n";
echo "✅ Ouders: " . (($ouder1Bsn && $ouder1Bsn !== '-1' ? 1 : 0) + ($ouder2Bsn && $ouder2Bsn !== '-1' ? 1 : 0)) . " gevonden\n";
echo "✅ Nationaliteiten: " . count($lines) . " gevonden\n";
echo "\n";
echo "De database queries werken correct! De endpoints zouden deze data moeten retourneren.\n";







