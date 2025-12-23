<?php
/**
 * Error Response Service
 * 
 * Genereert Haal Centraal-compliant error responses
 */

namespace OCA\OpenRegister\Service\HaalCentraal;

class ErrorResponseService {
    
    /**
     * Genereer Bad Request (400) error response
     */
    public function badRequest(string $detail, ?string $instance = null, ?array $errors = null): array {
        return $this->createErrorResponse(400, 'Bad Request', $detail, $instance, $errors);
    }
    
    /**
     * Genereer Unauthorized (401) error response
     */
    public function unauthorized(string $detail = 'Authentication required', ?string $instance = null): array {
        return $this->createErrorResponse(401, 'Unauthorized', $detail, $instance);
    }
    
    /**
     * Genereer Forbidden (403) error response
     */
    public function forbidden(string $detail = 'Insufficient permissions', ?string $instance = null): array {
        return $this->createErrorResponse(403, 'Forbidden', $detail, $instance);
    }
    
    /**
     * Genereer Not Found (404) error response
     */
    public function notFound(string $detail, ?string $instance = null): array {
        return $this->createErrorResponse(404, 'Not Found', $detail, $instance);
    }
    
    /**
     * Genereer Unprocessable Entity (422) error response
     */
    public function unprocessableEntity(string $detail, ?string $instance = null, ?array $errors = null): array {
        return $this->createErrorResponse(422, 'Unprocessable Entity', $detail, $instance, $errors);
    }
    
    /**
     * Genereer Too Many Requests (429) error response
     */
    public function tooManyRequests(string $detail = 'Rate limit exceeded', ?string $instance = null): array {
        return $this->createErrorResponse(429, 'Too Many Requests', $detail, $instance);
    }
    
    /**
     * Genereer Internal Server Error (500) error response
     */
    public function internalServerError(string $detail, ?string $instance = null): array {
        return $this->createErrorResponse(500, 'Internal Server Error', $detail, $instance);
    }
    
    /**
     * Genereer gestructureerde error response
     */
    private function createErrorResponse(
        int $status,
        string $title,
        string $detail,
        ?string $instance = null,
        ?array $errors = null
    ): array {
        $response = [
            'status' => $status,
            'title' => $title,
            'detail' => $detail
        ];
        
        if ($instance) {
            $response['instance'] = $instance;
        }
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        // Log error (maar niet in productie met gevoelige data)
        error_log(sprintf(
            "Haal Centraal API Error [%d]: %s - %s%s",
            $status,
            $title,
            $detail,
            $instance ? " (instance: $instance)" : ""
        ));
        
        return $response;
    }
}







