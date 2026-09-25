<?php ob_start(); ?>

<h2>Корзина</h2>

<?php if (empty($items)): ?>
  <p class="muted">Корзина пуста. <a href="index.php?r=/">Перейти в каталог</a></p>
<?php else: ?>
  <div class="cart-list">
    <?php foreach ($items as $game): ?>
      <div class="cart-item">
        <div>
          <h4><?= htmlspecialchars($game['title']) ?></h4>
          <span class="muted"><?= htmlspecialchars($game['genre']) ?></span>
        </div>
        <div class="cart-item-right">
          <span class="price"><?= $game['price'] > 0 ? number_format($game['price'], 0, ',', ' ') . ' ₽' : 'Бесплатно' ?></span>
          <button class="btn secondary btn-remove" data-game="<?= htmlspecialchars($game['id']) ?>">Удалить</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="cart-summary">
    <p>Ваш баланс: <strong><?= number_format($balance, 0, ',', ' ') ?> ₽</strong></p>
    <p>Итого к оплате: <strong><?= number_format($total, 0, ',', ' ') ?> ₽</strong></p>
    <button id="checkout-btn" class="btn">Оформить покупку</button>
  </div>
<?php endif; ?>

<div class="topup card">
  <h3>Пополнение баланса</h3>
  <p class="muted">Демо-оплата банковской картой. Реальное списание денег не производится, номер карты нигде не сохраняется — только проверяется его формат.</p>

  <form id="topup-form" class="card-form">
    <div class="form-section">
      <label>Сумма пополнения, ₽
        <input type="number" name="amount" min="1" max="100000" placeholder="Например, 1000" required>
      </label>
    </div>

    <div class="form-section">
      <p class="form-section-title">Данные карты</p>

      <label>Номер карты
        <input type="text" name="card_number" inputmode="numeric" autocomplete="cc-number"
               placeholder="2200 1234 5678 9010" maxlength="19" required>
      </label>
      <span class="field-error" data-error-for="card_number"></span>

      <label>Имя держателя
        <input type="text" name="card_name" autocomplete="cc-name" placeholder="IVAN IVANOV" maxlength="50" required>
      </label>
      <span class="field-error" data-error-for="card_name"></span>

      <div class="card-form-row">
        <div>
          <label>Срок действия
            <input type="text" name="card_expiry" autocomplete="cc-exp" placeholder="ММ/ГГ" maxlength="5" required>
          </label>
          <span class="field-error" data-error-for="card_expiry"></span>
        </div>
        <div>
          <label>CVV/CVC
            <input type="text" name="card_cvv" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="3" required>
          </label>
          <span class="field-error" data-error-for="card_cvv"></span>
        </div>
      </div>
    </div>

    <button class="btn" type="submit">Оплатить и пополнить</button>
  </form>
</div>

<div id="keys-modal" class="modal-overlay hidden">
  <div class="modal">
    <h3>Покупка успешна 🎉</h3>
    <p class="muted">Ваши ключи активации (также доступны в «Библиотеке»):</p>
    <div id="keys-modal-list"></div>
    <button class="btn" id="keys-modal-close">Понятно</button>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
