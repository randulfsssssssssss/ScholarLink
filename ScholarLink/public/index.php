<?php

declare(strict_types=1);

define('SCHOLARLINK_ROOT', dirname(__DIR__));
require SCHOLARLINK_ROOT . '/bootstrap.php';

ob_start();

$appConfig = require SCHOLARLINK_ROOT . '/config/config.php';
$session = SessionManager::getInstance();
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = dirname($_SERVER['SCRIPT_NAME']);

$authUser = $session->isLoggedIn() ? User::find((int)$session->get('user_id')) : null;

if ($authUser) {
    unset($authUser['password_hash']);
}

function renderPage(string $title, string $bodyContent, ?array $user = null, ?array $config = null): void
{
    $role = $user ? $user['role'] : 'guest';
    $pageTitle = $title;
    $csrfToken = (new Csrf(SessionManager::getInstance()))->getToken();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
        <title><?= htmlspecialchars($pageTitle) ?> - ScholarLink</title>
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body class="role-<?= htmlspecialchars($role) ?>">
        <?php require SCHOLARLINK_ROOT . '/includes/header.php'; ?>
        <main class="main-content">
            <?= $bodyContent ?>
        </main>
        <?php require SCHOLARLINK_ROOT . '/includes/footer.php'; ?>
        <script src="/assets/js/vendor.js" defer></script>
        <script src="/assets/js/main.js" defer></script>
        <script>
            window.APP_CONFIG = {
                baseUrl: '/api/v1',
                csrfToken: '<?= htmlspecialchars($csrfToken) ?>',
                userRole: '<?= htmlspecialchars($role) ?>',
                userId: <?= $user['id'] ?? 'null' ?>,
                currentPath: '<?= htmlspecialchars(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) ?>'
            };
        </script>
    </body>
    </html>
    <?php
}

if ($authUser === null) {
    $route = trim(explode('/', ltrim($requestPath, '/'))[0] ?? '', '/');
    if (!in_array($route, ['login', 'register', 'forgot', 'reset'], true)) {
        $route = 'login';
    }

    switch ($route) {
        case 'register':
            require SCHOLARLINK_ROOT . '/dashboard/auth/register.php';
            break;
        case 'forgot':
            require SCHOLARLINK_ROOT . '/dashboard/auth/forgot-password.php';
            break;
        case 'reset':
            require SCHOLARLINK_ROOT . '/dashboard/auth/reset-password.php';
            break;
        case 'login':
        default:
            require SCHOLARLINK_ROOT . '/dashboard/auth/login.php';
            break;
    }
} else {
    $route = trim(explode('/', ltrim($requestPath, '/'))[0] ?? 'dashboard', '/');

    switch ($route) {
        case 'dashboard':
            switch ($authUser['role']) {
                case 'student':
                    require SCHOLARLINK_ROOT . '/dashboard/student/index.php';
                    break;
                case 'organization':
                    require SCHOLARLINK_ROOT . '/dashboard/organization/index.php';
                    break;
                case 'admin':
                    require SCHOLARLINK_ROOT . '/dashboard/admin/index.php';
                    break;
            }
            break;

        case 'scholarships':
            if (isset($requestPath) && strpos($requestPath, '/scholarships/') !== false) {
                require SCHOLARLINK_ROOT . '/dashboard/student/scholarship-detail.php';
            } else {
                require SCHOLARLINK_ROOT . '/dashboard/student/scholarships.php';
            }
            break;

        case 'applications':
            require SCHOLARLINK_ROOT . '/dashboard/student/applications.php';
            break;

        case 'bookmarks':
            require SCHOLARLINK_ROOT . '/dashboard/student/bookmarks.php';
            break;

        case 'messages':
            require SCHOLARLINK_ROOT . '/dashboard/messages/index.php';
            break;

        case 'profile':
            require SCHOLARLINK_ROOT . '/dashboard/profile/index.php';
            break;

        case 'logout':
            $session->destroy();
            redirect('/login');
            break;

        case 'admin':
            if ($authUser['role'] !== 'admin') {
                redirect('/dashboard');
            }
            require SCHOLARLINK_ROOT . '/dashboard/admin/index.php';
            break;

        default:
            redirect('/dashboard');
            break;
    }
}
