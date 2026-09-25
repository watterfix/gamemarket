<?php ob_start(); ?>

<div class="toolbar">
  <h2>Админ-панель</h2>
  <p class="muted">Каталог игр, пользователи и статистика продаж</p>
</div>

<div class="tabs" id="admin-tabs">
  <button class="tab-btn active" data-tab="at-games">Игры</button>
  <button class="tab-btn" data-tab="at-users">Пользователи</button>
  <button class="tab-btn" data-tab="at-stats">Статистика продаж</button>
</div>

<!-- ---------- Вкладка: игры ---------- -->
<div class="tab-panel active" id="at-games">
  <div class="card admin-add">
    <h3>Добавить игру</h3>
    <form id="admin-create-form" class="admin-form">
      <input name="title" placeholder="Название" required>
      <input name="genre" placeholder="Категория" required>
      <input name="price" type="number" min="0" placeholder="Цена, ₽" required>
      <input name="stock" type="number" placeholder="Остаток ключей (-1 = неограничено)" value="10">
      <input name="desc" placeholder="Описание" class="wide">
      <input name="screenshots" placeholder="Ссылки на скриншоты через запятую (необязательно)" class="wide">
      <label class="wide small-note">Обложка — свой файл PNG/JPG (необязательно)
        <input type="file" name="cover_file" accept="image/png,image/jpeg">
      </label>
      <label class="wide small-note">Скриншоты — свои файлы PNG/JPG, до 6 штук (необязательно)
        <input type="file" name="screenshot_files[]" accept="image/png,image/jpeg" multiple>
      </label>
      <button class="btn" type="submit">Добавить игру</button>
    </form>
  </div>

  <div class="games-admin-list" id="admin-games-body">
    <?php foreach ($games as $game): ?>
      <div class="admin-game-card" data-id="<?= htmlspecialchars($game['id']) ?>">
        <div class="ag-row-head">
          <div class="ag-cover">
            <img class="admin-cover-thumb" src="<?= htmlspecialchars(\App\Models\Game::coverImage($game)) ?>" alt="">
            <input type="file" class="field-file" name="cover_file" accept="image/png,image/jpeg" title="Заменить обложку">
          </div>
          <div class="ag-field">
            <span class="ag-label">Название</span>
            <input class="field" name="title" value="<?= htmlspecialchars($game['title']) ?>">
          </div>
          <div class="ag-field">
            <span class="ag-label">Категория</span>
            <input class="field" name="genre" value="<?= htmlspecialchars($game['genre']) ?>">
          </div>
          <div class="ag-field ag-field-small">
            <span class="ag-label">Цена, ₽</span>
            <input class="field small" name="price" type="number" min="0" value="<?= (int)$game['price'] ?>">
          </div>
          <div class="ag-field ag-field-small">
            <span class="ag-label">Остаток (-1 = ∞)</span>
            <input class="field small" name="stock" type="number" value="<?= (int)$game['stock'] ?>">
          </div>
        </div>

        <div class="ag-field ag-field-wide">
          <span class="ag-label">Описание</span>
          <input class="field wide" name="desc" value="<?= htmlspecialchars($game['desc']) ?>">
        </div>

        <div class="ag-field ag-field-wide">
          <span class="ag-label">Скриншоты (URL через запятую)</span>
          <input class="field wide" name="screenshots" value="<?= htmlspecialchars(implode(', ', $game['screenshots'] ?? [])) ?>" placeholder="авто-заглушка">
          <input type="file" class="field-file" name="screenshot_files[]" accept="image/png,image/jpeg" multiple title="Добавить свои PNG/JPG скриншоты">
        </div>

        <div class="admin-actions">
          <button class="btn secondary btn-save-game">Сохранить</button>
          <button class="btn danger btn-delete-game">Удалить</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ---------- Вкладка: пользователи ---------- -->
<div class="tab-panel" id="at-users">
  <div class="admin-table-wrap">
    <div id="users-skeleton" class="skeleton-table">
      <?php for ($i = 0; $i < 4; $i++): ?><div class="skeleton-row"></div><?php endfor; ?>
    </div>
    <table class="admin-table users-table hidden" id="users-table">
      <thead>
        <tr>
          <th>Пользователь</th>
          <th>Роль</th>
          <th>Баланс, ₽</th>
          <th>В библиотеке</th>
          <th>Покупок</th>
          <th>Потрачено, ₽</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="admin-users-body"></tbody>
    </table>
  </div>
</div>

<!-- ---------- Вкладка: статистика продаж ---------- -->
<div class="tab-panel" id="at-stats">
  <div id="stats-skeleton" class="stats-grid">
    <?php for ($i = 0; $i < 4; $i++): ?><div class="skeleton-card"></div><?php endfor; ?>
  </div>
  <div id="stats-content" class="hidden">
    <div class="stats-grid" id="stats-cards"></div>
    <div class="card top-games-card">
      <h3 class="profile-section-title" style="margin-top:0">Топ продаваемых игр</h3>
      <div id="top-games-chart" class="bar-chart"></div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
