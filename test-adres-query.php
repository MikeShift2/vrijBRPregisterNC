<?php
/**
 * Test script om te controleren of de adres query werkt
 */

require_once __DIR__ . '/config/config.php';

use OCP\IDBConnection;

// Simuleer de query
$db = \OC::$server->get(IDBConnection::class);

$bsn = '100720432';
$registerId = 3;
$schemaId = 7;

$qb = $db->getQueryBuilder();
$qb->select('object')
   ->from('openregister_objects')
   ->where($qb->expr()->eq('register', $qb->createNamedParameter($registerId)))
   ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter($schemaId)))
   ->andWhere($qb->expr()->eq(
       $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.bsn') . '))'),
       $qb->createNamedParameter($bsn)
   ))
   ->setMaxResults(1);

$result = $qb->executeQuery();
$row = $result->fetch();

echo "=== Test Adres Query ===\n";
echo "BSN: $bsn\n";
echo "Register: $registerId\n";
echo "Schema: $schemaId\n\n";

if ($row === false) {
    echo "❌ Geen resultaat gevonden\n";
} else {
    echo "✅ Resultaat gevonden:\n";
    echo "Row type: " . gettype($row) . "\n";
    echo "Row keys: " . (is_array($row) ? implode(', ', array_keys($row)) : 'N/A') . "\n";
    
    $objectJson = null;
    if (isset($row['object'])) {
        $objectJson = $row['object'];
        echo "✅ Object gevonden via 'object' key\n";
    } elseif (isset($row[0])) {
        $objectJson = $row[0];
        echo "✅ Object gevonden via index 0\n";
    } elseif (is_array($row) && count($row) > 0) {
        $objectJson = reset($row);
        echo "✅ Object gevonden via reset()\n";
    }
    
    if ($objectJson) {
        $adresData = json_decode($objectJson, true);
        if ($adresData && is_array($adresData)) {
            echo "\n✅ AdresData gedecodeerd:\n";
            print_r($adresData);
            
            unset($adresData['bsn']);
            echo "\n✅ AdresData na unset BSN:\n";
            print_r($adresData);
        } else {
            echo "❌ Kon adresData niet decoderen\n";
        }
    } else {
        echo "❌ Geen objectJson gevonden\n";
    }
}







