<?php

declare(strict_types=1);

define('SCHOLARLINK_ROOT', dirname(__DIR__));

require SCHOLARLINK_ROOT . '/bootstrap.php';
require SCHOLARLINK_ROOT . '/api/categories/CategoryApi.php';

$appConfig = require SCHOLARLINK_ROOT . '/config/config.php';
$app = $appConfig['app'];

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/api/v1';

if (strpos($path, $basePath) !== 0) {
    jsonResponse(['error' => 'Not found'], 404);
}

$route = substr($path, strlen($basePath));
$routeParts = explode('/', trim($route, '/'));

$resource = $routeParts[0] ?? '';
$action = $routeParts[1] ?? '';
$resourceId = $routeParts[2] ?? '';
$subId = $routeParts[3] ?? '';

$csrf = new Csrf(SessionManager::getInstance());
$csrf->check();

header('Access-Control-Allow-Origin: ' . ($app['environment'] === 'production' ? 'https://scholarlink.app' : '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

switch ($resource) {
    case 'auth':
        $authApi = new AuthApi();
        $authApi->route($method, $action, $resourceId);
        break;

    case 'categories':
        $catApi = new CategoryApi();
        $catApi->route($method, $action, $resourceId);
        break;

    case 'scholarships':
        $scholarApi = new ScholarshipApi();
        $scholarApi->route($method, $action, $resourceId, $subId);
        break;

    case 'applications':
        $appApi = new ApplicationApi();
        $appApi->route($method, $action, $resourceId, $subId);
        break;

    case 'bookmarks':
        $bmApi = new BookmarkApi();
        $bmApi->route($method, $action, $resourceId);
        break;

    case 'documents':
        $docApi = new DocumentApi();
        $docApi->route($method, $action, $resourceId);
        break;

    case 'requirements':
        $reqApi = new RequirementApi();
        $reqApi->route($method, $action, $resourceId);
        break;

    case 'admin':
        $adminApi = new AdminApi();
        $adminApi->route($method, $action, $resourceId);
        break;

    case 'profile':
        $profileApi = new ProfileApi();
        $profileApi->route($method, $action, $resourceId);
        break;

    case 'messages':
        $msgApi = new MessageApi();
        $msgApi->route($method, $action, $resourceId);
        break;

    default:
        jsonResponse(['error' => 'Resource not found'], 404);
}
