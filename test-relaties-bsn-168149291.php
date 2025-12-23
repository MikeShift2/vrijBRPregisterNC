<?php
/**
 * Test script om relaties toe te voegen aan BSN 168149291
 */

// Database configuraties
$nextcloudDbHost = 'nextcloud-db';
$nextcloudDbName = 'nextcloud';
$nextcloudDbUser = 'nextcloud_user';
$nextcloudDbPass = 'nextcloud_secure_pass_2024';

// Verbind met MariaDB (Nextcloud)
try {
    $nextcloudPdo = new PDO("mysql:host=$nextcloudDbHost;dbname=$nextcloudDbName;charset=utf8mb4", $nextcloudDbUser, $nextcloudDbPass);
    $nextcloudPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("MariaDB verbinding gefaald: " . $e->getMessage() . "\n");
}

$bsn = '168149291';
$schemaId = 6;
$registerId = 2;

echo "🔍 Test relaties voor BSN: $bsn\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Verbind met PostgreSQL via PDO (zoals in HaalCentraalBrpController)
try {
    $pgPdo = new PDO('pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev', 'postgres', 'postgres');
    $pgPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ PostgreSQL verbinding gefaald: " . $e->getMessage() . "\n");
}

// Stap 1: Haal pl_id op
echo "1. Haal pl_id op...\n";
try {
    $stmt = $pgPdo->prepare("SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1");
    $stmt->execute(['bsn' => $bsn]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || !isset($result['pl_id'])) {
        die("❌ Geen pl_id gevonden voor BSN $bsn\n");
    }
    
    $plId = (int)$result['pl_id'];
    echo "   ✅ pl_id: $plId\n\n";
} catch (PDOException $e) {
    die("❌ Fout bij ophalen pl_id: " . $e->getMessage() . "\n");
}

// Stap 2: Haal partners op
echo "2. Haal partners op...\n";
try {
    $stmt = $pgPdo->prepare("
        SELECT DISTINCT p.bsn 
        FROM huw_ax h 
        JOIN pl p ON p.a1 = h.a1_ref AND p.a2 = h.a2_ref AND p.a3 = h.a3_ref 
        WHERE h.pl_id = :pl_id 
        AND h.ax = 'A' 
        AND h.hist = 'A' 
        AND p.bsn::text != :bsn
    ");
    $stmt->execute(['pl_id' => $plId, 'bsn' => $bsn]);
    $partnerBsns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'bsn');
    $partnerBsns = array_filter($partnerBsns, function($b) { return $b && $b !== '-1'; });
    echo "   ✅ Partners BSNs: " . implode(', ', $partnerBsns) . "\n\n";
} catch (PDOException $e) {
    echo "   ⚠️  Fout bij ophalen partners: " . $e->getMessage() . "\n";
    $partnerBsns = [];
}

// Stap 3: Haal kinderen op
echo "3. Haal kinderen op...\n";
try {
    $stmt = $pgPdo->prepare("
        SELECT DISTINCT p.bsn 
        FROM afst_ax a 
        JOIN pl p ON p.a1 = a.a1_ref AND p.a2 = a.a2_ref AND p.a3 = a.a3_ref 
        WHERE a.pl_id = :pl_id 
        AND a.ax = 'A' 
        AND a.hist = 'A' 
        AND p.bsn::text != '-1'
    ");
    $stmt->execute(['pl_id' => $plId]);
    $kindBsns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'bsn');
    $kindBsns = array_filter($kindBsns, function($b) { return $b && $b !== '-1'; });
    echo "   ✅ Kinderen BSNs: " . implode(', ', $kindBsns) . "\n\n";
} catch (PDOException $e) {
    echo "   ⚠️  Fout bij ophalen kinderen: " . $e->getMessage() . "\n";
    $kindBsns = [];
}

// Stap 4: Haal ouders op
echo "4. Haal ouders op...\n";
$ouderBsns = [];
try {
    // Ouder 1 via mdr_ax
    $stmt = $pgPdo->prepare("
        SELECT DISTINCT p.bsn 
        FROM mdr_ax m 
        JOIN pl p ON p.a1 = m.a1_ref AND p.a2 = m.a2_ref AND p.a3 = m.a3_ref 
        WHERE m.pl_id = :pl_id 
        AND m.ax = 'A' 
        AND m.hist = 'A' 
        AND p.bsn::text != '-1' 
        LIMIT 1
    ");
    $stmt->execute(['pl_id' => $plId]);
    $ouder1 = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ouder1 && isset($ouder1['bsn']) && $ouder1['bsn'] !== '-1') {
        $ouderBsns[] = $ouder1['bsn'];
    }
    
    // Ouder 2 via vdr_ax
    $stmt = $pgPdo->prepare("
        SELECT DISTINCT p.bsn 
        FROM vdr_ax v 
        JOIN pl p ON p.a1 = v.a1_ref AND p.a2 = v.a2_ref AND p.a3 = v.a3_ref 
        WHERE v.pl_id = :pl_id 
        AND v.ax = 'A' 
        AND v.hist = 'A' 
        AND p.bsn::text != '-1' 
        LIMIT 1
    ");
    $stmt->execute(['pl_id' => $plId]);
    $ouder2 = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ouder2 && isset($ouder2['bsn']) && $ouder2['bsn'] !== '-1') {
        $ouderBsns[] = $ouder2['bsn'];
    }
    
    echo "   ✅ Ouders BSNs: " . implode(', ', $ouderBsns) . "\n\n";
} catch (PDOException $e) {
    echo "   ⚠️  Fout bij ophalen ouders: " . $e->getMessage() . "\n";
}

// Stap 5: Haal nationaliteiten op
echo "5. Haal nationaliteiten op...\n";
$nationaliteiten = [];
try {
    $stmt = $pgPdo->prepare("
        SELECT n.c_natio, nat.natio 
        FROM nat_ax n 
        LEFT JOIN natio nat ON nat.c_natio = n.c_natio 
        WHERE n.pl_id = :pl_id 
        AND n.ax = 'A' 
        AND n.hist = 'A'
    ");
    $stmt->execute(['pl_id' => $plId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $code = $row['c_natio'] ?? null;
        $omschrijving = $row['natio'] ?? null;
        if ($code) {
            $nationaliteiten[] = [
                'code' => trim($code),
                'omschrijving' => $omschrijving ? trim($omschrijving) : null
            ];
        }
    }
    
    echo "   ✅ Nationaliteiten: " . count($nationaliteiten) . "\n";
    foreach ($nationaliteiten as $nat) {
        echo "      - Code: {$nat['code']}, Omschrijving: {$nat['omschrijving']}\n";
    }
    echo "\n";
} catch (PDOException $e) {
    echo "   ⚠️  Fout bij ophalen nationaliteiten: " . $e->getMessage() . "\n";
}

// Stap 6: Haal huidige persoon op uit Open Register
echo "6. Haal persoon op uit Open Register...\n";
$stmt = $nextcloudPdo->prepare("
    SELECT object FROM oc_openregister_objects 
    WHERE register = ? AND schema = ? 
    AND JSON_EXTRACT(object, '$.bsn') = ?
    LIMIT 1
");
$stmt->execute([$registerId, $schemaId, $bsn]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("❌ Persoon niet gevonden in Open Register\n");
}

$persoonData = json_decode($result['object'], true);
echo "   ✅ Persoon gevonden\n\n";

// Stap 7: Voeg relaties toe (vereenvoudigd - alleen BSNs voor nu)
echo "7. Voeg relaties toe aan persoon object...\n";
$persoonData['_embedded'] = [
    'partners' => array_map(function($bsn) {
        return ['burgerservicenummer' => $bsn];
    }, $partnerBsns),
    'kinderen' => array_map(function($bsn) {
        return ['burgerservicenummer' => $bsn];
    }, $kindBsns),
    'ouders' => array_map(function($bsn) {
        return ['burgerservicenummer' => $bsn];
    }, $ouderBsns),
    'nationaliteiten' => array_map(function($nat) {
        return [
            'nationaliteit' => [
                'code' => $nat['code'],
                'omschrijving' => $nat['omschrijving']
            ]
        ];
    }, $nationaliteiten)
];

// Stap 8: Update in Open Register
echo "8. Update persoon in Open Register...\n";
$updateStmt = $nextcloudPdo->prepare("
    UPDATE oc_openregister_objects 
    SET object = ?, updated = NOW()
    WHERE register = ? AND schema = ? 
    AND JSON_EXTRACT(object, '$.bsn') = ?
");
$updateStmt->execute([
    json_encode($persoonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    $registerId,
    $schemaId,
    $bsn
]);

echo "   ✅ Persoon bijgewerkt met relaties\n\n";

// Stap 9: Verifieer
echo "9. Verifieer relaties in Open Register...\n";
$stmt = $nextcloudPdo->prepare("
    SELECT 
        JSON_EXTRACT(object, '$._embedded.partners') as partners,
        JSON_EXTRACT(object, '$._embedded.kinderen') as kinderen,
        JSON_EXTRACT(object, '$._embedded.ouders') as ouders,
        JSON_EXTRACT(object, '$._embedded.nationaliteiten') as nationaliteiten
    FROM oc_openregister_objects 
    WHERE register = ? AND schema = ? 
    AND JSON_EXTRACT(object, '$.bsn') = ?
");
$stmt->execute([$registerId, $schemaId, $bsn]);
$verify = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   Partners: " . $verify['partners'] . "\n";
echo "   Kinderen: " . $verify['kinderen'] . "\n";
echo "   Ouders: " . $verify['ouders'] . "\n";
echo "   Nationaliteiten: " . $verify['nationaliteiten'] . "\n\n";

echo "✅ Test voltooid!\n";

