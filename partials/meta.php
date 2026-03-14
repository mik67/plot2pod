<?php
/**
 * SEO meta partial. Set these variables before including:
 *   $metaTitle       — page title (without site name)
 *   $metaDesc        — meta description (max 160 chars)
 *   $metaCanonical   — canonical URL (full https://...)
 *   $metaNoindex     — true to block indexing (login, dashboard, admin)
 *   $metaOgImage     — optional OG image URL
 */
$siteUrl   = rtrim(SITE_URL, '/');
$siteName  = 'plot2pod';
$title     = isset($metaTitle) ? htmlspecialchars($metaTitle) . ' – ' . $siteName : $siteName . ' – AI-generated podcasts on any topic';
$desc      = htmlspecialchars(isset($metaDesc) ? mb_substr($metaDesc, 0, 160) : 'Turn any topic into a debate-format podcast. Submit a topic, upload your materials, or share your sources.');
$canonical = isset($metaCanonical) ? htmlspecialchars($metaCanonical) : htmlspecialchars($siteUrl . strtok($_SERVER['REQUEST_URI'], '?'));
$ogImage   = isset($metaOgImage) ? htmlspecialchars($metaOgImage) : htmlspecialchars($siteUrl . '/img/og-default.png');
?>
    <meta name="description" content="<?= $desc ?>">
    <?php if (!empty($metaNoindex)): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <meta name="robots" content="index, follow">
    <?php endif; ?>
    <link rel="canonical" href="<?= $canonical ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:site_name"   content="<?= $siteName ?>">
    <meta property="og:title"       content="<?= $title ?>">
    <meta property="og:description" content="<?= $desc ?>">
    <meta property="og:url"         content="<?= $canonical ?>">
    <meta property="og:image"       content="<?= $ogImage ?>">
    <meta property="og:locale" content="en_US">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $title ?>">
    <meta name="twitter:description" content="<?= $desc ?>">
    <meta name="twitter:image"       content="<?= $ogImage ?>">

    <link rel="alternate" type="application/rss+xml" title="plot2pod" href="<?= $siteUrl ?>/feed.php">
