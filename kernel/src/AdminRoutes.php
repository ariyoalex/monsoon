<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class AdminRoutes
{
    public static function register(Router $router, array $config): void
    {
        $router->addRoute('GET', '/manage/login', function () {
            return [
                'status' => 200,
                'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                'body' => self::renderLoginPage(),
            ];
        });

        $router->addRoute('POST', '/manage/login', function () use ($config) {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                return [
                    'status' => 400,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'body' => self::renderLoginPage('Email and password are required.'),
                ];
            }

            try {
                $db = Database::getInstance()->getConnection();
                $auth = Auth::getInstance();
                $auth->setDatabase($db);

                $result = $auth->login($email, $password);

                if (!$result['success']) {
                    return [
                        'status' => 401,
                        'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                        'body' => self::renderLoginPage($result['error'] ?? 'Login failed.'),
                    ];
                }

                header('Location: /manage');
                return [
                    'status' => 302,
                    'headers' => ['Location' => '/manage'],
                    'body' => '',
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 500,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'body' => self::renderLoginPage('An internal error occurred.'),
                ];
            }
        });

        $router->addRoute('GET', '/manage/logout', function () {
            Auth::getInstance()->logout();
            header('Location: /manage/login');
            return [
                'status' => 302,
                'headers' => ['Location' => '/manage/login'],
                'body' => '',
            ];
        });

        $router->addRoute('GET', '/manage', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                header('Location: /manage/login');
                return [
                    'status' => 302,
                    'headers' => ['Location' => '/manage/login'],
                    'body' => '',
                ];
            }

            $user = $auth->getCurrentUser();
            return [
                'status' => 200,
                'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                'body' => self::renderDashboard($user),
            ];
        });
    }

    private static function renderLoginPage(string $error = ''): string
    {
        $errorHtml = '';
        if ($error !== '') {
            $errorHtml = '<div class="alert alert-danger mb-4">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; display: flex; align-items: center; min-height: 100vh; }
.login-card { max-width: 420px; width: 100%; }
.card { border-color: #E1E5EC; }
.card-header { background: #1034A6; color: #fff; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
</style>
</head>
<body>
<div class="container">
<div class="row justify-content-center">
<div class="col login-card">
<div class="card shadow-sm">
<div class="card-header">
<h1 class="h4 mb-0">Monsoon CMS</h1>
</div>
<div class="card-body">
{$errorHtml}
<form method="post" action="/manage/login">
<div class="mb-3">
<label for="email" class="form-label">Email</label>
<input type="email" class="form-control" id="email" name="email" required autocomplete="email">
</div>
<div class="mb-3">
<label for="password" class="form-label">Password</label>
<input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
</div>
<button type="submit" class="btn btn-primary w-100">Log in</button>
</form>
</div>
</div>
</div>
</div>
</div>
</body>
</html>
HTML;
    }

    private static function renderDashboard(?array $user): string
    {
        $userEmail = htmlspecialchars($user['email'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content">Content</a>
<a href="/manage/media">Media</a>
<a href="/manage/users">Users</a>
<a href="/manage/settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3">Dashboard</h1>
<span class="text-muted">{$userEmail}</span>
</div>
<div class="row">
<div class="col-md-4 mb-3">
<div class="card shadow-sm">
<div class="card-body">
<h2 class="h6 text-muted">Content</h2>
<p class="display-6 mb-0">0</p>
</div>
</div>
</div>
<div class="col-md-4 mb-3">
<div class="card shadow-sm">
<div class="card-body">
<h2 class="h6 text-muted">Media</h2>
<p class="display-6 mb-0">0</p>
</div>
</div>
</div>
<div class="col-md-4 mb-3">
<div class="card shadow-sm">
<div class="card-body">
<h2 class="h6 text-muted">Users</h2>
<p class="display-6 mb-0">1</p>
</div>
</div>
</div>
</div>
</div>
</div>
</body>
</html>
HTML;
    }
}
