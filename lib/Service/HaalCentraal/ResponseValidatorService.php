<?php
/**
 * Response Validator Service
 * 
 * Valideert API responses tegen Haal Centraal OpenAPI specificatie
 */

namespace OCA\OpenRegister\Service\HaalCentraal;

use OCA\OpenRegister\Service\Validation\ValidationResult;
use OCA\OpenRegister\Service\Validation\ValidationError;

class ResponseValidatorService {
    private array $openApiSpec;
    
    public function __construct(OpenApiSpecService $openApiService) {
        $this->openApiSpec = $openApiService->generateSpec();
    }
    
    /**
     * Valideer response tegen OpenAPI specificatie
     */
    public function validateResponse(array $data, string $endpoint, string $method = 'GET'): ValidationResult {
        $errors = [];
        
        // Haal schema op uit OpenAPI spec
        $schema = $this->getResponseSchema($endpoint, $method);
        
        if (!$schema) {
            return new ValidationResult(true); // Geen schema = geen validatie nodig
        }
        
        // Valideer vereiste velden
        $requiredFields = $schema['required'] ?? [];
        foreach ($requiredFields as $field) {
            if (!$this->hasNestedValue($data, $field)) {
                $errors[] = new ValidationError(
                    $field,
                    "Required field '{$field}' is missing",
                    'REQUIRED_FIELD_MISSING'
                );
            }
        }
        
        // Valideer datatypes en formats
        $properties = $schema['properties'] ?? [];
        foreach ($properties as $field => $property) {
            if ($this->hasNestedValue($data, $field)) {
                $value = $this->getNestedValue($data, $field);
                $typeError = $this->validateProperty($value, $property, $field);
                if ($typeError) {
                    $errors[] = $typeError;
                }
            }
        }
        
        return new ValidationResult(empty($errors), $errors);
    }
    
    /**
     * Haal response schema op uit OpenAPI spec
     */
    private function getResponseSchema(string $endpoint, string $method): ?array {
        $path = $this->openApiSpec['paths'][$endpoint] ?? null;
        if (!$path) {
            return null;
        }
        
        $operation = $path[strtolower($method)] ?? null;
        if (!$operation) {
            return null;
        }
        
        $response = $operation['responses']['200'] ?? null;
        if (!$response) {
            return null;
        }
        
        $content = $response['content']['application/json'] ?? null;
        if (!$content) {
            return null;
        }
        
        $schemaRef = $content['schema']['$ref'] ?? null;
        if ($schemaRef) {
            return $this->resolveSchemaRef($schemaRef);
        }
        
        return $content['schema'] ?? null;
    }
    
    /**
     * Resolve schema reference (bijv. #/components/schemas/IngeschrevenPersoon)
     */
    private function resolveSchemaRef(string $ref): ?array {
        if (strpos($ref, '#/components/schemas/') === 0) {
            $schemaName = substr($ref, strlen('#/components/schemas/'));
            return $this->openApiSpec['components']['schemas'][$schemaName] ?? null;
        }
        
        return null;
    }
    
    /**
     * Valideer property waarde tegen schema
     */
    private function validateProperty($value, array $property, string $fieldPath): ?ValidationError {
        // Check type
        $expectedType = $property['type'] ?? null;
        
        if ($expectedType === 'string' && !is_string($value)) {
            return new ValidationError(
                $fieldPath,
                "Expected string, got " . gettype($value),
                'INVALID_TYPE'
            );
        }
        
        if ($expectedType === 'integer' && !is_int($value)) {
            return new ValidationError(
                $fieldPath,
                "Expected integer, got " . gettype($value),
                'INVALID_TYPE'
            );
        }
        
        if ($expectedType === 'array' && !is_array($value)) {
            return new ValidationError(
                $fieldPath,
                "Expected array, got " . gettype($value),
                'INVALID_TYPE'
            );
        }
        
        if ($expectedType === 'object' && !is_array($value)) {
            return new ValidationError(
                $fieldPath,
                "Expected object, got " . gettype($value),
                'INVALID_TYPE'
            );
        }
        
        // Check format (bijv. ISO 8601 voor datums)
        if (isset($property['format'])) {
            if ($property['format'] === 'date' && !$this->isValidDate($value)) {
                return new ValidationError(
                    $fieldPath,
                    'Invalid date format (expected ISO 8601: YYYY-MM-DD)',
                    'INVALID_FORMAT'
                );
            }
        }
        
        // Check pattern (bijv. BSN pattern)
        if (isset($property['pattern'])) {
            if (!preg_match('/' . $property['pattern'] . '/', $value)) {
                return new ValidationError(
                    $fieldPath,
                    "Value does not match pattern: {$property['pattern']}",
                    'PATTERN_MISMATCH'
                );
            }
        }
        
        // Check enum
        if (isset($property['enum'])) {
            if (!in_array($value, $property['enum'])) {
                return new ValidationError(
                    $fieldPath,
                    "Value must be one of: " . implode(', ', $property['enum']),
                    'INVALID_ENUM_VALUE'
                );
            }
        }
        
        // Recursieve validatie voor geneste objecten
        if ($expectedType === 'object' && isset($property['properties'])) {
            foreach ($property['properties'] as $subField => $subProperty) {
                if (isset($value[$subField])) {
                    $subError = $this->validateProperty($value[$subField], $subProperty, $fieldPath . '.' . $subField);
                    if ($subError) {
                        return $subError;
                    }
                }
            }
        }
        
        // Recursieve validatie voor arrays
        if ($expectedType === 'array' && isset($property['items'])) {
            if (is_array($value)) {
                foreach ($value as $index => $item) {
                    $itemError = $this->validateProperty($item, $property['items'], $fieldPath . "[$index]");
                    if ($itemError) {
                        return $itemError;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check of waarde geldige datum is (ISO 8601)
     */
    private function isValidDate($value): bool {
        if (!is_string($value)) {
            return false;
        }
        
        // Check ISO 8601 formaat (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        
        // Check of datum geldig is
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }
    
    /**
     * Check of geneste waarde bestaat
     */
    private function hasNestedValue(array $data, string $path): bool {
        return $this->getNestedValue($data, $path) !== null;
    }
    
    /**
     * Haal geneste waarde op
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
}

