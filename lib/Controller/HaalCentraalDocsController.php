<?php
/**
 * Haal Centraal API Documentation Controller
 * 
 * Endpoints voor OpenAPI specificatie en Swagger UI
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCA\OpenRegister\Service\HaalCentraal\OpenApiSpecService;

class HaalCentraalDocsController extends Controller {
    
    private OpenApiSpecService $openApiService;
    
    public function __construct(
        $appName,
        IRequest $request,
        OpenApiSpecService $openApiService
    ) {
        parent::__construct($appName, $request);
        $this->openApiService = $openApiService;
    }
    
    /**
     * GET /api/docs/openapi.json
     * OpenAPI 3.0 specificatie
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getOpenApiSpec(): JSONResponse {
        $spec = $this->openApiService->generateSpec();
        return new JSONResponse($spec);
    }
    
    /**
     * GET /api/docs
     * Swagger UI pagina
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getDocs(): TemplateResponse {
        $specUrl = '/apps/openregister/api/docs/openapi.json';
        
        return new TemplateResponse('openregister', 'swagger-ui', [
            'specUrl' => $specUrl
        ]);
    }
}







