<?php
/**
 * Script om BSN's te vinden met adres historie in de database
 */

// Database connectie
$pdo = new PDO(
    'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
    'postgres',
    'postgres',
    [
        PDO::ATTR_TIMEOUT => 10,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);

echo "=== Zoeken naar BSN's met adres historie ===\n\n";

// Query 1: BSN's met meerdere adressen (actueel + historisch)
echo "1. BSN's met meerdere adressen:\n";
$stmt1 = $pdo->query("
    SELECT DISTINCT 
        i.bsn, 
        COUNT(*) as aantal_adressen,
        COUNT(CASE WHEN vb.ax = 'A' AND vb.hist = 'A' THEN 1 END) as actuele_adressen,
        COUNT(CASE WHEN vb.hist = 'Z' THEN 1 END) as historische_adressen
    FROM inw_ax i
    INNER JOIN vb_ax vb ON i.pl_id = vb.pl_id
    WHERE i.ax = 'A' AND i.hist = 'A'
    GROUP BY i.bsn
    HAVING COUNT(*) > 1
    ORDER BY aantal_adressen DESC
    LIMIT 20
");

$results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
if (empty($results1)) {
    echo "Geen resultaten gevonden.\n\n";
} else {
    printf("%-12s %-20s %-20s %-20s\n", "BSN", "Totaal adressen", "Actuele adressen", "Historische adressen");
    echo str_repeat("-", 75) . "\n";
    foreach ($results1 as $row) {
        printf("%-12s %-20s %-20s %-20s\n", 
            $row['bsn'], 
            $row['aantal_adressen'],
            $row['actuele_adressen'],
            $row['historische_adressen']
        );
    }
    echo "\n";
}

// Query 2: BSN's met alleen historische adressen (hist='Z')
echo "2. BSN's met historische adressen (hist='Z'):\n";
$stmt2 = $pdo->query("
    SELECT DISTINCT 
        i.bsn, 
        COUNT(*) as aantal_historische_adressen
    FROM inw_ax i
    INNER JOIN vb_ax vb ON i.pl_id = vb.pl_id
    WHERE i.ax = 'A' AND i.hist = 'A'
    AND vb.hist = 'Z'
    GROUP BY i.bsn
    ORDER BY aantal_historische_adressen DESC
    LIMIT 20
");

$results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
if (empty($results2)) {
    echo "Geen resultaten gevonden.\n\n";
} else {
    printf("%-12s %-30s\n", "BSN", "Aantal historische adressen");
    echo str_repeat("-", 45) . "\n";
    foreach ($results2 as $row) {
        printf("%-12s %-30s\n", $row['bsn'], $row['aantal_historische_adressen']);
    }
    echo "\n";
}

// Query 3: Detail voor specifieke test BSN's
echo "3. Detail adres historie voor bekende test BSN's:\n";
$testBsns = ['168149291', '167943698', '547503866', '167879352', '386952875'];
$placeholders = implode(',', array_fill(0, count($testBsns), '?'));

$stmt3 = $pdo->prepare("
    SELECT 
        i.bsn,
        COALESCE(s.straat::text, '') as straatnaam,
        COALESCE(vb.hnr::text, '') as huisnummer,
        COALESCE(vb.pc::text, '') as postcode,
        COALESCE(w.plaats::text, '') as woonplaats,
        vb.d_aanv as ingangsdatum,
        vb.d_vertrek as einddatum,
        vb.ax as actueel,
        vb.hist as historie
    FROM inw_ax i
    INNER JOIN vb_ax vb ON i.pl_id = vb.pl_id
    LEFT JOIN straat s ON vb.c_straat = s.c_straat
    LEFT JOIN plaats w ON vb.c_wpl = w.c_plaats
    WHERE i.bsn IN ($placeholders)
    ORDER BY i.bsn, vb.d_aanv DESC
");

$stmt3->execute($testBsns);
$results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

if (empty($results3)) {
    echo "Geen resultaten gevonden voor test BSN's.\n\n";
} else {
    $currentBsn = null;
    foreach ($results3 as $row) {
        if ($currentBsn !== $row['bsn']) {
            if ($currentBsn !== null) {
                echo "\n";
            }
            echo "BSN: " . $row['bsn'] . "\n";
            echo str_repeat("-", 80) . "\n";
            printf("%-30s %-10s %-10s %-5s %-5s\n", 
                "Adres", "Ingang", "Eind", "Actueel", "Hist");
            echo str_repeat("-", 80) . "\n";
            $currentBsn = $row['bsn'];
        }
        
        $adres = trim($row['straatnaam'] . ' ' . $row['huisnummer'] . ' ' . $row['postcode'] . ' ' . $row['woonplaats']);
        $ingang = $row['ingangsdatum'] != -1 ? $row['ingangsdatum'] : '-';
        $eind = $row['einddatum'] != -1 ? $row['einddatum'] : '-';
        $actueel = $row['actueel'];
        $hist = $row['historie'];
        
        printf("%-30s %-10s %-10s %-5s %-5s\n", 
            substr($adres, 0, 30),
            $ingang,
            $eind,
            $actueel,
            $hist
        );
    }
    echo "\n";
}

echo "=== Klaar ===\n";

