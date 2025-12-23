<?php
/**
 * Field Selection Service
 * 
 * Implementeert field selection (fields parameter) voor Haal Centraal API
 */

namespace OCA\OpenRegister\Service\HaalCentraal;

class FieldSelectionService {
    
    /**
     * Pas field selection toe op data volgens fields parameter
     * 
     * @param array $data Volledige data object
     * @param string|null $fieldsParam Comma-separated lijst van velden (bijv. "burgerservicenummer,naam,geboorte")
     * @return array Gefilterde data met alleen opgegeven velden
     */
    public function applyFieldSelection(array $data, ?string $fieldsParam): array {
        if (empty($fieldsParam)) {
            return $data;
        }
        
        $fields = array_map('trim', explode(',', $fieldsParam));
        $filtered = [];
        
        foreach ($fields as $field) {
            $value = $this->getNestedValue($data, $field);
            if ($value !== null) {
                $this->setNestedValue($filtered, $field, $value);
            }
        }
        
        // Behoud altijd _links en _embedded als die bestaan
        if (isset($data['_links'])) {
            $filtered['_links'] = $data['_links'];
        }
        if (isset($data['_embedded'])) {
            $filtered['_embedded'] = $data['_embedded'];
        }
        
        return $filtered;
    }
    
    /**
     * Haal geneste waarde op uit array (bijv. "naam.voornamen")
     */
    private function getNestedValue(array $data, string $path) {
        $keys = explode('.', $path);
        $value = $data;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * Zet geneste waarde in array (bijv. "naam.voornamen" => ["Jan", "Piet"])
     */
    private function setNestedValue(array &$data, string $path, $value): void {
        $keys = explode('.', $path);
        $current = &$data;
        
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        
        $current = $value;
    }
}







