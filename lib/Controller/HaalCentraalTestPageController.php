<?php
/**
 * Haal Centraal Test Page Controller
 * 
 * Testpagina voor het zoeken op personen via de Haal Centraal BRP Bevragen API
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;

class HaalCentraalTestPageController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /haal-centraal-test
     * Testpagina voor Haal Centraal API
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function page(): TemplateResponse {
        try {
            $response = new TemplateResponse(
                $this->appName,
                'haalcentraaltest',
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

