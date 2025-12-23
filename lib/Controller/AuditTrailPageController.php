<?php
/**
 * Audit Trail Page Controller
 * 
 * Render de audit trail pagina voor Nextcloud Open Register
 */

namespace OCA\OpenRegister\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;

class AuditTrailPageController extends Controller {
    
    public function __construct(
        $appName,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
    }
    
    /**
     * GET /audit-trails
     * Render de audit trail pagina
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function page(): TemplateResponse {
        try {
            $response = new TemplateResponse(
                $this->appName,
                'audit-trails',
                []
            );

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedConnectDomain('*');
            $csp->addAllowedScriptDomain('*');
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





