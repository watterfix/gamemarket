<?php ob_start(); ?>

<?php
$presetAvatars = ['🎮','👾','🕹️','🦸','🐉','🤖','👻','🚀','🧙','🥷','🐱','🦊'];
?>

<div class="toolbar">
  <h2>Профиль</h2>
  <p class="muted">Аватар, история покупок, библиотека и безопасность аккаунта</p>
</div>

<div class="tabs" id="profile-tabs">
  <button class="tab-btn active" data-tab="pt-overview">Профиль</button>
  <button class="tab-btn" data-tab="pt-purchases">История покупок</button>
  <button class="tab-btn" data-tab="pt-library">Библиотека</button>
  <button class="tab-btn" data-tab="pt-security">Безопасность</button>
</div>

<!-- ---------- Вкладка: профиль / аватар ---------- -->
<div class="tab-panel active" id="pt-overview">
  <div class="card profile-card">
    <div class="profile-head">
      <div class="profile-avatar-big" id="profile-avatar-preview">
        <?php if ($avatar && str_starts_with($avatar, 'data:image')): ?>
          <img src="<?= htmlspecialchars($avatar) ?>" alt="">
        <?php elseif ($avatar): ?>
          <span><?= htmlspecialchars($avatar) ?></span>
        <?php else: ?>
          <span><?= htmlspecialchars(mb_strtoupper(mb_substr($user, 0, 1))) ?></span>
        <?php endif; ?>
      </div>
      <div>
        <h3><?= htmlspecialchars($user) ?></h3>
        <p class="muted"><?= $role === 'admin' ? 'Администратор' : 'Пользователь' ?> · Баланс: <?= number_format($balance, 0, ',', ' ') ?> ₽</p>
      </div>
    </div>

    <h4 class="profile-section-title">Выбрать аватар</h4>
    <div class="avatar-grid" id="avatar-grid">
      <?php foreach ($presetAvatars as $a): ?>
        <button type="button" class="avatar-option" data-avatar="<?= htmlspecialchars($a) ?>"><?= $a ?></button>
      <?php endforeach; ?>
    </div>

    <h4 class="profile-section-title">Или загрузить своё изображение</h4>
    <label class="btn secondary avatar-upload-btn">
      Загрузить изображение
      <input type="file" id="avatar-upload" accept="image/*" class="hidden">
    </label>
    <p class="muted small-note">JPG/PNG, будет автоматически уменьшено под размер аватара</p>
  </div>
</div>

<!-- ---------- Вкладка: история покупок ---------- -->
<div class="tab-panel" id="pt-purchases">
  <?php if (empty($purchases)): ?>
    <p class="muted">У вас пока нет покупок. <a href="index.php?r=/">Перейти в каталог</a></p>
  <?php else: ?>
    <div class="purchase-list">
      <?php foreach ($purchases as $p): ?>
        <div class="card purchase-card">
          <div class="purchase-card-head">
            <span class="muted"><?= htmlspecialchars($p['date']) ?></span>
            <span class="price"><?= number_format((int)$p['total'], 0, ',', ' ') ?> ₽</span>
          </div>
          <ul class="purchase-items">
            <?php foreach (($p['items'] ?? []) as $item): ?>
              <li><?= htmlspecialchars($item['title']) ?> — <?= $item['price'] > 0 ? number_format($item['price'], 0, ',', ' ') . ' ₽' : 'бесплатно' ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ---------- Вкладка: библиотека ---------- -->
<div class="tab-panel" id="pt-library">
  <?php if (empty($games)): ?>
    <p class="muted">Вы пока ничего не купили. <a href="index.php?r=/">Перейти в каталог</a></p>
  <?php else: ?>
    <div class="games">
      <?php foreach ($games as $i => $game): ?>
        <?php $shots = \App\Models\Game::screenshotUrls($game); ?>
        <div class="card game-card fade-in-up"
             style="animation-delay: <?= min($i * 0.05, 0.6) ?>s"
             data-id="<?= htmlspecialchars($game['id']) ?>"
             data-title="<?= htmlspecialchars($game['title']) ?>"
             data-desc="<?= htmlspecialchars($game['desc']) ?>"
             data-genre="<?= htmlspecialchars($game['genre']) ?>"
             data-price="<?= (int)$game['price'] ?>"
             data-shots="<?= htmlspecialchars(implode('|', $shots)) ?>"
             data-status="owned">
          <div class="card-cover">
            <div class="cover-skeleton"></div>
            <img src="<?= htmlspecialchars(\App\Models\Game::coverImage($game)) ?>" alt="<?= htmlspecialchars($game['title']) ?>" loading="lazy"
                 class="cover-img" onload="this.classList.add('loaded'); this.previousElementSibling.remove()">
          </div>
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
</div>

<!-- ---------- Вкладка: безопасность ---------- -->
<div class="tab-panel" id="pt-security">
  <div class="card" style="max-width: 420px;">
    <h3 class="profile-section-title" style="margin-top:0">Смена пароля</h3>
    <form id="password-form" class="card-form">
      <label>Текущий пароль
        <input type="password" name="old_password" required>
      </label>
      <span class="field-error" data-error-for="old_password"></span>

      <label>Новый пароль (от 4 символов)
        <input type="password" name="new_password" minlength="4" required>
      </label>
      <span class="field-error" data-error-for="new_password"></span>

      <label>Повторите новый пароль
        <input type="password" name="new_password_repeat" minlength="4" required>
      </label>
      <span class="field-error" data-error-for="new_password_repeat"></span>

      <button class="btn" type="submit">Сменить пароль</button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
