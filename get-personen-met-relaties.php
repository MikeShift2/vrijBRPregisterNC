<?php
/**
 * Script om alle personen op te halen die relaties hebben (partner, kinderen, ouders)
 * 
 * Gebruik: php get-personen-met-relaties.php
 */

// Database configuratie
$dbHost = 'nextcloud-db';
$dbUser = 'nextcloud_user';
$dbPass = 'nextcloud_secure_pass_2024';
$dbName = 'nextcloud';

// PostgreSQL configuratie voor probev
$pgHost = 'host.docker.internal';
$pgPort = '5432';
$pgUser = 'postgres';
$pgPass = 'postgres';
$pgDb = 'bevax';

try {
    // Connectie met Nextcloud database (MariaDB)
    $nextcloudDb = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Connectie met PostgreSQL (probev)
    $pgDb = new PDO(
        "pgsql:host=$pgHost;port=$pgPort;dbname=$pgDb;options=-csearch_path=probev",
        $pgUser,
        $pgPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== Personen met Relaties ===\n\n";
    
    // Haal alle personen op uit OpenRegister
    $stmt = $nextcloudDb->query("
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(object, '$.burgerservicenummer')) as bsn,
            JSON_UNQUOTE(JSON_EXTRACT(object, '$.naam.voornamen')) as voornamen,
            JSON_UNQUOTE(JSON_EXTRACT(object, '$.naam.geslachtsnaam')) as geslachtsnaam,
            JSON_UNQUOTE(JSON_EXTRACT(object, '$.naam.voorvoegsel')) as voorvoegsel
        FROM oc_openregister_objects
        WHERE register = 2 AND schema = 6
        ORDER BY geslachtsnaam, voornamen
        LIMIT 1000
    ");
    
    $personenMetRelaties = [];
    $teller = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bsn = $row['bsn'];
        if (empty($bsn) || $bsn === '-1') continue;
        
        $teller++;
        if ($teller % 100 === 0) {
            echo "Verwerkt: $teller personen...\n";
        }
        
        $relaties = [];
        
        // Check partners
        $partnerStmt = $pgDb->prepare("
            SELECT COUNT(*) as count
            FROM huw_ax h
            JOIN pl p ON p.a1 = h.a1_ref AND p.a2 = h.a2_ref AND p.a3 = h.a3_ref
            WHERE h.pl_id = (SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1)
            AND h.ax = 'A' AND h.hist = 'A'
        ");
        $partnerStmt->execute(['bsn' => $bsn]);
        $partnerCount = $partnerStmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($partnerCount > 0) {
            $relaties['partners'] = (int)$partnerCount;
        }
        
        // Check kinderen
        $kinderenStmt = $pgDb->prepare("
            SELECT COUNT(*) as count
            FROM afst_ax a
            JOIN pl p ON p.a1 = a.a1_ref AND p.a2 = a.a2_ref AND p.a3 = a.a3_ref
            WHERE a.pl_id = (SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1)
            AND a.ax = 'A' AND a.hist = 'A'
            AND p.bsn::text != '-1'
        ");
        $kinderenStmt->execute(['bsn' => $bsn]);
        $kinderenCount = $kinderenStmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($kinderenCount > 0) {
            $relaties['kinderen'] = (int)$kinderenCount;
        }
        
        // Check ouders
        $oudersStmt = $pgDb->prepare("
            SELECT COUNT(*) as count
            FROM (
                SELECT DISTINCT p.bsn
                FROM mdr_ax m
                JOIN pl p ON p.a1 = m.a1_ref AND p.a2 = m.a2_ref AND p.a3 = m.a3_ref
                WHERE m.pl_id = (SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1)
                AND m.ax = 'A' AND m.hist = 'A' AND p.bsn::text != '-1'
                UNION
                SELECT DISTINCT p.bsn
                FROM vdr_ax v
                JOIN pl p ON p.a1 = v.a1_ref AND p.a2 = v.a2_ref AND p.a3 = v.a3_ref
                WHERE v.pl_id = (SELECT pl_id FROM inw_ax WHERE bsn = :bsn AND ax = 'A' AND hist = 'A' LIMIT 1)
                AND v.ax = 'A' AND v.hist = 'A' AND p.bsn::text != '-1'
            ) ouders
        ");
        $oudersStmt->execute(['bsn' => $bsn]);
        $oudersCount = $oudersStmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($oudersCount > 0) {
            $relaties['ouders'] = (int)$oudersCount;
        }
        
        // Alleen toevoegen als er relaties zijn
        if (!empty($relaties)) {
            $voornamen = is_array(json_decode($row['voornamen'], true)) 
                ? implode(' ', json_decode($row['voornamen'], true)) 
                : $row['voornamen'];
            
            $personenMetRelaties[] = [
                'bsn' => $bsn,
                'naam' => trim(($row['voorvoegsel'] ? $row['voorvoegsel'] . ' ' : '') . $row['geslachtsnaam'] . ', ' . $voornamen),
                'relaties' => $relaties
            ];
        }
    }
    
    echo "\n=== Resultaten ===\n\n";
    echo "Totaal aantal personen met relaties: " . count($personenMetRelaties) . "\n\n";
    
    // Groepeer per relatie type
    $metPartners = array_filter($personenMetRelaties, function($p) { return isset($p['relaties']['partners']); });
    $metKinderen = array_filter($personenMetRelaties, function($p) { return isset($p['relaties']['kinderen']); });
    $metOuders = array_filter($personenMetRelaties, function($p) { return isset($p['relaties']['ouders']); });
    
    echo "Personen met partner(s): " . count($metPartners) . "\n";
    echo "Personen met kinderen: " . count($metKinderen) . "\n";
    echo "Personen met ouders: " . count($metOuders) . "\n\n";
    
    echo "=== Top 50 Personen met Relaties ===\n\n";
    
    // Sorteer op aantal relaties
    usort($personenMetRelaties, function($a, $b) {
        $aTotal = array_sum($a['relaties']);
        $bTotal = array_sum($b['relaties']);
        return $bTotal <=> $aTotal;
    });
    
    $top50 = array_slice($personenMetRelaties, 0, 50);
    
    foreach ($top50 as $index => $persoon) {
        $relatieInfo = [];
        if (isset($persoon['relaties']['partners'])) {
            $relatieInfo[] = $persoon['relaties']['partners'] . " partner(s)";
        }
        if (isset($persoon['relaties']['kinderen'])) {
            $relatieInfo[] = $persoon['relaties']['kinderen'] . " kind(eren)";
        }
        if (isset($persoon['relaties']['ouders'])) {
            $relatieInfo[] = $persoon['relaties']['ouders'] . " ouder(s)";
        }
        
        echo sprintf(
            "%3d. BSN: %s | %s | %s\n",
            $index + 1,
            $persoon['bsn'],
            $persoon['naam'],
            implode(', ', $relatieInfo)
        );
    }
    
    // Export naar JSON (in workspace directory)
    $jsonFile = '/var/www/html/custom_apps/openregister/personen-met-relaties.json';
    file_put_contents($jsonFile, json_encode($personenMetRelaties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n\n=== Export ===\n";
    echo "Resultaten geëxporteerd naar: $jsonFile\n";
    echo "Totaal: " . count($personenMetRelaties) . " personen\n";
    
    // Toon eerste 20 als voorbeeld
    echo "\n=== Voorbeeld (eerste 20) ===\n";
    foreach (array_slice($personenMetRelaties, 0, 20) as $index => $persoon) {
        $relatieInfo = [];
        if (isset($persoon['relaties']['partners'])) {
            $relatieInfo[] = $persoon['relaties']['partners'] . " partner(s)";
        }
        if (isset($persoon['relaties']['kinderen'])) {
            $relatieInfo[] = $persoon['relaties']['kinderen'] . " kind(eren)";
        }
        if (isset($persoon['relaties']['ouders'])) {
            $relatieInfo[] = $persoon['relaties']['ouders'] . " ouder(s)";
        }
        
        echo sprintf(
            "%3d. BSN: %s | %s | %s\n",
            $index + 1,
            $persoon['bsn'],
            $persoon['naam'],
            implode(', ', $relatieInfo)
        );
    }
    
} catch (PDOException $e) {
    echo "Database fout: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Fout: " . $e->getMessage() . "\n";
    exit(1);
}

