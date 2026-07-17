<?php

declare(strict_types=1);

namespace Monsoon\Modules\BackupRestore;

use Monsoon\Kernel\Router;
use Monsoon\Kernel\Response;

final class BackupModule
{
    private \mysqli $db;
    private BackupService $backupService;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->backupService = new BackupService($db);
    }

    public function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/api/v1/backups', function () {
            $backups = $this->backupService->getAll();
            return Response::json(['data' => $backups]);
        });

        $router->addRoute('POST', '/api/v1/backups', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $name = $data['name'] ?? 'Backup ' . date('Y-m-d H:i');
            $type = $data['type'] ?? 'full';
            $notes = $data['notes'] ?? '';
            try {
                $backup = $this->backupService->createBackup($name, $type, $notes);
                return Response::json(['data' => $backup], 201);
            } catch (\Throwable $e) {
                return Response::error(500, $e->getMessage());
            }
        });

        $router->addRoute('GET', '/api/v1/backups/{id}', function (array $params) {
            $backup = $this->backupService->findById($params['id']);
            if (!$backup) {
                return Response::error(404, 'Backup not found.');
            }
            return Response::json(['data' => $backup]);
        });

        $router->addRoute('DELETE', '/api/v1/backups/{id}', function (array $params) {
            $deleted = $this->backupService->deleteBackup($params['id']);
            if (!$deleted) {
                return Response::error(404, 'Backup not found.');
            }
            return Response::json(['data' => ['deleted' => true]]);
        });

        $router->addRoute('POST', '/api/v1/backups/{id}/restore', function (array $params) {
            try {
                $result = $this->backupService->restoreFromBackup($params['id']);
                return Response::json(['data' => $result]);
            } catch (\Throwable $e) {
                return Response::error(500, $e->getMessage());
            }
        });

        $router->addRoute('GET', '/api/v1/backups/stats', function () {
            $stats = $this->backupService->getBackupStats();
            return Response::json(['data' => $stats]);
        });
    }

    public function getBackupService(): BackupService
    {
        return $this->backupService;
    }
}
