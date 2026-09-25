<?php ob_start(); ?>

<h2>Моя библиотека</h2>

<?php if (empty($games)): ?>
  <p class="muted">Вы пока ничего не купили. <a href="index.php?r=/">Перейти в каталог</a></p>
<?php else: ?>
  <div class="games">
    <?php foreach ($games as $game): ?>
      <div class="card game-card">
        <div class="game-genre"><?= htmlspecialchars($game['genre']) ?></div>
        <h3><?= htmlspecialchars($game['title']) ?></h3>
        <p class="game-desc"><?= htmlspecialchars($game['desc']) ?></p>
        <span class="badge owned">✓ В библиотеке</span>

        <?php if (!empty($keys[$game['id']])): ?>
          <div class="key-box">
            <span class="key-label">Ключ активации:</span>
            <code class="key-value"><?= htmlspecialchars($keys[$game['id']]) ?></code>
            <button class="btn secondary btn-copy-key" data-key="<?= htmlspecialchars($keys[$game['id']]) ?>">Копировать</button>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
