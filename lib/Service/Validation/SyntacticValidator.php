<?php
/**
 * Syntactic Validator
 * 
 * Syntactische validaties: JSON schema, formaten, verplichte velden
 */

namespace OCA\OpenRegister\Service\Validation;

class SyntacticValidator {
    
    /**
     * Valideer BSN formaat
     */
    public function validateBsn(?string $bsn): ?ValidationError {
        if ($bsn === null || $bsn === '') {
            return new ValidationError('bsn', 'BSN is required');
        }
        
        if (!is_string($bsn)) {
            return new ValidationError('bsn', 'BSN must be a string');
        }
        
        if (!preg_match('/^\d{9}$/', $bsn)) {
            return new ValidationError('bsn', 'BSN must be exactly 9 digits');
        }

        // 11-proef
        if (!$this->passesElfProef($bsn)) {
            return new ValidationError('bsn', 'BSN does not pass 11-proef checksum');
        }
        
        return null;
    }
    
    /**
     * Valideer postcode formaat
     */
    public function validatePostalCode(?string $postcode): ?ValidationError {
        if ($postcode === null || $postcode === '') {
            return new ValidationError('postalCode', 'Postal code is required');
        }
        
        if (!is_string($postcode)) {
            return new ValidationError('postalCode', 'Postal code must be a string');
        }
        
        if (!preg_match('/^\d{4}[A-Z]{2}$/', $postcode)) {
            return new ValidationError(
                'postalCode', 
                'Postal code format is invalid (expected: 1234AB)'
            );
        }
        
        return null;
    }

    /**
     * BSN 11-proef controle
     */
    private function passesElfProef(string $bsn): bool {
        $digits = str_split($bsn);
        if (count($digits) !== 9) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $weight = ($i === 8) ? -1 : (9 - $i);
            $sum += intval($digits[$i]) * $weight;
        }

        return $sum % 11 === 0;
    }
    
    /**
     * Valideer datum formaat (ISO 8601)
     */
    public function validateDate(?string $date, string $fieldName = 'date'): ?ValidationError {
        if ($date === null || $date === '') {
            return new ValidationError($fieldName, 'Date is required');
        }
        
        if (!is_string($date)) {
            return new ValidationError($fieldName, 'Date must be a string');
        }
        
        // Check ISO 8601 formaat (YYYY-MM-DD of YYYY-MM-DDTHH:MM:SSZ)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z?)?$/', $date)) {
            return new ValidationError(
                $fieldName,
                'Date format is invalid (expected: YYYY-MM-DD)'
            );
        }
        
        // Check of datum geldig is
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return new ValidationError($fieldName, 'Date is not a valid date');
        }
        
        return null;
    }
    
    /**
     * Valideer verplichte velden
     */
    public function validateRequiredFields(array $data, array $requiredFields): array {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            $value = $this->getNestedValue($data, $field);
            
            if ($value === null || $value === '') {
                $errors[] = new ValidationError(
                    $field,
                    ucfirst(str_replace('.', ' ', $field)) . ' is required'
                );
            }
        }
        
        return $errors;
    }
    
    /**
     * Haal geneste waarde op uit array (bijv. "declarant.bsn")
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
     * Valideer JSON structuur
     */
    public function validateJson($data): ?ValidationError {
        if (!is_array($data)) {
            return new ValidationError('request', 'Request body must be a JSON object');
        }
        
        return null;
    }
}






