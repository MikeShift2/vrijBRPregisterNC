<?php
/**
 * Validation Result Object
 * 
 * Bevat het resultaat van een validatie: isValid, errors, transformedData
 */

namespace OCA\OpenRegister\Service\Validation;

class ValidationResult {
    private bool $isValid;
    private array $errors;
    private ?array $transformedData;
    
    public function __construct(
        bool $isValid,
        array $errors = [],
        ?array $transformedData = null
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->transformedData = $transformedData;
    }
    
    public function isValid(): bool {
        return $this->isValid;
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
    
    public function getTransformedData(): ?array {
        return $this->transformedData;
    }
    
    public function addError(ValidationError $error): void {
        $this->errors[] = $error;
        $this->isValid = false;
    }
    
    public function setTransformedData(array $data): void {
        $this->transformedData = $data;
    }
    
    public function toErrorArray(): array {
        return array_map(function($error) {
            return $error instanceof ValidationError 
                ? $error->toArray() 
                : $error;
        }, $this->errors);
    }
}







