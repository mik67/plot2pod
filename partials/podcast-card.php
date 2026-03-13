<?php // expects $p array in scope ?>
<article class="podcast-card">
    <div class="podcast-info">
        <h3><a href="/podcast.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></a></h3>
        <p class="podcast-desc"><?= htmlspecialchars($p['description']) ?></p>
        <span class="podcast-duration"><?= gmdate('G:i:s', $p['duration']) ?></span>
    </div>
    <audio controls preload="none">
        <source src="<?= htmlspecialchars($p['mp3_path']) ?>" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
</article>
