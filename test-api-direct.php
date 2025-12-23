<?php
/**
 * Direct API test script
 * Test the HaalCentraalBrpController directly
 */

// Bootstrap Nextcloud
require_once '/var/www/html/lib/base.php';

use OCA\OpenRegister\Controller\HaalCentraalBrpController;
use OCP\IRequest;
use OCP\IDBConnection;

// Create minimal request mock
$request = new class implements IRequest {
    private $params = ['bsn' => '216007574', '_limit' => '20'];
    
    public function getParam(string $key, $default = null) {
        return $this->params[$key] ?? $default;
    }
    
    // Other required methods...
    public function getHeader(string $name): string { return ''; }
    public function getMethod(): string { return 'GET'; }
    public function getRequestUri(): string { return '/test'; }
    public function getServerProtocol(): string { return 'HTTP/1.1'; }
    public function getUploadedFile(string $key) { return null; }
    public function getEnv(string $name) { return null; }
    public function getCookie(string $name) { return null; }
    public function passesCSRFCheck(): bool { return true; }
    public function passesStrictCookieCheck(): bool { return true; }
    public function passesLaxCookieCheck(): bool { return true; }
    public function getId(): string { return 'test'; }
    public function getRemoteAddress(): string { return '127.0.0.1'; }
    public function getServerHost(): string { return 'localhost'; }
    public function getInsecureServerHost(): string { return 'localhost'; }
    public function getRequestUriWithoutParams(): string { return '/test'; }
};

try {
    // Get DB connection
    $db = \OC::$server->getDatabaseConnection();
    
    // Direct database query test
    $qb = $db->getQueryBuilder();
    $qb->select('*')
       ->from('openregister_objects')
       ->where($qb->expr()->eq('register', $qb->createNamedParameter(2)))
       ->andWhere($qb->expr()->eq('schema', $qb->createNamedParameter(6)))
       ->andWhere($qb->expr()->eq(
           $qb->createFunction('JSON_UNQUOTE(JSON_EXTRACT(object, ' . $qb->createNamedParameter('$.burgerservicenummer') . '))'),
           $qb->createNamedParameter('216007574')
       ))
       ->setMaxResults(1);
    
    echo "=== TESTING DIRECT DATABASE QUERY ===\n\n";
    echo "SQL: " . $qb->getSQL() . "\n\n";
    
    $result = $qb->executeQuery();
    $rows = $result->fetchAll();
    
    echo "Found " . count($rows) . " rows\n\n";
    
    if (!empty($rows)) {
        $row = $rows[0];
        echo "ID: " . $row['id'] . "\n";
        echo "UUID: " . $row['uuid'] . "\n";
        
        $object = json_decode($row['object'], true);
        echo "\nObject data:\n";
        echo "BSN: " . ($object['burgerservicenummer'] ?? 'NOT FOUND') . "\n";
        echo "Naam: " . json_encode($object['naam'] ?? 'NOT FOUND') . "\n";
        echo "\n";
        
        echo "Full object:\n";
        echo json_encode($object, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "NO ROWS FOUND!\n";
        echo "\nDEBUG: Check if data exists:\n";
        
        $checkQb = $db->getQueryBuilder();
        $checkQb->select('id', 'object')
                ->from('openregister_objects')
                ->where($checkQb->expr()->eq('schema', $checkQb->createNamedParameter(6)))
                ->andWhere($checkQb->expr()->like('object', $checkQb->createNamedParameter('%216007574%')))
                ->setMaxResults(1);
        
        $checkResult = $checkQb->executeQuery();
        $checkRows = $checkResult->fetchAll();
        
        if (!empty($checkRows)) {
            echo "Found with LIKE query: ID " . $checkRows[0]['id'] . "\n";
            $obj = json_decode($checkRows[0]['object'], true);
            echo "Object keys: " . implode(', ', array_keys($obj)) . "\n";
            echo "BSN field value: " . json_encode($obj['burgerservicenummer'] ?? $obj['bsn'] ?? 'NONE') . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
