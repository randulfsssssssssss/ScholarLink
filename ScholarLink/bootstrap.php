<?php

declare(strict_types=1);

require_once SCHOLARLINK_ROOT . '/config/config.php';
require_once SCHOLARLINK_ROOT . '/config/database.php';
require_once SCHOLARLINK_ROOT . '/config/session.php';
require_once SCHOLARLINK_ROOT . '/config/csrf.php';
require_once SCHOLARLINK_ROOT . '/includes/helpers.php';
require_once SCHOLARLINK_ROOT . '/includes/models/User.php';
require_once SCHOLARLINK_ROOT . '/includes/models/Scholarship.php';
require_once SCHOLARLINK_ROOT . '/includes/models/Application.php';
require_once SCHOLARLINK_ROOT . '/includes/models/Bookmark.php';
require_once SCHOLARLINK_ROOT . '/includes/models/Document.php';
require_once SCHOLARLINK_ROOT . '/includes/models/Requirement.php';
require_once SCHOLARLINK_ROOT . '/includes/models/AuditLog.php';
require_once SCHOLARLINK_ROOT . '/includes/models/Message.php';
require_once SCHOLARLINK_ROOT . '/includes/middleware/AuthMiddleware.php';
require_once SCHOLARLINK_ROOT . '/includes/middleware/RoleMiddleware.php';

$config = require SCHOLARLINK_ROOT . '/config/config.php';

Database::getInstance($config['database']);
SessionManager::getInstance($config['session']);
$csrf = new Csrf(SessionManager::getInstance());

spl_autoload_register(function ($class) {
    $paths = [
        SCHOLARLINK_ROOT . '/includes/models/' . $class . '.php',
        SCHOLARLINK_ROOT . '/includes/middleware/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});
