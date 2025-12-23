<?php
/**
 * Cache Service voor Haal Centraal API
 * 
 * Implementeert caching voor veelgebruikte queries
 */

namespace OCA\OpenRegister\Service\HaalCentraal;

use OCP\ICache;
use OCP\ICacheFactory;

class CacheService {
    private ICache $cache;
    private int $defaultTtl = 3600; // 1 uur
    
    public function __construct(ICacheFactory $cacheFactory) {
        $this->cache = $cacheFactory->createDistributed('haalcentraal');
    }
    
    /**
     * Haal data uit cache of voer callback uit en cache resultaat
     */
    public function get(string $key, callable $callback, ?int $ttl = null): array {
        $cached = $this->cache->get($key);
        
        if ($cached !== null) {
            $data = json_decode($cached, true);
            if ($data !== null) {
                return $data;
            }
        }
        
        // Voer callback uit
        $data = $callback();
        
        // Cache resultaat
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Sla data op in cache
     */
    public function set(string $key, array $data, ?int $ttl = null): void {
        $ttl = $ttl ?? $this->defaultTtl;
        $this->cache->set($key, json_encode($data), $ttl);
    }
    
    /**
     * Verwijder data uit cache
     */
    public function remove(string $key): void {
        $this->cache->remove($key);
    }
    
    /**
     * Invalideer cache voor BSN
     */
    public function invalidateBsn(string $bsn): void {
        $keys = [
            "ingeschrevenpersoon:{$bsn}",
            "partners:{$bsn}",
            "kinderen:{$bsn}",
            "ouders:{$bsn}",
            "verblijfplaats:{$bsn}",
            "nationaliteiten:{$bsn}",
            "verblijfplaatshistorie:{$bsn}"
        ];
        
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }
    
    /**
     * Genereer cache key voor endpoint
     */
    public function generateKey(string $endpoint, array $params = []): string {
        $key = $endpoint;
        
        if (!empty($params)) {
            // Sorteer params voor consistente keys
            ksort($params);
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Clear alle cache
     */
    public function clear(): void {
        $this->cache->clear();
    }
}







