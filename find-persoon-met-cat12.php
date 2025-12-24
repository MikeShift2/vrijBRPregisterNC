<?php
/**
 * Script om een persoon te vinden met categorie 12 (Verblijfsaantekening EU/EER) gevuld
 */

// Directe PostgreSQL connectie voor probev
$pdoProbev = new PDO(
    'pgsql:host=host.docker.internal;port=5432;dbname=bevax;options=-csearch_path=probev',
    'postgres',
    'postgres'
);
$pdoProbev->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Nextcloud database connectie
$dbHost = getenv('POSTGRES_HOST') ?: 'nextcloud-db';
$dbName = getenv('POSTGRES_DB') ?: 'nextcloud';
$dbUser = getenv('POSTGRES_USER') ?: 'nextcloud';
$dbPass = getenv('POSTGRES_PASSWORD') ?: 'nextcloud';

try {
    $pdoNextcloud = new PDO(
        "pgsql:host=$dbHost;port=5432;dbname=$dbName",
        $dbUser,
        $dbPass
    );
    $pdoNextcloud->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Kan niet verbinden met Nextcloud database: " . $e->getMessage() . "\n";
    $pdoNextcloud = null;
}

echo "Zoeken naar personen met categorie 12 (Verblijfsaantekening EU/EER)...\n\n";

// Eerst kijken welke tabellen er zijn die mogelijk verblijfsaantekening data bevatten
try {
    // Zoek naar tabellen die mogelijk verblijfsaantekening data bevatten
    $stmt = $pdoProbev->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'probev' 
        AND (table_name LIKE '%aant%' OR table_name LIKE '%eu%' OR table_name LIKE '%eer%')
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Gevonden tabellen:\n";
    foreach ($tables as $table) {
        echo "  - " . $table['table_name'] . "\n";
    }
    echo "\n";
    
    // Zoek in probev tabellen - verblijfsaantekening data staat waarschijnlijk in aant_pl of aant tabel
    echo "Zoeken in aant_pl tabel...\n\n";
    
    // Eerst kijken naar de structuur van aant_pl
    $stmt = $pdoProbev->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'probev' 
        AND table_name = 'aant_pl'
        ORDER BY ordinal_position
        LIMIT 20
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Kolommen in aant_pl:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
    }
    echo "\n";
    
    // Zoek naar personen met verblijfsaantekening data
    // aant_pl gebruikt a1, a2, a3 om te koppelen aan pl
    $stmt = $pdoProbev->query("
        SELECT 
            a.a1,
            a.a2,
            a.a3,
            a.v_regel,
            a.v_aant_pl,
            a.tekst as aantekening,
            p.bsn::text as bsn,
            p.pl_id
        FROM aant_pl a
        JOIN pl p ON p.a1 = a.a1 AND p.a2 = a.a2 AND p.a3 = a.a3
        WHERE p.bsn IS NOT NULL
        AND p.bsn::text != '-1'
        AND a.tekst IS NOT NULL
        AND a.tekst != ''
        LIMIT 10
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "Gevonden personen met verblijfsaantekening (categorie 12):\n\n";
        foreach ($results as $row) {
            $bsn = $row['bsn'] ?? 'Onbekend';
            $aantekening = $row['aantekening'] ?? 'Onbekend';
            $vRegel = $row['v_regel'] ?? '-';
            $vAantPl = $row['v_aant_pl'] ?? '-';
            
            echo "BSN: " . $bsn . "\n";
            echo "PL_ID: " . ($row['pl_id'] ?? 'Onbekend') . "\n";
            echo "Aantekening: " . $aantekening . "\n";
            echo "V_regel: " . $vRegel . "\n";
            echo "V_aant_pl: " . $vAantPl . "\n";
            echo "---\n";
        }
    } else {
        echo "Geen personen gevonden met verblijfsaantekening in aant_pl.\n";
        echo "Zoeken in andere aant tabellen...\n\n";
        
        // Bekijk structuur van aant tabel
        $stmt = $pdoProbev->query("
            SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_schema = 'probev' 
            AND table_name = 'aant'
            ORDER BY ordinal_position
            LIMIT 20
        ");
        $aantColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Kolommen in aant tabel:\n";
        foreach ($aantColumns as $col) {
            echo "  - " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
        }
        echo "\n";
        
        // Zoek direct in aant tabel en koppel aan personen via a1, a2, a3
        echo "Zoeken direct in aant tabel...\n\n";
        
        $stmt = $pdoProbev->query("
            SELECT 
                a.v_aant,
                a.aant as aantekening,
                a.d_geld,
                a.v_geld,
                a.a1,
                a.a2,
                a.a3,
                p.bsn::text as bsn,
                p.pl_id
            FROM aant a
            JOIN pl p ON p.a1 = a.a1 AND p.a2 = a.a2 AND p.a3 = a.a3
            WHERE a.hist = 'A'
            AND a.aant IS NOT NULL
            AND a.aant != ''
            AND p.bsn IS NOT NULL
            AND p.bsn::text != '-1'
            LIMIT 10
        ");
        $aantResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($aantResults) > 0) {
            echo "Gevonden personen met verblijfsaantekening (categorie 12):\n\n";
            foreach ($aantResults as $row) {
                echo "BSN: " . $row['bsn'] . "\n";
                echo "PL_ID: " . $row['pl_id'] . "\n";
                echo "Aantekening code: " . ($row['v_aant'] ?? 'Onbekend') . "\n";
                echo "Aantekening: " . ($row['aantekening'] ?? 'Onbekend') . "\n";
                
                // Format datum
                function formatDatum($datum) {
                    if ($datum == -1 || $datum === null) return '-';
                    $datumStr = (string)$datum;
                    if (strlen($datumStr) == 8) {
                        return substr($datumStr, 6, 2) . '-' . substr($datumStr, 4, 2) . '-' . substr($datumStr, 0, 4);
                    }
                    return $datumStr;
                }
                
                echo "Datum geldigheid: " . formatDatum($row['d_geld']) . "\n";
                echo "Volgnummer geldigheid: " . ($row['v_geld'] ?? '-') . "\n";
                echo "---\n";
            }
        } else {
            echo "Geen personen gevonden met verblijfsaantekening in aant tabel.\n";
            echo "Totaal aantal records in aant tabel: ";
            $stmt = $pdoProbev->query("SELECT COUNT(*) as count FROM aant WHERE hist = 'A'");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $count['count'] . "\n";
            
            // Probeer ook aant3 en aantek3 tabellen
            echo "\nZoeken in aant3 tabel...\n";
            $stmt = $pdoProbev->query("SELECT COUNT(*) as count FROM aant3");
            $countAant3 = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Aantal records in aant3: " . $countAant3['count'] . "\n";
            
            if ($countAant3['count'] > 0) {
                // Bekijk structuur van aant3
                $stmt = $pdoProbev->query("
                    SELECT column_name, data_type 
                    FROM information_schema.columns 
                    WHERE table_schema = 'probev' 
                    AND table_name = 'aant3'
                    ORDER BY ordinal_position
                    LIMIT 20
                ");
                $aant3Columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "Kolommen in aant3:\n";
                foreach ($aant3Columns as $col) {
                    echo "  - " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
                }
                echo "\n";
                
                // Zoek personen met aantekening in aant3
                if (in_array('a1', array_column($aant3Columns, 'column_name'))) {
                    $stmt = $pdoProbev->query("
                        SELECT 
                            a3.a1,
                            a3.a2,
                            a3.a3,
                            a3.*,
                            p.bsn::text as bsn,
                            p.pl_id
                        FROM aant3 a3
                        JOIN pl p ON p.a1 = a3.a1 AND p.a2 = a3.a2 AND p.a3 = a3.a3
                        WHERE p.bsn IS NOT NULL
                        AND p.bsn::text != '-1'
                        LIMIT 5
                    ");
                    $aant3Results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($aant3Results) > 0) {
                        echo "Gevonden personen met aantekening in aant3:\n\n";
                        $firstPerson = null;
                        foreach ($aant3Results as $row) {
                            if (!$firstPerson) {
                                $firstPerson = $row;
                            }
                            echo "BSN: " . ($row['bsn'] ?? 'Onbekend') . "\n";
                            echo "PL_ID: " . ($row['pl_id'] ?? 'Onbekend') . "\n";
                            echo "Aantekening: " . ($row['aant'] ?? 'Onbekend') . "\n";
                            echo "---\n";
                        }
                        
                        if ($firstPerson) {
                            echo "\n=== EERSTE PERSOON VOOR TEST ===\n";
                            echo "BSN: " . $firstPerson['bsn'] . "\n";
                            echo "PL_ID: " . $firstPerson['pl_id'] . "\n";
                            echo "Aantekening: " . $firstPerson['aant'] . "\n";
                        }
                    }
                }
            }
        }
        
        // Zoek ook in Nextcloud OpenRegister objects
        if ($pdoNextcloud) {
            echo "\nZoeken in Nextcloud OpenRegister objects...\n\n";
            
            $stmt = $pdoNextcloud->prepare("
                SELECT 
                    id,
                    object->>'burgerservicenummer' as bsn,
                    object->'naam'->>'geslachtsnaam' as geslachtsnaam,
                    object->'naam'->>'voornamen' as voornamen,
                    object->'verblijfsaantekening' as verblijfsaantekening
                FROM oc_openregister_objects
                WHERE register = 2
                AND schema = 6
                AND (
                    object::text LIKE '%verblijfsaantekening%' 
                    OR object::text LIKE '%aantekening%'
                    OR object->'verblijfsaantekening' IS NOT NULL
                )
                LIMIT 10
            ");
            $stmt->execute();
            $nextcloudResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($nextcloudResults) > 0) {
                echo "Gevonden personen met verblijfsaantekening in Nextcloud:\n\n";
                foreach ($nextcloudResults as $row) {
                    $bsn = $row['bsn'] ?? 'Onbekend';
                    $naam = ($row['geslachtsnaam'] ?? '') . ', ' . ($row['voornamen'] ?? '');
                    $aantekening = $row['verblijfsaantekening'];
                    
                    echo "BSN: " . $bsn . "\n";
                    echo "Naam: " . $naam . "\n";
                    if ($aantekening) {
                        if (is_string($aantekening)) {
                            $aantekeningData = json_decode($aantekening, true);
                            if ($aantekeningData) {
                                echo "Aantekening: " . json_encode($aantekeningData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                            } else {
                                echo "Aantekening: " . $aantekening . "\n";
                            }
                        } else {
                            echo "Aantekening: " . json_encode($aantekening, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                        }
                    } else {
                        echo "Aantekening: (in object aanwezig maar leeg)\n";
                    }
                    echo "---\n";
                }
            } else {
                echo "Geen personen gevonden met verblijfsaantekening in Nextcloud.\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Fout: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

