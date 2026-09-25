<?php
$currentUser    = $user ?? null;
$currentBalance = $balance ?? null;
$cartCount      = 0;
$isAdmin        = false;
$currentAvatar  = null;

if ($currentUser) {
    $u = \App\Models\User::find($currentUser);
    if ($u) {
        $cartCount = count($u['cart']);
        if ($currentBalance === null) $currentBalance = $u['balance'];
        $isAdmin = ($u['role'] ?? 'user') === 'admin';
        $currentAvatar = $u['avatar'] ?? null;
    }
}

// Небольшой хелпер: рендер круглой аватарки (эмодзи / картинка / первая буква логина)
function renderAvatar(?string $avatar, string $login, string $class = 'avatar-chip'): string
{
    if ($avatar && str_starts_with($avatar, 'data:image')) {
        return '<span class="' . $class . '"><img src="' . htmlspecialchars($avatar) . '" alt=""></span>';
    }
    if ($avatar) {
        return '<span class="' . $class . '">' . htmlspecialchars($avatar) . '</span>';
    }
    $letter = mb_strtoupper(mb_substr($login, 0, 1));
    return '<span class="' . $class . '">' . htmlspecialchars($letter) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Game Market</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script>
    // Применяем сохранённую тему ДО отрисовки страницы, чтобы не было "мигания" темы
    (function () {
      try {
        var theme = localStorage.getItem('gm-theme');
        if (!theme) {
          theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        }
        document.documentElement.setAttribute('data-theme', theme);
      } catch (e) {}
    })();
  </script>
</head>
<body>
<header>
  <a href="index.php?r=/" class="logo"><h1>Game Market</h1></a>

  <button id="menu-toggle" class="menu-toggle" aria-label="Меню" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>

  <div class="nav-wrapper" id="nav-wrapper">
    <nav class="nav-links">
      <a href="index.php?r=/">Каталог</a>
      <?php if ($currentUser): ?>
        <a href="index.php?r=/profile">Профиль</a>
        <a href="index.php?r=/cart" class="cart-link">
          Корзина
          <?php if ($cartCount > 0): ?>
            <span class="badge" id="cart-badge"><?= $cartCount ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
        <a href="index.php?r=/admin" class="admin-link">⚙ Админ-панель</a>
      <?php endif; ?>
    </nav>

    <div class="header-right">
      <button id="theme-toggle" class="icon-btn" title="Сменить тему" aria-label="Сменить тему">
        <span class="theme-icon-dark">🌙</span><span class="theme-icon-light">☀️</span>
      </button>
      <?php if ($currentUser): ?>
        <span class="balance-chip" id="balance-chip">💰 <?= number_format((float)$currentBalance, 0, ',', ' ') ?> ₽</span>
        <a href="index.php?r=/profile" class="user-chip user-chip-link">
          <?= renderAvatar($currentAvatar, $currentUser) ?>
          <?= htmlspecialchars($currentUser) ?>
        </a>
        <form method="POST" action="index.php?r=/logout" style="display:inline">
          <button class="secondary">Выйти</button>
        </form>
      <?php else: ?>
        <a href="index.php?r=/login">Вход</a>
        <a href="index.php?r=/register">Регистрация</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div id="toast-container"></div>

<main>
  <?= $content ?>
</main>

<button id="back-to-top" class="back-to-top hidden" title="Наверх" aria-label="Наверх">↑</button>

<!-- Модалка деталей игры: описание + скриншоты -->
<div id="game-modal" class="modal-overlay hidden">
  <div class="modal modal-wide">
    <button class="modal-close" id="game-modal-close" aria-label="Закрыть">✕</button>
    <div class="game-modal-genre" id="gm-genre"></div>
    <h3 id="gm-title"></h3>
    <p class="game-modal-desc" id="gm-desc"></p>

    <div class="gallery">
      <div class="gallery-main">
        <div class="gallery-skeleton" id="gm-skeleton"></div>
        <img id="gm-main-image" class="gallery-main-img" alt="Скриншот" style="display:none">
        <button class="gallery-nav gallery-prev" id="gm-prev" aria-label="Предыдущий скриншот">‹</button>
        <button class="gallery-nav gallery-next" id="gm-next" aria-label="Следующий скриншот">›</button>
      </div>
      <div class="gallery-thumbs" id="gm-thumbs"></div>
    </div>

    <div class="game-modal-footer">
      <span class="price" id="gm-price"></span>
      <div id="gm-action"></div>
    </div>
  </div>
</div>

<!-- Универсальная красивая замена window.confirm() -->
<div id="confirm-modal" class="modal-overlay hidden">
  <div class="modal modal-confirm">
    <h3 id="confirm-title">Вы уверены?</h3>
    <p class="muted" id="confirm-text"></p>
    <div class="confirm-actions">
      <button class="btn secondary" id="confirm-cancel">Отмена</button>
      <button class="btn danger" id="confirm-ok">Подтвердить</button>
    </div>
  </div>
</div>

<script src="assets/script.js"></script>
</body>
</html>
