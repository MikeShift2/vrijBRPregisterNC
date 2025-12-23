<?php
/**
 * Migreer bestaande platte objecten naar nested structuur
 * Transformeert oude velden naar nieuwe nested format
 */

// Database configuratie
$nextcloudPdo = new PDO(
    'mysql:host=nextcloud-db;dbname=nextcloud',
    'nextcloud_user',
    'nextcloud_secure_pass_2024'
);
$nextcloudPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$schemaId = 6;  // Personen schema
$batchSize = 100;

/**
 * Transformeer plat object naar nested structuur
 */
function migrateToNested(array $oldObject): array {
    $nested = [];
    
    // BSN → burgerservicenummer
    if (isset($oldObject['bsn'])) {
        $nested['burgerservicenummer'] = $oldObject['bsn'];
    } elseif (isset($oldObject['burgerservicenummer'])) {
        $nested['burgerservicenummer'] = $oldObject['burgerservicenummer'];
    }
    
    // A-nummer
    if (isset($oldObject['anr']) || isset($oldObject['aNummer'])) {
        $nested['aNummer'] = $oldObject['aNummer'] ?? $oldObject['anr'];
    }
    
    // Naam (nested)
    if (isset($oldObject['voornamen']) || isset($oldObject['geslachtsnaam']) || isset($oldObject['voorvoegsel'])) {
        $nested['naam'] = [];
        if (isset($oldObject['voornamen'])) {
            $nested['naam']['voornamen'] = $oldObject['voornamen'];
        }
        if (isset($oldObject['voorvoegsel'])) {
            $nested['naam']['voorvoegsel'] = $oldObject['voorvoegsel'];
        }
        if (isset($oldObject['geslachtsnaam'])) {
            $nested['naam']['geslachtsnaam'] = $oldObject['geslachtsnaam'];
        }
    }
    
    // Geboorte (nested)
    if (isset($oldObject['geboortedatum']) || isset($oldObject['geboorteplaats'])) {
        $nested['geboorte'] = [];
        
        // Datum
        if (isset($oldObject['geboortedatum'])) {
            $dateStr = $oldObject['geboortedatum'];
            
            // Als het al ISO format is, gebruik dat
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                $parts = explode('-', $dateStr);
                $nested['geboorte']['datum'] = [
                    'datum' => $dateStr,
                    'jaar' => (int)$parts[0],
                    'maand' => (int)$parts[1],
                    'dag' => (int)$parts[2]
                ];
            }
            // Als het YYYYMMDD format is
            elseif (strlen($dateStr) === 8 && ctype_digit($dateStr)) {
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
        }
        
        // Plaats
        if (isset($oldObject['geboorteplaats'])) {
            $nested['geboorte']['plaats'] = $oldObject['geboorteplaats'];
        }
        
        // Land
        if (isset($oldObject['geboorteland_code']) || isset($oldObject['geboorteland_omschrijving'])) {
            $nested['geboorte']['land'] = [];
            if (isset($oldObject['geboorteland_code'])) {
                $nested['geboorte']['land']['code'] = $oldObject['geboorteland_code'];
            }
            if (isset($oldObject['geboorteland_omschrijving'])) {
                $nested['geboorte']['land']['omschrijving'] = $oldObject['geboorteland_omschrijving'];
            }
        }
    }
    
    // Geslacht (nested)
    if (isset($oldObject['geslacht']) || isset($oldObject['geslachtsaanduiding'])) {
        $geslachtCode = $oldObject['geslacht'] ?? strtoupper(substr($oldObject['geslachtsaanduiding'] ?? '', 0, 1));
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
    
    // Verblijfplaats (nested)
    $verblijfplaatsVelden = [
        'verblijfplaats_straatnaam' => 'straatnaam',
        'verblijfplaats_huisnummer' => 'huisnummer',
        'verblijfplaats_huisletter' => 'huisletter',
        'verblijfplaats_huisnummertoevoeging' => 'huisnummertoevoeging',
        'verblijfplaats_postcode' => 'postcode',
        'verblijfplaats_woonplaats' => 'woonplaats'
    ];
    
    $hasVerblijfplaats = false;
    $verblijfplaats = [];
    
    foreach ($verblijfplaatsVelden as $oldKey => $newKey) {
        if (isset($oldObject[$oldKey])) {
            $verblijfplaats[$newKey] = $oldObject[$oldKey];
            $hasVerblijfplaats = true;
        }
    }
    
    // Land
    if (isset($oldObject['verblijfplaats_land_code']) || isset($oldObject['verblijfplaats_land_omschrijving'])) {
        $verblijfplaats['land'] = [];
        if (isset($oldObject['verblijfplaats_land_code'])) {
            $verblijfplaats['land']['code'] = $oldObject['verblijfplaats_land_code'];
        }
        if (isset($oldObject['verblijfplaats_land_omschrijving'])) {
            $verblijfplaats['land']['omschrijving'] = $oldObject['verblijfplaats_land_omschrijving'];
        }
        $hasVerblijfplaats = true;
    }
    
    if ($hasVerblijfplaats) {
        $nested['verblijfplaats'] = $verblijfplaats;
    }
    
    // _embedded (behouden)
    if (isset($oldObject['_embedded'])) {
        $nested['_embedded'] = $oldObject['_embedded'];
    }
    
    // _metadata (nieuwe interne velden)
    $metadata = [];
    if (isset($oldObject['pl_id'])) {
        $metadata['pl_id'] = (int)$oldObject['pl_id'];
    }
    if (isset($oldObject['ax'])) {
        $metadata['ax'] = $oldObject['ax'];
    }
    if (isset($oldObject['hist'])) {
        $metadata['hist'] = $oldObject['hist'];
    }
    
    if (!empty($metadata)) {
        $nested['_metadata'] = $metadata;
    }
    
    return $nested;
}

// Main migration logic
echo "============================================================\n";
echo "🔄 Migratie: Platte Objecten → Nested Structuur\n";
echo "============================================================\n\n";

// Tel objecten
$countStmt = $nextcloudPdo->prepare("
    SELECT COUNT(*) FROM oc_openregister_objects 
    WHERE schema = ?
");
$countStmt->execute([$schemaId]);
$totalCount = $countStmt->fetchColumn();

echo "Totaal aantal objecten te migreren: $totalCount\n\n";

$migrated = 0;
$skipped = 0;
$errors = 0;
$offset = 0;

while ($offset < $totalCount) {
    echo "📦 Batch verwerken: offset $offset...\n";
    
    // Haal batch op
    $selectStmt = $nextcloudPdo->prepare("
        SELECT uuid, object 
        FROM oc_openregister_objects 
        WHERE schema = :schema
        ORDER BY uuid
        LIMIT :limit OFFSET :offset
    ");
    $selectStmt->bindValue(':schema', $schemaId, PDO::PARAM_INT);
    $selectStmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
    $selectStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $selectStmt->execute();
    $objects = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($objects)) {
        echo "Geen objecten meer, stoppen.\n";
        break;
    }
    
    foreach ($objects as $row) {
        try {
            $oldObject = json_decode($row['object'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors++;
                continue;
            }
            
            // Check of al nested (heeft naam.voornamen ipv voornamen)
            if (isset($oldObject['naam']) && is_array($oldObject['naam'])) {
                $skipped++;
                continue;  // Al gemigreerd
            }
            
            // Migreer naar nested
            $nestedObject = migrateToNested($oldObject);
            $objectJson = json_encode($nestedObject, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // Update object
            $updateStmt = $nextcloudPdo->prepare("
                UPDATE oc_openregister_objects 
                SET object = ?, updated = NOW()
                WHERE uuid = ?
            ");
            $updateStmt->execute([$objectJson, $row['uuid']]);
            
            $migrated++;
            
            if ($migrated % 100 === 0) {
                echo "  ✅ Gemigreerd: $migrated, Overgeslagen: $skipped\n";
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "  ❌ Fout bij migreren UUID {$row['uuid']}: " . $e->getMessage() . "\n";
        }
    }
    
    $offset += $batchSize;
}

echo "\n============================================================\n";
echo "✅ Migratie voltooid!\n";
echo "============================================================\n";
echo "Gemigreerd: $migrated objecten\n";
echo "Overgeslagen (al nested): $skipped objecten\n";
echo "Fouten: $errors objecten\n";
echo "\nVolgende stap:\n";
echo "  • Test een object: docker exec nextcloud-db mariadb -u nextcloud_user -pnextcloud_secure_pass_2024 nextcloud -e \"SELECT object FROM oc_openregister_objects WHERE schema=6 LIMIT 1\\G\"\n";
echo "  • Check via API: curl http://localhost:8080/apps/openregister/vrijbrppersonen/personen\n";
