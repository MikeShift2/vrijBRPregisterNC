<?php
/**
 * Script om bevax tabellen te importeren als OpenRegister schemas
 */

// Database configuratie - gebruik Docker host IP
$dbHost = 'host.docker.internal'; // Voor MariaDB via Docker
$dbName = 'nextcloud';
$dbUser = 'nextcloud_user';
$dbPass = 'nextcloud_secure_pass_2024';

// Verbind met MariaDB via Docker
try {
    // Probeer eerst directe connectie
    $pdo = @new PDO("mysql:host=nextcloud-db;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    if (!$pdo) {
        // Fallback naar host.docker.internal
        $pdo = new PDO("mysql:host=$dbHost;port=3306;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("MariaDB verbinding gefaald: " . $e->getMessage() . "\n");
}

// Haal PostgreSQL gegevens op via docker exec
function getPostgresTables() {
    $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d vrijBRPauth -t -c \"SELECT table_name FROM information_schema.tables WHERE table_schema = 'bevax' ORDER BY table_name;\" 2>&1";
    $output = shell_exec($cmd);
    return array_filter(array_map('trim', explode("\n", $output)));
}

function getPostgresColumns($table) {
    $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d vrijBRPauth -t -c \"SELECT column_name FROM information_schema.columns WHERE table_schema = 'bevax' AND table_name = '$table' ORDER BY ordinal_position;\" 2>&1";
    $output = shell_exec($cmd);
    return array_filter(array_map('trim', explode("\n", $output)));
}

// Haal tabellen op uit PostgreSQL
$tables = getPostgresTables();

echo "📊 Bevax tabellen importeren als OpenRegister schemas...\n\n";
echo "Gevonden tabellen: " . count($tables) . "\n\n";

foreach ($tables as $table) {
    if (empty($table)) continue;
    
    echo "📋 Schema aanmaken voor tabel: $table\n";
    
    // Haal kolommen op
    $columns = getPostgresColumns($table);
    
    // Maak properties JSON
    $properties = [];
    foreach ($columns as $col) {
        if (!empty($col)) {
            $properties[$col] = ['type' => 'string'];
        }
    }
    
    $propertiesJson = json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $uuid = bin2hex(random_bytes(16));
    $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20, 12);
    
    // Voeg schema toe
    try {
        $stmt = $pdo->prepare("
            INSERT INTO oc_openregister_schemas 
            (uuid, version, title, description, properties, created, updated)
            VALUES 
            (?, '0.0.1', ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $uuid,
            $table,
            "Schema voor bevax tabel: $table",
            $propertiesJson
        ]);
        
        echo "✅ Schema '$table' toegevoegd\n";
    } catch (PDOException $e) {
        echo "❌ Fout bij toevoegen van schema '$table': " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Import voltooid!\n\n";
echo "Controleer de schemas:\n";
$stmt = $pdo->query("SELECT id, title FROM oc_openregister_schemas ORDER BY title");
$schemas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($schemas as $schema) {
    echo "  - {$schema['title']} (ID: {$schema['id']})\n";
}

