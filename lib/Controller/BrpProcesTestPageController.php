<?php
/**
 * BRP Proces Test Page Controller
 * 
 * Test pagina voor volledig BRP proces simulatie
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;

class BrpProcesTestPageController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /brp-proces-test
     * Test pagina voor BRP proces simulatie
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function page(): TemplateResponse {
        try {
            $response = new TemplateResponse(
                $this->appName,
                'brp-proces-test',
                []
            );

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedConnectDomain('*');
            $csp->addAllowedScriptDomain('*');
            // Allow inline scripts with nonce (Nextcloud handles this automatically)
            $response->setContentSecurityPolicy($csp);
            
            // Add cache control headers to prevent caching issues
            $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->addHeader('Pragma', 'no-cache');
            $response->addHeader('Expires', '0');

            return $response;
        } catch (\Exception $e) {
            return new TemplateResponse(
                $this->appName,
                'error',
                ['error' => $e->getMessage()],
                '500'
            );
        }
    }
}

