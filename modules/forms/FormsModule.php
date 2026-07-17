<?php

declare(strict_types=1);

namespace Monsoon\Modules\Forms;

use Monsoon\Kernel\Router;
use Monsoon\Kernel\Response;

final class FormsModule
{
    private \mysqli $db;
    private FormService $formService;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->formService = new FormService($db);
    }

    public function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/api/v1/forms', function () {
            $forms = $this->formService->getAll();
            return Response::json(['data' => $forms]);
        });

        $router->addRoute('POST', '/api/v1/forms', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $form = $this->formService->create($data);
            return Response::json(['data' => $form], 201);
        });

        $router->addRoute('GET', '/api/v1/forms/{id}', function (array $params) {
            $form = $this->formService->findById($params['id']);
            if (!$form) {
                return Response::error(404, 'Form not found.');
            }
            return Response::json(['data' => $form]);
        });

        $router->addRoute('PUT', '/api/v1/forms/{id}', function (array $params) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $form = $this->formService->update($params['id'], $data);
            if (!$form) {
                return Response::error(404, 'Form not found.');
            }
            return Response::json(['data' => $form]);
        });

        $router->addRoute('DELETE', '/api/v1/forms/{id}', function (array $params) {
            $deleted = $this->formService->delete($params['id']);
            if (!$deleted) {
                return Response::error(404, 'Form not found.');
            }
            return Response::json(['data' => ['deleted' => true]]);
        });

        $router->addRoute('POST', '/api/v1/forms/{id}/submit', function (array $params) {
            $data = $_POST;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            try {
                $result = $this->formService->submit($params['id'], $data, $ip, $ua);
                return Response::json(['data' => $result]);
            } catch (\Throwable $e) {
                return Response::error(400, $e->getMessage());
            }
        });

        $router->addRoute('GET', '/api/v1/forms/{id}/submissions', function (array $params) {
            $subs = $this->formService->getSubmissions($params['id']);
            $count = $this->formService->getSubmissionCount($params['id']);
            return Response::json(['data' => $subs, 'total' => $count]);
        });

        $router->addRoute('DELETE', '/api/v1/forms/submissions/{id}', function (array $params) {
            $deleted = $this->formService->deleteSubmission($params['id']);
            if (!$deleted) {
                return Response::error(404, 'Submission not found.');
            }
            return Response::json(['data' => ['deleted' => true]]);
        });

        $router->addRoute('GET', '/api/v1/forms/{id}/export', function (array $params) {
            $csv = $this->formService->exportCsv($params['id']);
            return new Response(200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="submissions.csv"',
            ], $csv);
        });
    }

    public function getFormService(): FormService
    {
        return $this->formService;
    }
}
