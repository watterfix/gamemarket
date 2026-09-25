<?php ob_start(); ?>

<h2>Вход</h2>

<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="index.php?r=/login">
  <input name="login" placeholder="Логин" required>
  <input name="password" type="password" placeholder="Пароль" required>
  <button>Войти</button>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
