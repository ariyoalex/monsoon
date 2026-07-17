<?php

declare(strict_types=1);

namespace Monsoon\Modules\SecurityHardening;

use Monsoon\Kernel\Router;
use Monsoon\Kernel\Response;

final class SecurityModule
{
    private \mysqli $db;
    private SecurityService $securityService;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->securityService = new SecurityService($db);
    }

    public function registerRoutes(Router $router): void
    {
        // Audit log API
        $router->addRoute('GET', '/api/v1/audit-log', function () {
            $limit = (int)($_GET['limit'] ?? 100);
            $offset = (int)($_GET['offset'] ?? 0);
            $action = $_GET['action'] ?? null;
            $entityType = $_GET['entity_type'] ?? null;
            $logs = $this->securityService->getAuditLog($limit, $offset, $action, $entityType);
            return Response::json(['data' => $logs]);
        });

        // 2FA API
        $router->addRoute('POST', '/api/v1/2fa/setup', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $userId = $data['user_id'] ?? '';
            $result = $this->securityService->generate2faSecret($userId);
            return Response::json(['data' => $result]);
        });

        $router->addRoute('POST', '/api/v1/2fa/verify', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $userId = $data['user_id'] ?? '';
            $code = $data['code'] ?? '';
            $valid = $this->securityService->verify2faCode($userId, $code);
            if ($valid) {
                $this->securityService->enable2fa($userId);
                $this->securityService->log('2fa.enabled', $userId, 'user', $userId);
            }
            return Response::json(['data' => ['valid' => $valid]]);
        });

        $router->addRoute('POST', '/api/v1/2fa/disable', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $userId = $data['user_id'] ?? '';
            $this->securityService->disable2fa($userId);
            $this->securityService->log('2fa.disabled', $userId, 'user', $userId);
            return Response::json(['data' => ['disabled' => true]]);
        });

        // File integrity
        $router->addRoute('POST', '/api/v1/integrity/scan', function () {
            $root = dirname(__DIR__, 2);
            $files = $this->securityService->scanFiles($root);
            $this->securityService->storeChecksums($files);
            return Response::json(['data' => ['scanned' => count($files), 'message' => 'Baseline stored.']]);
        });

        $router->addRoute('GET', '/api/v1/integrity/check', function () {
            $root = dirname(__DIR__, 2);
            $result = $this->securityService->checkIntegrity($root);
            return Response::json(['data' => $result]);
        });
    }

    public function getSecurityService(): SecurityService
    {
        return $this->securityService;
    }
}
