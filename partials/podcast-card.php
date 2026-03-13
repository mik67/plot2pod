<?php // expects $p array in scope ?>
<a href="/podcast.php?id=<?= $p['id'] ?>" class="podcast-card">
    <div class="podcast-card-cover">🎙</div>
    <h3><?= htmlspecialchars($p['title']) ?></h3>
    <?php if ($p['description']): ?>
        <p><?= htmlspecialchars(mb_substr($p['description'], 0, 100)) ?><?= mb_strlen($p['description']) > 100 ? '…' : '' ?></p>
    <?php endif; ?>
    <div class="podcast-card-footer">
        <span class="podcast-card-play">▶ Listen</span>
        <?php if ($p['duration']): ?>
            <span class="podcast-card-duration"><?= gmdate('G:i:s', $p['duration']) ?></span>
        <?php endif; ?>
    </div>
</a>
