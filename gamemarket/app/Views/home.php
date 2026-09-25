<?php ob_start(); ?>

<div class="toolbar">
  <h2>Каталог игр</h2>
  <p class="muted">Выберите игру и добавьте её в корзину. Нажмите на карточку, чтобы посмотреть описание и скриншоты</p>
</div>

<?php
$genres = array_values(array_unique(array_column($games, 'genre')));
sort($genres, SORT_STRING | SORT_FLAG_CASE);
?>
<div class="category-filter" id="category-filter">
  <button class="chip active" data-genre="all">Все</button>
  <?php foreach ($genres as $genre): ?>
    <button class="chip" data-genre="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></button>
  <?php endforeach; ?>
</div>

<div id="games" class="games">
  <?php foreach ($games as $i => $game): ?>
    <?php
      $inLibrary  = in_array($game['id'], $library, true);
      $inCart     = in_array($game['id'], $cart, true);
      $outOfStock = $game['stock'] === 0;
      $cover      = \App\Models\Game::coverImage($game);
      $shots      = \App\Models\Game::screenshotUrls($game);
    ?>
    <div class="card game-card fade-in-up"
         style="animation-delay: <?= min($i * 0.05, 0.6) ?>s"
         data-genre="<?= htmlspecialchars($game['genre']) ?>"
         data-id="<?= htmlspecialchars($game['id']) ?>"
         data-title="<?= htmlspecialchars($game['title']) ?>"
         data-desc="<?= htmlspecialchars($game['desc']) ?>"
         data-price="<?= (int)$game['price'] ?>"
         data-shots="<?= htmlspecialchars(implode('|', $shots)) ?>"
         data-status="<?= !$user ? 'guest' : ($inLibrary ? 'owned' : ($inCart ? 'incart' : ($outOfStock ? 'outofstock' : 'buyable'))) ?>">

      <div class="card-cover">
        <div class="cover-skeleton"></div>
        <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($game['title']) ?>" loading="lazy"
             class="cover-img" onload="this.classList.add('loaded'); this.previousElementSibling.remove()">
      </div>

      <div class="game-genre"><?= htmlspecialchars($game['genre']) ?></div>
      <h3><?= htmlspecialchars($game['title']) ?></h3>
      <p class="game-desc"><?= htmlspecialchars($game['desc']) ?></p>

      <?php if ($game['stock'] >= 0): ?>
        <p class="stock-line <?= $outOfStock ? 'stock-empty' : '' ?>">
          <?= $outOfStock ? 'Ключи закончились' : 'Осталось ключей: ' . (int)$game['stock'] ?>
        </p>
      <?php endif; ?>

      <div class="game-footer">
        <span class="price"><?= $game['price'] > 0 ? number_format($game['price'], 0, ',', ' ') . ' ₽' : 'Бесплатно' ?></span>

        <?php if (!$user): ?>
          <a class="btn" href="index.php?r=/login">Войти, чтобы купить</a>
        <?php elseif ($inLibrary): ?>
          <button class="btn secondary" disabled>✓ Куплено</button>
        <?php elseif ($inCart): ?>
          <button class="btn secondary" disabled>В корзине</button>
        <?php elseif ($outOfStock): ?>
          <button class="btn secondary" disabled>Нет в наличии</button>
        <?php else: ?>
          <button class="btn btn-add" data-game="<?= htmlspecialchars($game['id']) ?>">Добавить в корзину</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<p id="no-games-msg" class="muted" style="display:none">В этой категории пока нет игр.</p>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
