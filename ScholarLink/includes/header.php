<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarLink - Scholarship Management Platform</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="/">ScholarLink</a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <?php if (SessionManager::getInstance()->isLoggedIn()): ?>
                            <?php $userRole = SessionManager::getInstance()->getUserRole(); ?>
                            <?php if ($userRole === 'student'): ?>
                                <li><a href="/dashboard">Dashboard</a></li>
                                <li><a href="/scholarships">Browse Scholarships</a></li>
                                <li><a href="/bookmarks">Bookmarks</a></li>
                                <li><a href="/applications">My Applications</a></li>
                            <?php elseif ($userRole === 'organization'): ?>
                                <li><a href="/dashboard">Dashboard</a></li>
                                <li><a href="/scholarships">My Scholarships</a></li>
                                <li><a href="/applications">Applications</a></li>
                            <?php elseif ($userRole === 'admin'): ?>
                                <li><a href="/dashboard">Dashboard</a></li>
                                <li><a href="/admin/users">Users</a></li>
                                <li><a href="/admin/scholarships">Scholarships</a></li>
                            <?php endif; ?>
                            <li><a href="/messages">Messages</a></li>
                            <li><a href="/profile">Profile</a></li>
                            <li><a href="/logout">Logout</a></li>
                        <?php else: ?>
                            <li><a href="/login">Sign In</a></li>
                            <li><a href="/register">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
