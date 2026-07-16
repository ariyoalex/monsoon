<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class PermissionMiddleware implements MiddlewareInterface
{
    private string $capability;

    public function __construct(string $capability)
    {
        $this->capability = $capability;
    }

    public function handle(Request $request, callable $next): Response
    {
        $auth = Auth::getInstance();
        $user = $auth->getCurrentUser();

        if ($user === null) {
            return Response::error(401, 'Authentication required.');
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT capabilities FROM roles WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $user['role_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $role = $result->fetch_assoc();
        $stmt->close();

        if ($role === null) {
            return Response::error(403, 'Role not found.');
        }

        $capabilities = json_decode($role['capabilities'], true) ?? [];

        if (!in_array($this->capability, $capabilities, true)) {
            if ($request->wantsJson()) {
                return Response::error(403, "Missing capability: {$this->capability}");
            }
            return Response::error(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
