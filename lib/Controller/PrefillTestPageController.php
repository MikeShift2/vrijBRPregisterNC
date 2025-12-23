<?php
/**
 * Prefill Test Page Controller
 * 
 * Testpagina specifiek voor het testen van prefill functionaliteit
 * met vrijBRP register
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;

class PrefillTestPageController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /prefill-test
     * Testpagina voor prefill functionaliteit
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function page(): TemplateResponse {
        try {
            $response = new TemplateResponse(
                $this->appName,
                'prefilltest',
                []
            );

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedConnectDomain('*');
            $csp->addAllowedScriptDomain('*');
            // Inline scripts zijn al toegestaan via nonce in Nextcloud templates
            $response->setContentSecurityPolicy($csp);

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







