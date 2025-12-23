<?php
/**
 * Validation Error Object
 * 
 * Representeert een validatie fout met veld, message, code en obstructions
 */

namespace OCA\OpenRegister\Service\Validation;

class ValidationError {
    private string $field;
    private string $message;
    private ?string $code;
    private ?array $obstructions;
    
    public function __construct(
        string $field,
        string $message,
        ?string $code = null,
        ?array $obstructions = null
    ) {
        $this->field = $field;
        $this->message = $message;
        $this->code = $code;
        $this->obstructions = $obstructions;
    }
    
    public function getField(): string {
        return $this->field;
    }
    
    public function getMessage(): string {
        return $this->message;
    }
    
    public function getCode(): ?string {
        return $this->code;
    }
    
    public function getObstructions(): ?array {
        return $this->obstructions;
    }
    
    public function toArray(): array {
        $result = [
            'field' => $this->field,
            'message' => $this->message
        ];
        
        if ($this->code !== null) {
            $result['code'] = $this->code;
        }
        
        if ($this->obstructions !== null && !empty($this->obstructions)) {
            $result['obstructions'] = $this->obstructions;
        }
        
        return $result;
    }
}







