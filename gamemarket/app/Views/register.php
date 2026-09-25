<?php ob_start(); ?>

<h2>Регистрация</h2>

<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="index.php?r=/register">
  <input name="login" placeholder="Логин (от 3 символов)" required>
  <input name="password" type="password" placeholder="Пароль (от 4 символов)" required>
  <button>Зарегистрироваться</button>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
