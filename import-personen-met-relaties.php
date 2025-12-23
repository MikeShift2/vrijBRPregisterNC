<?php
/**
 * Script om Personen data met relaties te importeren van PostgreSQL naar OpenRegister
 * 
 * Dit script:
 * 1. Haalt personen op uit probev database
 * 2. Haalt relaties op (partners, kinderen, ouders, nationaliteiten, verblijfplaats)
 * 3. Voegt relaties toe als _embedded object
 * 4. Importeert naar Open Register
 */

// Database configuraties
$nextcloudDbHost = 'nextcloud-db';
$nextcloudDbName = 'nextcloud';
$nextcloudDbUser = 'nextcloud_user';
$nextcloudDbPass = 'nextcloud_secure_pass_2024';

$postgresHost = 'host.docker.internal';
$postgresPort = '5432';
$postgresDb = 'bevax';
$postgresUser = 'postgres';
$postgresPass = '';

// Verbind met MariaDB (Nextcloud)
try {
    $nextcloudPdo = new PDO("mysql:host=$nextcloudDbHost;dbname=$nextcloudDbName;charset=utf8mb4", $nextcloudDbUser, $nextcloudDbPass);
    $nextcloudPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("MariaDB verbinding gefaald: " . $e->getMessage() . "\n");
}

// Verbind met PostgreSQL
function getPostgresPdo() {
    global $postgresHost, $postgresPort, $postgresDb, $postgresUser, $postgresPass;
    try {
        // Gebruik docker exec voor PostgreSQL connectie (omdat PDO mogelijk niet beschikbaar is)
        // We gebruiken shell_exec voor queries
        return 'docker_exec'; // Marker voor shell_exec gebruik
    } catch (PDOException $e) {
        error_log("PostgreSQL verbinding gefaald: " . $e->getMessage());
        return null;
    }
}

// Haal data op via docker exec
function execPostgresQuery($sql, $params = []) {
    global $postgresDb, $postgresUser;
    
    // Escape parameters voor shell
    $escapedSql = escapeshellarg($sql);
    
    // Voeg parameters toe als bind variables niet mogelijk zijn
    foreach ($params as $key => $value) {
        $escapedSql = str_replace(':' . $key, escapeshellarg($value), $escapedSql);
    }
    
    $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U $postgresUser -d $postgresDb -t -A -c $escapedSql 2>&1";
    $output = shell_exec($cmd);
    
    // Parse output
    $lines = explode("\n", trim($output));
    $results = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && $line !== 'SET' && !preg_match('/^[^|]*\|/', $line)) {
            $results[] = $line;
        }
    }
    
    return $results;
}

// Haal personen op uit probev via view
function getPersonenFromPostgres($offset = 0, $limit = 100) {
    try {
        $sql = "SELECT json_agg(row_to_json(t)) FROM (SELECT * FROM probev.v_personen_compleet_haal_centraal ORDER BY bsn LIMIT $limit OFFSET $offset) t;";
        $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c " . escapeshellarg($sql) . " 2>&1";
        $output = shell_exec($cmd);
        $json = trim($output);
        
        // Verwijder SET output
        $json = preg_replace('/^SET\s*\n?/m', '', $json);
        $json = trim($json);
        
        if (empty($json) || $json === '(0 rows)' || $json === 'null') {
            return [];
        }
        
        // Pak alleen JSON array
        $json = preg_replace('/.*?(\[.*\]).*/s', '$1', $json);
        
        $data = json_decode($json, true);
        return $data ?: [];
    } catch (Exception $e) {
        error_log("Fout bij ophalen personen: " . $e->getMessage());
        return [];
    }
}

// Haal pl_id op basis van BSN
function getPlIdFromBsn($bsn) {
    if (!$bsn) return null;
    
    try {
        $sql = "SELECT pl_id FROM probev.inw_ax WHERE bsn = '$bsn' AND ax = 'A' AND hist = 'A' LIMIT 1;";
        $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c " . escapeshellarg($sql) . " 2>&1";
        $output = shell_exec($cmd);
        $plId = trim($output);
        
        // Verwijder SET output
        $plId = preg_replace('/^SET\s*\n?/m', '', $plId);
        $plId = trim($plId);
        
        if (empty($plId) || !is_numeric($plId)) {
            return null;
        }
        
        return (int)$plId;
    } catch (Exception $e) {
        error_log("Fout bij ophalen pl_id voor BSN $bsn: " . $e->getMessage());
        return null;
    }
}

// Haal partners op voor een pl_id
function getPartnersForPlId($plId, $bsn) {
    $pdo = getPostgresPdo();
    if (!$pdo || !$plId) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.bsn 
            FROM huw_ax h 
            JOIN pl p ON p.a1 = h.a1_ref AND p.a2 = h.a2_ref AND p.a3 = h.a3_ref 
            WHERE h.pl_id = :pl_id 
            AND h.ax = 'A' 
            AND h.hist = 'A' 
            AND p.bsn::text != :bsn
        ");
        $stmt->execute(['pl_id' => $plId, 'bsn' => $bsn]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $partners = [];
        foreach ($results as $row) {
            $partnerBsn = $row['bsn'] ?? null;
            if ($partnerBsn && $partnerBsn !== '-1') {
                // Haal partner data op
                $partnerData = getPersonFromPostgresByBsn($partnerBsn);
                if ($partnerData) {
                    $partners[] = transformToHaalCentraalFormat($partnerData);
                }
            }
        }
        
        return $partners;
    } catch (PDOException $e) {
        error_log("Fout bij ophalen partners: " . $e->getMessage());
        return [];
    }
}

// Haal kinderen op voor een pl_id
function getKinderenForPlId($plId) {
    $pdo = getPostgresPdo();
    if (!$pdo || !$plId) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.bsn 
            FROM afst_ax a 
            JOIN pl p ON p.a1 = a.a1_ref AND p.a2 = a.a2_ref AND p.a3 = a.a3_ref 
            WHERE a.pl_id = :pl_id 
            AND a.ax = 'A' 
            AND a.hist = 'A' 
            AND p.bsn::text != '-1'
        ");
        $stmt->execute(['pl_id' => $plId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $kinderen = [];
        foreach ($results as $row) {
            $kindBsn = $row['bsn'] ?? null;
            if ($kindBsn && $kindBsn !== '-1') {
                $kindData = getPersonFromPostgresByBsn($kindBsn);
                if ($kindData) {
                    $kinderen[] = transformToHaalCentraalFormat($kindData);
                }
            }
        }
        
        return $kinderen;
    } catch (PDOException $e) {
        error_log("Fout bij ophalen kinderen: " . $e->getMessage());
        return [];
    }
}

// Haal ouders op voor een pl_id
function getOudersForPlId($plId) {
    $pdo = getPostgresPdo();
    if (!$pdo || !$plId) return [];
    
    $ouders = [];
    
    try {
        // Ouder 1 via mdr_ax
        $stmt1 = $pdo->prepare("
            SELECT DISTINCT p.bsn 
            FROM mdr_ax m 
            JOIN pl p ON p.a1 = m.a1_ref AND p.a2 = m.a2_ref AND p.a3 = m.a3_ref 
            WHERE m.pl_id = :pl_id 
            AND m.ax = 'A' 
            AND m.hist = 'A' 
            AND p.bsn::text != '-1' 
            LIMIT 1
        ");
        $stmt1->execute(['pl_id' => $plId]);
        $ouder1Result = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        if ($ouder1Result && isset($ouder1Result['bsn']) && $ouder1Result['bsn'] !== '-1') {
            $ouder1Data = getPersonFromPostgresByBsn($ouder1Result['bsn']);
            if ($ouder1Data) {
                $ouders[] = transformToHaalCentraalFormat($ouder1Data);
            }
        }
        
        // Ouder 2 via vdr_ax
        $stmt2 = $pdo->prepare("
            SELECT DISTINCT p.bsn 
            FROM vdr_ax v 
            JOIN pl p ON p.a1 = v.a1_ref AND p.a2 = v.a2_ref AND p.a3 = v.a3_ref 
            WHERE v.pl_id = :pl_id 
            AND v.ax = 'A' 
            AND v.hist = 'A' 
            AND p.bsn::text != '-1' 
            LIMIT 1
        ");
        $stmt2->execute(['pl_id' => $plId]);
        $ouder2Result = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if ($ouder2Result && isset($ouder2Result['bsn']) && $ouder2Result['bsn'] !== '-1') {
            $ouder2Data = getPersonFromPostgresByBsn($ouder2Result['bsn']);
            if ($ouder2Data) {
                $ouders[] = transformToHaalCentraalFormat($ouder2Data);
            }
        }
        
        return $ouders;
    } catch (PDOException $e) {
        error_log("Fout bij ophalen ouders: " . $e->getMessage());
        return [];
    }
}

// Haal nationaliteiten op voor een pl_id
function getNationaliteitenForPlId($plId) {
    $pdo = getPostgresPdo();
    if (!$pdo || !$plId) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT n.c_natio, nat.natio 
            FROM nat_ax n 
            LEFT JOIN natio nat ON nat.c_natio = n.c_natio 
            WHERE n.pl_id = :pl_id 
            AND n.ax = 'A' 
            AND n.hist = 'A'
        ");
        $stmt->execute(['pl_id' => $plId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $nationaliteiten = [];
        foreach ($results as $row) {
            $code = $row['c_natio'] ?? null;
            $omschrijving = $row['natio'] ?? null;
            
            if ($code) {
                $nationaliteiten[] = [
                    'nationaliteit' => [
                        'code' => trim($code),
                        'omschrijving' => $omschrijving ? trim($omschrijving) : null
                    ]
                ];
            }
        }
        
        return $nationaliteiten;
    } catch (PDOException $e) {
        error_log("Fout bij ophalen nationaliteiten: " . $e->getMessage());
        return [];
    }
}

// Haal persoon op basis van BSN
function getPersonFromPostgresByBsn($bsn) {
    $pdo = getPostgresPdo();
    if (!$pdo || !$bsn) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM v_personen_compleet_haal_centraal 
            WHERE bsn = :bsn 
            LIMIT 1
        ");
        $stmt->execute(['bsn' => $bsn]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fout bij ophalen persoon BSN $bsn: " . $e->getMessage());
        return null;
    }
}

// Transformeer naar Haal Centraal formaat (vereenvoudigd)
function transformToHaalCentraalFormat($data) {
    if (!$data) return null;
    
    // Split voornamen
    $voornamen = !empty($data['voornamen']) ? explode(' ', $data['voornamen']) : [];
    
    // Format datum
    $geboortedatum = null;
    if (!empty($data['geboortedatum'])) {
        $datum = $data['geboortedatum'];
        if (strlen($datum) === 8 && is_numeric($datum)) {
            // JJJJMMDD -> YYYY-MM-DD
            $geboortedatum = substr($datum, 0, 4) . '-' . substr($datum, 4, 2) . '-' . substr($datum, 6, 2);
        } else {
            $geboortedatum = $datum;
        }
    }
    
    // Map geslacht
    $geslacht = null;
    if (!empty($data['geslacht'])) {
        $geslachtMap = ['V' => 'vrouw', 'M' => 'man', 'O' => 'onbekend'];
        $geslacht = $geslachtMap[$data['geslacht']] ?? 'onbekend';
    }
    
    return [
        'burgerservicenummer' => $data['bsn'] ?? null,
        'naam' => [
            'voornamen' => $voornamen,
            'geslachtsnaam' => $data['geslachtsnaam'] ?? null,
            'voorvoegsel' => $data['voorvoegsel'] ?? null,
        ],
        'geboorte' => [
            'datum' => [
                'datum' => $geboortedatum
            ]
        ],
        'geslachtsaanduiding' => $geslacht,
        'aNummer' => $data['anr'] ?? null,
        '_links' => [
            'self' => [
                'href' => '/ingeschrevenpersonen/' . ($data['bsn'] ?? '')
            ]
        ]
    ];
}

// Haal relaties op voor een persoon
function getRelationsForPerson($bsn, $plId) {
    if (!$bsn || !$plId) {
        return [
            'partners' => [],
            'kinderen' => [],
            'ouders' => [],
            'nationaliteiten' => []
        ];
    }
    
    return [
        'partners' => getPartnersForPlId($plId, $bsn),
        'kinderen' => getKinderenForPlId($plId),
        'ouders' => getOudersForPlId($plId),
        'nationaliteiten' => getNationaliteitenForPlId($plId)
    ];
}

// Haal schema ID en register ID op
$schemaStmt = $nextcloudPdo->prepare("SELECT id FROM oc_openregister_schemas WHERE id = 6");
$schemaStmt->execute();
$schema = $schemaStmt->fetch(PDO::FETCH_ASSOC);

if (!$schema) {
    die("❌ Schema ID 6 niet gevonden\n");
}

$schemaId = $schema['id'];
$registerId = 2; // vrijBRPpersonen

echo "📊 Personen met relaties importeren naar OpenRegister...\n";
echo "Schema ID: $schemaId\n";
echo "Register ID: $registerId\n\n";

// Haal totaal aantal personen op
$pdo = getPostgresPdo();
$countStmt = $pdo->query("SELECT COUNT(*) FROM v_personen_compleet_haal_centraal");
$totalCount = (int)$countStmt->fetchColumn();

echo "Totaal aantal personen in probev database: $totalCount\n\n";

if ($totalCount == 0) {
    die("❌ Geen personen gevonden in probev database\n");
}

$imported = 0;
$errors = 0;
$batchSize = 10; // Kleine batches omdat we relaties ophalen
$offset = 0;

// Controleer of er al personen zijn geïmporteerd
$existingStmt = $nextcloudPdo->query("SELECT COUNT(*) FROM oc_openregister_objects WHERE register = $registerId AND schema = $schemaId");
$existingCount = $existingStmt->fetchColumn();
echo "Aantal personen al in OpenRegister: $existingCount\n\n";

// Importeer in batches
while ($offset < $totalCount) {
    echo "📥 Batch ophalen: offset $offset, limit $batchSize...\n";
    $personen = getPersonenFromPostgres($offset, $batchSize);
    
    if (empty($personen)) {
        echo "Geen personen meer gevonden, stoppen.\n";
        break;
    }
    
    echo "Gevonden in deze batch: " . count($personen) . "\n";
    
    foreach ($personen as $persoon) {
        try {
            $bsn = $persoon['bsn'] ?? null;
            if (!$bsn) {
                continue;
            }
            
            // Check of persoon al bestaat
            $checkStmt = $nextcloudPdo->prepare("
                SELECT COUNT(*) FROM oc_openregister_objects 
                WHERE register = ? AND schema = ? 
                AND JSON_EXTRACT(object, '$.bsn') = ?
            ");
            $checkStmt->execute([$registerId, $schemaId, $bsn]);
            if ($checkStmt->fetchColumn() > 0) {
                // Update bestaande persoon met relaties
                echo "  Updaten persoon BSN: $bsn\n";
                
                // Haal pl_id op
                $plId = getPlIdFromBsn($bsn);
                
                // Haal relaties op
                $relaties = getRelationsForPerson($bsn, $plId);
                
                // Haal huidige object op
                $getStmt = $nextcloudPdo->prepare("
                    SELECT object FROM oc_openregister_objects 
                    WHERE register = ? AND schema = ? 
                    AND JSON_EXTRACT(object, '$.bsn') = ?
                    LIMIT 1
                ");
                $getStmt->execute([$registerId, $schemaId, $bsn]);
                $existingObject = $getStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingObject) {
                    $currentData = json_decode($existingObject['object'], true);
                    $currentData['_embedded'] = $relaties;
                    
                    // Update object
                    $updateStmt = $nextcloudPdo->prepare("
                        UPDATE oc_openregister_objects 
                        SET object = ?, updated = NOW()
                        WHERE register = ? AND schema = ? 
                        AND JSON_EXTRACT(object, '$.bsn') = ?
                    ");
                    $updateStmt->execute([
                        json_encode($currentData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        $registerId,
                        $schemaId,
                        $bsn
                    ]);
                    
                    $imported++;
                }
            } else {
                // Nieuwe persoon - voeg relaties toe
                echo "  Nieuwe persoon BSN: $bsn\n";
                
                // Haal pl_id op
                $plId = getPlIdFromBsn($bsn);
                
                // Haal relaties op
                $relaties = getRelationsForPerson($bsn, $plId);
                
                // Voeg _embedded toe aan persoon object
                $persoon['_embedded'] = $relaties;
                
                // Genereer UUID
                $uuid = bin2hex(random_bytes(16));
                $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20, 12);
                
                // Maak object JSON
                $object = json_encode($persoon, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                // Voeg object toe
                $insertStmt = $nextcloudPdo->prepare("
                    INSERT INTO oc_openregister_objects 
                    (uuid, version, register, schema, object, created, updated)
                    VALUES 
                    (?, '0.0.1', ?, ?, ?, NOW(), NOW())
                ");
                
                $insertStmt->execute([
                    $uuid,
                    $registerId,
                    $schemaId,
                    $object
                ]);
                
                $imported++;
            }
            
            if ($imported % 10 == 0) {
                echo "✅ $imported personen verwerkt...\n";
            }
        } catch (PDOException $e) {
            $errors++;
            if ($errors <= 10) {
                echo "❌ Fout bij importeren: " . $e->getMessage() . "\n";
            }
        }
    }
    
    $offset += $batchSize;
    echo "Batch voltooid. Totaal verwerkt: $imported\n\n";
}

echo "\n✅ Import voltooid!\n";
echo "Verwerkt: $imported\n";
echo "Fouten: $errors\n";

// Controleer resultaat
$countStmt = $nextcloudPdo->query("SELECT COUNT(*) FROM oc_openregister_objects WHERE schema = $schemaId");
$total = $countStmt->fetchColumn();
echo "\nTotaal objecten in schema: $total\n";

