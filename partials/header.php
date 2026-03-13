<?php require_once __DIR__ . '/../auth.php'; ?>
<header class="site-header">
    <a href="/" class="logo">plot2pod</a>
    <nav>
        <?php if (isLoggedIn()): ?>
            <a href="/dashboard.php">My requests</a>
            <a href="/request.php" class="btn-primary">Submit topic</a>
            <a href="/logout.php">Log out</a>
        <?php else: ?>
            <a href="/login.php">Log in</a>
            <a href="/register.php" class="btn-primary">Register</a>
        <?php endif; ?>
    </nav>
</header>
