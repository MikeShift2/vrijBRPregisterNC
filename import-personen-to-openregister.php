<?php
/**
 * Script om Personen data te importeren van PostgreSQL naar OpenRegister
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

// Verbind met PostgreSQL via docker exec (omdat PDO mogelijk niet beschikbaar is)
function getPersonenFromPostgres($offset = 0, $limit = 1000) {
    // Haal data op in batches - gebruik expliciet schema naam
    $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT json_agg(row_to_json(t)) FROM (SELECT * FROM probev.\\\"Personen\\\" ORDER BY id LIMIT $limit OFFSET $offset) t;\" 2>&1";
    $output = shell_exec($cmd);
    $json = trim($output);
    
    if (empty($json) || $json === '' || $json === '(0 rows)' || $json === 'null' || strpos($json, 'SET') !== false) {
        // Verwijder "SET" output als die er is
        $json = preg_replace('/^SET\s*\n?/m', '', $json);
        $json = trim($json);
    }
    
    if (empty($json) || $json === '' || $json === '(0 rows)' || $json === 'null') {
        return [];
    }
    
    // Verwijder eventuele extra output, pak alleen JSON array
    $json = preg_replace('/.*?(\[.*\]).*/s', '$1', $json);
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
        echo "Raw output (first 500 chars): " . substr($output, 0, 500) . "\n";
        return [];
    }
    
    return $data ?: [];
}

function getPersonenCount() {
    $countCmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"SELECT COUNT(*) FROM probev.\\\"Personen\\\";\" 2>&1";
    $countOutput = shell_exec($countCmd);
    // Verwijder "SET" en andere output, pak alleen het nummer
    $countOutput = preg_replace('/.*?(\d+).*/s', '$1', trim($countOutput));
    return (int)$countOutput;
}

// Haal schema ID en register ID op
$schemaStmt = $nextcloudPdo->prepare("SELECT id FROM oc_openregister_schemas WHERE title = 'Personen'");
$schemaStmt->execute();
$schema = $schemaStmt->fetch(PDO::FETCH_ASSOC);

if (!$schema) {
    die("❌ Schema 'Personen' niet gevonden\n");
}

$schemaId = $schema['id'];

// Haal register ID op (vrijBRPpersonen = register 2)
$registerStmt = $nextcloudPdo->prepare("SELECT id FROM oc_openregister_registers WHERE id = 2");
$registerStmt->execute();
$register = $registerStmt->fetch(PDO::FETCH_ASSOC);
$registerId = $register['id'] ?? 2;

echo "📊 Personen importeren naar OpenRegister...\n";
echo "Schema ID: $schemaId\n";
echo "Register ID: $registerId\n\n";

// Haal totaal aantal personen op
$totalCount = getPersonenCount();
echo "Totaal aantal personen in probev database: $totalCount\n\n";

if ($totalCount == 0) {
    die("❌ Geen personen gevonden in probev database\n");
}

$imported = 0;
$errors = 0;
$batchSize = 1000;
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
            // Check of persoon al bestaat (op basis van BSN)
            $bsn = $persoon['bsn'] ?? null;
            if ($bsn) {
                $checkStmt = $nextcloudPdo->prepare("
                    SELECT COUNT(*) FROM oc_openregister_objects 
                    WHERE register = ? AND schema = ? 
                    AND JSON_EXTRACT(object, '$.bsn') = ?
                ");
                $checkStmt->execute([$registerId, $schemaId, $bsn]);
                if ($checkStmt->fetchColumn() > 0) {
                    // Skip als al bestaat
                    continue;
                }
            }
            
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
            
            if ($imported % 100 == 0) {
                echo "✅ $imported personen geïmporteerd...\n";
            }
        } catch (PDOException $e) {
            $errors++;
            if ($errors <= 10) {
                echo "❌ Fout bij importeren: " . $e->getMessage() . "\n";
            }
        }
    }
    
    $offset += $batchSize;
    echo "Batch voltooid. Totaal geïmporteerd: $imported\n\n";
}

echo "\n✅ Import voltooid!\n";
echo "Geïmporteerd: $imported\n";
echo "Fouten: $errors\n";

// Controleer resultaat
$countStmt = $nextcloudPdo->query("SELECT COUNT(*) FROM oc_openregister_objects WHERE schema = $schemaId");
$total = $countStmt->fetchColumn();
echo "\nTotaal objecten in schema: $total\n";

