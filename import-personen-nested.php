<?php
/**
 * Import personen met nested objects structuur
 * Transformeert platte probev data naar Haal Centraal nested format
 */

// Database configuratie
$nextcloudPdo = new PDO(
    'mysql:host=nextcloud-db;dbname=nextcloud',
    'nextcloud_user',
    'nextcloud_secure_pass_2024'
);
$nextcloudPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$registerId = 2;  // vrijBRPpersonen
$schemaId = 6;    // Personen schema
$batchSize = 100;

/**
 * Transformeer platte data naar nested Haal Centraal structuur
 */
function transformToNestedObject(array $flatData): array {
    $nested = [
        'burgerservicenummer' => $flatData['bsn'] ?? null
    ];
    
    // A-nummer (indien aanwezig)
    if (!empty($flatData['anr'])) {
        $nested['aNummer'] = $flatData['anr'];
    }
    
    // Naam (nested object)
    $nested['naam'] = [];
    if (!empty($flatData['voornamen'])) {
        $nested['naam']['voornamen'] = $flatData['voornamen'];
    }
    if (!empty($flatData['voorvoegsel'])) {
        $nested['naam']['voorvoegsel'] = $flatData['voorvoegsel'];
    }
    if (!empty($flatData['geslachtsnaam'])) {
        $nested['naam']['geslachtsnaam'] = $flatData['geslachtsnaam'];
    }
    
    // Geboorte (nested object)
    if (!empty($flatData['geboortedatum'])) {
        $nested['geboorte'] = [
            'datum' => []
        ];
        
        // Convert YYYYMMDD naar ISO date
        $dateStr = $flatData['geboortedatum'];
        if (strlen($dateStr) === 8) {
            $year = substr($dateStr, 0, 4);
            $month = substr($dateStr, 4, 2);
            $day = substr($dateStr, 6, 2);
            
            $nested['geboorte']['datum'] = [
                'datum' => "$year-$month-$day",
                'jaar' => (int)$year,
                'maand' => (int)$month,
                'dag' => (int)$day
            ];
        }
        
        if (!empty($flatData['geboorteplaats'])) {
            $nested['geboorte']['plaats'] = $flatData['geboorteplaats'];
        }
        
        if (!empty($flatData['geboorteland'])) {
            $nested['geboorte']['land'] = [
                'omschrijving' => $flatData['geboorteland']
            ];
            if (!empty($flatData['geboorteland_code'])) {
                $nested['geboorte']['land']['code'] = $flatData['geboorteland_code'];
            }
        }
    }
    
    // Geslacht (nested object)
    if (!empty($flatData['geslacht'])) {
        $geslachtCode = strtoupper($flatData['geslacht']);
        $geslachtMap = [
            'M' => 'man',
            'V' => 'vrouw',
            'O' => 'onbekend'
        ];
        
        $nested['geslacht'] = [
            'code' => $geslachtCode,
            'omschrijving' => $geslachtMap[$geslachtCode] ?? 'onbekend'
        ];
    }
    
    // Verblijfplaats (nested object)
    $hasVerblijfplaats = false;
    $verblijfplaats = [];
    
    if (!empty($flatData['straatnaam'])) {
        $verblijfplaats['straatnaam'] = $flatData['straatnaam'];
        $hasVerblijfplaats = true;
    }
    if (!empty($flatData['huisnummer'])) {
        $verblijfplaats['huisnummer'] = (int)$flatData['huisnummer'];
        $hasVerblijfplaats = true;
    }
    if (!empty($flatData['huisnummertoevoeging'])) {
        $verblijfplaats['huisnummertoevoeging'] = $flatData['huisnummertoevoeging'];
        $hasVerblijfplaats = true;
    }
    if (!empty($flatData['postcode'])) {
        $verblijfplaats['postcode'] = $flatData['postcode'];
        $hasVerblijfplaats = true;
    }
    if (!empty($flatData['woonplaats'])) {
        $verblijfplaats['woonplaats'] = $flatData['woonplaats'];
        $hasVerblijfplaats = true;
    }
    
    if ($hasVerblijfplaats) {
        $nested['verblijfplaats'] = $verblijfplaats;
    }
    
    // _embedded (behouden als al aanwezig)
    if (!empty($flatData['_embedded'])) {
        $nested['_embedded'] = $flatData['_embedded'];
    }
    
    // _metadata (interne velden)
    $metadata = [];
    if (isset($flatData['pl_id'])) {
        $metadata['pl_id'] = (int)$flatData['pl_id'];
    }
    if (isset($flatData['ax'])) {
        $metadata['ax'] = $flatData['ax'];
    }
    if (isset($flatData['hist'])) {
        $metadata['hist'] = $flatData['hist'];
    }
    
    if (!empty($metadata)) {
        $nested['_metadata'] = $metadata;
    }
    
    return $nested;
}

/**
 * Haal personen op uit PostgreSQL probev schema
 */
function getPersonenFromPostgres($offset = 0, $limit = 100): array {
    $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"
        SELECT json_agg(row_to_json(t)) FROM (
            SELECT 
                pl.bsn,
                pl.pl_id,
                i.anr,
                v.voorn as voornamen,
                n.naam as geslachtsnaam,
                i.d_geb as geboortedatum,
                i.geslacht,
                st.straat as straatnaam,
                vb.hnr as huisnummer,
                vb.hnr_toev as huisnummertoevoeging,
                vb.postcode,
                pl2.plaats as woonplaats,
                i.ax,
                i.hist
            FROM probev.pl pl
            LEFT JOIN probev.inw_ax i ON i.pl_id = pl.pl_id AND i.ax = 'A' AND i.hist = 'A'
            LEFT JOIN probev.voorn v ON v.c_voorn = i.c_voorn
            LEFT JOIN probev.naam n ON n.c_naam = i.c_naam
            LEFT JOIN probev.vb_ax vb ON vb.pl_id = pl.pl_id AND vb.ax = 'A' AND vb.hist = 'A'
            LEFT JOIN probev.straat st ON st.c_straat = vb.c_straat
            LEFT JOIN probev.plaats pl2 ON pl2.p_plaats = vb.p_plaats
            WHERE pl.bsn IS NOT NULL
            ORDER BY pl.pl_id
            LIMIT $limit OFFSET $offset
        ) t;
    \" 2>&1";
    
    $output = shell_exec($cmd);
    $json = trim($output);
    
    // Clean output
    $json = preg_replace('/^SET\s*\n?/m', '', $json);
    $json = trim($json);
    
    if (empty($json) || $json === '(0 rows)' || $json === 'null') {
        return [];
    }
    
    $json = preg_replace('/.*?(\[.*\]).*/s', '$1', $json);
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
        return [];
    }
    
    return $data ?: [];
}

/**
 * Tel totaal aantal personen
 */
function getPersonenCount(): int {
    $cmd = "docker exec mvpvrijbrp2025-db-1 psql -U postgres -d bevax -t -A -c \"
        SELECT COUNT(*) FROM probev.pl WHERE bsn IS NOT NULL;
    \" 2>&1";
    
    $output = shell_exec($cmd);
    $output = preg_replace('/.*?(\d+).*/s', '$1', trim($output));
    return (int)$output;
}

// Main import logic
echo "============================================================\n";
echo "📦 Import Personen met Nested Objects\n";
echo "============================================================\n\n";

$totalCount = getPersonenCount();
echo "Totaal aantal personen in probev: $totalCount\n";

// Tel bestaande personen
$existingStmt = $nextcloudPdo->prepare("
    SELECT COUNT(*) FROM oc_openregister_objects 
    WHERE register = ? AND schema = ?
");
$existingStmt->execute([$registerId, $schemaId]);
$existingCount = $existingStmt->fetchColumn();
echo "Aantal personen al in OpenRegister: $existingCount\n\n";

$imported = 0;
$skipped = 0;
$errors = 0;
$offset = 0;

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
                $skipped++;
                continue;
            }
            
            // Check of persoon al bestaat
            $checkStmt = $nextcloudPdo->prepare("
                SELECT COUNT(*) FROM oc_openregister_objects 
                WHERE register = ? AND schema = ? 
                AND JSON_EXTRACT(object, '$.burgerservicenummer') = ?
            ");
            $checkStmt->execute([$registerId, $schemaId, $bsn]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $skipped++;
                continue;
            }
            
            // Transformeer naar nested object
            $nestedObject = transformToNestedObject($persoon);
            
            // Genereer UUID
            $uuid = bin2hex(random_bytes(16));
            $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . 
                    substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . 
                    substr($uuid, 20, 12);
            
            // Maak object JSON
            $objectJson = json_encode($nestedObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // Insert object
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
                $objectJson
            ]);
            
            $imported++;
            
            if ($imported % 10 === 0) {
                echo "  ✅ Geïmporteerd: $imported, Overgeslagen: $skipped\n";
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "  ❌ Fout bij importeren persoon: " . $e->getMessage() . "\n";
        }
    }
    
    $offset += $batchSize;
}

echo "\n============================================================\n";
echo "✅ Import voltooid!\n";
echo "============================================================\n";
echo "Geïmporteerd: $imported\n";
echo "Overgeslagen: $skipped\n";
echo "Fouten: $errors\n";
echo "\nVolgende stap:\n";
echo "  • Verifieer via: curl http://localhost:8080/apps/openregister/vrijbrppersonen/personen\n";
echo "  • Check nested structure: burgerservicenummer, naam.voornamen, geboorte.datum\n";
