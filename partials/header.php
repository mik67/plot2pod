<?php require_once __DIR__ . '/../auth.php'; ?>
<header class="site-header">
    <div class="header-inner">
        <a href="/" class="site-logo">plot2<span>pod</span></a>
        <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav class="site-nav" id="site-nav">
            <a href="/">Home</a>
            <?php if (isLoggedIn()): ?>
                <a href="/dashboard.php">My requests</a>
                <?php if (isAdmin()): ?><a href="/admin.php">Admin</a><?php endif; ?>
                <a href="/logout.php">Log out</a>
                <a href="/request.php" class="nav-cta">Submit topic</a>
            <?php else: ?>
                <a href="/login.php">Log in</a>
                <a href="/register.php" class="nav-cta">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
document.getElementById('nav-toggle').addEventListener('click', function() {
    document.getElementById('site-nav').classList.toggle('open');
});
</script>
