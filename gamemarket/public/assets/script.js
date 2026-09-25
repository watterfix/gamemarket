// ==========================================================
// Game Market — клиентская логика
// ==========================================================

// ---------- Тосты (уведомления) ----------
function toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) {
        alert(message);
        return;
    }

    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.textContent = message;
    container.appendChild(el);

    requestAnimationFrame(() => el.classList.add('show'));

    setTimeout(() => {
        el.classList.remove('show');
        setTimeout(() => el.remove(), 300);
    }, 3000);
}

// ---------- Обёртка над fetch для POST-запросов (form-urlencoded) ----------
async function post(url, body) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });

        if (!res.ok && res.status !== 200) {
            let data = null;
            try { data = await res.json(); } catch (e) { /* ignore */ }
            return data || { ok: false, message: 'Ошибка сервера (' + res.status + ')' };
        }

        return await res.json();
    } catch (e) {
        return { ok: false, message: 'Не удалось связаться с сервером' };
    }
}

// ---------- Обёртка над fetch для POST-запросов с файлами (multipart/form-data) ----------
async function postForm(url, formData) {
    try {
        // Content-Type с boundary браузер выставит сам — руками его задавать нельзя
        const res = await fetch(url, { method: 'POST', body: formData });

        if (!res.ok && res.status !== 200) {
            let data = null;
            try { data = await res.json(); } catch (e) { /* ignore */ }
            return data || { ok: false, message: 'Ошибка сервера (' + res.status + ')' };
        }

        return await res.json();
    } catch (e) {
        return { ok: false, message: 'Не удалось связаться с сервером' };
    }
}

// ---------- Обновление счётчика корзины в шапке ----------
function updateCartBadge(count) {
    const link = document.querySelector('.cart-link');
    if (!link) return;

    let badge = document.getElementById('cart-badge');

    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge';
            badge.id = 'cart-badge';
            link.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

// ---------- Добавление игры в корзину (каталог) ----------
document.querySelectorAll('.btn-add').forEach(btn => {
    btn.addEventListener('click', async () => {
        btn.disabled = true;
        const gameId = btn.dataset.game;

        const data = await post('index.php?r=/cart/add', 'game=' + encodeURIComponent(gameId));
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            btn.textContent = 'В корзине';
            btn.classList.add('secondary');
            updateCartBadge(data.cartCount ?? 0);
        } else {
            btn.disabled = false;
        }
    });
});

// ---------- Удаление игры из корзины (страница корзины) ----------
document.querySelectorAll('.btn-remove').forEach(btn => {
    btn.addEventListener('click', async () => {
        btn.disabled = true;
        const gameId = btn.dataset.game;

        const data = await post('index.php?r=/cart/remove', 'game=' + encodeURIComponent(gameId));
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            updateCartBadge(data.cartCount ?? 0);
            setTimeout(() => window.location.reload(), 500);
        } else {
            btn.disabled = false;
        }
    });
});

// ---------- Модалка с полученными ключами ----------
function showKeysModal(keys) {
    const modal = document.getElementById('keys-modal');
    const list  = document.getElementById('keys-modal-list');
    if (!modal || !list || !keys || !keys.length) return;

    list.innerHTML = '';
    keys.forEach(k => {
        const row = document.createElement('div');
        row.className = 'key-box';
        row.innerHTML =
            '<span class="key-label">' + escapeHtml(k.title) + ':</span> ' +
            '<code class="key-value">' + escapeHtml(k.key) + '</code> ' +
            '<button class="btn secondary btn-copy-key" data-key="' + escapeHtml(k.key) + '">Копировать</button>';
        list.appendChild(row);
    });

    modal.classList.remove('hidden');
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

const keysModalClose = document.getElementById('keys-modal-close');
if (keysModalClose) {
    keysModalClose.addEventListener('click', () => {
        document.getElementById('keys-modal').classList.add('hidden');
        window.location.reload();
    });
}

// ---------- Копирование ключа в буфер обмена (делегирование, работает и для динамики) ----------
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-copy-key');
    if (!btn) return;

    const key = btn.dataset.key;
    navigator.clipboard?.writeText(key).then(() => {
        toast('Ключ скопирован в буфер обмена');
    }).catch(() => {
        toast('Не удалось скопировать. Ключ: ' + key, 'error');
    });
});

// ---------- Оформление покупки ----------
const checkoutBtn = document.getElementById('checkout-btn');
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', async () => {
        checkoutBtn.disabled = true;

        const data = await post('index.php?r=/cart/checkout', '');
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            if (data.keys && data.keys.length) {
                showKeysModal(data.keys);
            } else {
                setTimeout(() => window.location.reload(), 800);
            }
        } else {
            checkoutBtn.disabled = false;
        }
    });
}

// ---------- Пополнение баланса (демо-оплата картой) ----------
const topupForm = document.getElementById('topup-form');
if (topupForm) {
    // Автоформатирование номера карты — группы по 4 цифры
    const numberInput = topupForm.querySelector('[name="card_number"]');
    numberInput?.addEventListener('input', () => {
        let digits = numberInput.value.replace(/\D/g, '').slice(0, 16);
        numberInput.value = digits.replace(/(.{4})/g, '$1 ').trim();
    });

    // Автоформатирование срока действия ММ/ГГ
    const expiryInput = topupForm.querySelector('[name="card_expiry"]');
    expiryInput?.addEventListener('input', () => {
        let digits = expiryInput.value.replace(/\D/g, '').slice(0, 4);
        if (digits.length >= 3) {
            digits = digits.slice(0, 2) + '/' + digits.slice(2);
        }
        expiryInput.value = digits;
    });

    // Только цифры для CVV
    const cvvInput = topupForm.querySelector('[name="card_cvv"]');
    cvvInput?.addEventListener('input', () => {
        cvvInput.value = cvvInput.value.replace(/\D/g, '').slice(0, 3);
    });

    function clearFieldErrors() {
        topupForm.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        topupForm.querySelectorAll('input').forEach(el => el.classList.remove('input-error'));
    }

    topupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearFieldErrors();

        const submitBtn = topupForm.querySelector('button');
        submitBtn.disabled = true;

        const body = new URLSearchParams(new FormData(topupForm)).toString();
        const data = await post('index.php?r=/balance/topup', body);

        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            setTimeout(() => window.location.reload(), 800);
        } else {
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, msg]) => {
                    const errEl = topupForm.querySelector('[data-error-for="' + field + '"]');
                    const input = topupForm.querySelector('[name="' + field + '"]');
                    if (errEl) errEl.textContent = msg;
                    if (input) input.classList.add('input-error');
                });
            }
            submitBtn.disabled = false;
        }
    });
}

// ---------- Фильтр каталога по категориям ----------
const categoryFilter = document.getElementById('category-filter');
if (categoryFilter) {
    categoryFilter.addEventListener('click', (e) => {
        const chip = e.target.closest('.chip');
        if (!chip) return;

        categoryFilter.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');

        const genre = chip.dataset.genre;
        const cards = document.querySelectorAll('#games .game-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const match = genre === 'all' || card.dataset.genre === genre;
            card.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        const noGamesMsg = document.getElementById('no-games-msg');
        if (noGamesMsg) noGamesMsg.style.display = visibleCount === 0 ? '' : 'none';
    });
}

// ==========================================================
// Админ-панель
// ==========================================================

const adminCreateForm = document.getElementById('admin-create-form');
if (adminCreateForm) {
    adminCreateForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = adminCreateForm.querySelector('button');
        btn.disabled = true;

        const data = await postForm('index.php?r=/admin/game/create', new FormData(adminCreateForm));
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            setTimeout(() => window.location.reload(), 500);
        } else {
            btn.disabled = false;
        }
    });
}

document.querySelectorAll('.btn-save-game').forEach(btn => {
    btn.addEventListener('click', async () => {
        const row = btn.closest('.admin-game-card');
        const id  = row.dataset.id;

        const formData = new FormData();
        formData.set('id', id);
        row.querySelectorAll('input.field').forEach(input => {
            formData.set(input.name, input.value);
        });

        let hasFiles = false;

        const shotInput = row.querySelector('input[name="screenshot_files[]"]');
        if (shotInput) {
            Array.from(shotInput.files).forEach(f => {
                formData.append('screenshot_files[]', f);
                hasFiles = true;
            });
        }

        const coverInput = row.querySelector('input[name="cover_file"]');
        if (coverInput && coverInput.files[0]) {
            formData.append('cover_file', coverInput.files[0]);
            hasFiles = true;
        }

        btn.disabled = true;
        const data = await postForm('index.php?r=/admin/game/update', formData);
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok && hasFiles) {
            // картинка изменилась — перезагружаем, чтобы обновить превью
            setTimeout(() => window.location.reload(), 500);
        } else {
            btn.disabled = false;
        }
    });
});

document.querySelectorAll('.btn-delete-game').forEach(btn => {
    btn.addEventListener('click', async () => {
        const row = btn.closest('.admin-game-card');
        const id  = row.dataset.id;
        const title = row.querySelector('input[name="title"]')?.value || id;

        const sure = await customConfirm('Удалить игру «' + title + '» из каталога? Это действие необратимо.', 'Удаление игры');
        if (!sure) return;

        btn.disabled = true;
        const data = await post('index.php?r=/admin/game/delete', 'id=' + encodeURIComponent(id));
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            row.remove();
        } else {
            btn.disabled = false;
        }
    });
});

// ==========================================================
// Тема (светлая/тёмная)
// ==========================================================
(function () {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        const next = current === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('gm-theme', next); } catch (e) {}
    });
})();

// ==========================================================
// Адаптивное меню для мобильных
// ==========================================================
(function () {
    const toggle = document.getElementById('menu-toggle');
    const wrapper = document.getElementById('nav-wrapper');
    if (!toggle || !wrapper) return;

    toggle.addEventListener('click', () => {
        const open = wrapper.classList.toggle('open');
        toggle.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Закрываем меню при клике на ссылку/кнопку внутри него (мобильная навигация)
    wrapper.addEventListener('click', (e) => {
        if (e.target.closest('a, button:not(.icon-btn)')) {
            wrapper.classList.remove('open');
            toggle.classList.remove('open');
        }
    });
})();

// ==========================================================
// Кнопка "наверх"
// ==========================================================
(function () {
    const btn = document.getElementById('back-to-top');
    if (!btn) return;

    window.addEventListener('scroll', () => {
        btn.classList.toggle('visible', window.scrollY > 400);
    });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();

// ==========================================================
// Кастомная замена window.confirm() — стилизованная модалка
// ==========================================================
function customConfirm(message, title) {
    return new Promise((resolve) => {
        const modal   = document.getElementById('confirm-modal');
        const titleEl = document.getElementById('confirm-title');
        const textEl  = document.getElementById('confirm-text');
        const okBtn   = document.getElementById('confirm-ok');
        const cancelBtn = document.getElementById('confirm-cancel');

        if (!modal) { resolve(window.confirm(message)); return; }

        titleEl.textContent = title || 'Вы уверены?';
        textEl.textContent = message || '';
        modal.classList.remove('hidden');

        function cleanup(result) {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onOverlay);
            resolve(result);
        }

        function onOk() { cleanup(true); }
        function onCancel() { cleanup(false); }
        function onOverlay(e) { if (e.target === modal) cleanup(false); }

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        modal.addEventListener('click', onOverlay);
    });
}

// ==========================================================
// Модалка деталей игры: описание + галерея скриншотов
// ==========================================================
(function () {
    const modal   = document.getElementById('game-modal');
    if (!modal) return;

    const closeBtn = document.getElementById('game-modal-close');
    const titleEl = document.getElementById('gm-title');
    const genreEl = document.getElementById('gm-genre');
    const descEl  = document.getElementById('gm-desc');
    const priceEl = document.getElementById('gm-price');
    const actionEl = document.getElementById('gm-action');
    const mainImg = document.getElementById('gm-main-image');
    const skeleton = document.getElementById('gm-skeleton');
    const thumbsEl = document.getElementById('gm-thumbs');
    const prevBtn = document.getElementById('gm-prev');
    const nextBtn = document.getElementById('gm-next');

    let shots = [];
    let activeIndex = 0;

    function showImage(idx) {
        if (!shots.length) return;
        activeIndex = (idx + shots.length) % shots.length;

        mainImg.style.display = 'none';
        skeleton.style.display = 'block';

        const img = new Image();
        img.onload = () => {
            mainImg.src = shots[activeIndex];
            mainImg.style.display = 'block';
            skeleton.style.display = 'none';
        };
        img.src = shots[activeIndex];

        thumbsEl.querySelectorAll('img').forEach((t, i) => t.classList.toggle('active', i === activeIndex));
    }

    function openModal(card) {
        const status = card.dataset.status;
        const gameId = card.dataset.id;
        const price = parseInt(card.dataset.price, 10) || 0;

        titleEl.textContent = card.dataset.title || '';
        genreEl.textContent = card.dataset.genre || '';
        descEl.textContent = card.dataset.desc || 'Описание пока не добавлено.';
        priceEl.textContent = price > 0 ? price.toLocaleString('ru-RU') + ' ₽' : 'Бесплатно';

        shots = (card.dataset.shots || '').split('|').filter(Boolean);
        thumbsEl.innerHTML = '';
        shots.forEach((url, i) => {
            const t = document.createElement('img');
            t.src = url;
            t.loading = 'lazy';
            t.addEventListener('click', () => showImage(i));
            thumbsEl.appendChild(t);
        });
        showImage(0);

        actionEl.innerHTML = '';
        if (status === 'owned') {
            actionEl.innerHTML = '<button class="btn secondary" disabled>✓ Куплено</button>';
        } else if (status === 'incart') {
            actionEl.innerHTML = '<button class="btn secondary" disabled>В корзине</button>';
        } else if (status === 'outofstock') {
            actionEl.innerHTML = '<button class="btn secondary" disabled>Нет в наличии</button>';
        } else if (status === 'guest') {
            actionEl.innerHTML = '<a class="btn" href="index.php?r=/login">Войти, чтобы купить</a>';
        } else if (status === 'buyable') {
            const b = document.createElement('button');
            b.className = 'btn';
            b.textContent = 'Добавить в корзину';
            b.addEventListener('click', async () => {
                b.disabled = true;
                const data = await post('index.php?r=/cart/add', 'game=' + encodeURIComponent(gameId));
                toast(data.message, data.ok ? 'success' : 'error');
                if (data.ok) {
                    updateCartBadge(data.cartCount ?? 0);
                    closeModal();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    b.disabled = false;
                }
            });
            actionEl.appendChild(b);
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    document.querySelectorAll('.game-card').forEach(card => {
        if (!card.dataset.title) return; // карточка без данных для модалки (на всякий случай)
        card.addEventListener('click', (e) => {
            if (e.target.closest('button, a')) return; // не мешаем кнопкам покупки/удаления
            openModal(card);
        });
    });

    prevBtn?.addEventListener('click', () => showImage(activeIndex - 1));
    nextBtn?.addEventListener('click', () => showImage(activeIndex + 1));
    closeBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
})();

// ==========================================================
// Вкладки (профиль / админ-панель)
// ==========================================================
document.querySelectorAll('.tabs').forEach(tabs => {
    tabs.addEventListener('click', (e) => {
        const btn = e.target.closest('.tab-btn');
        if (!btn) return;

        tabs.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const targetId = btn.dataset.tab;
        const panels = document.querySelectorAll('.tab-panel');
        panels.forEach(p => p.classList.toggle('active', p.id === targetId));

        // Ленивая загрузка данных админ-вкладок при первом открытии
        if (targetId === 'at-users') loadAdminUsers();
        if (targetId === 'at-stats') loadAdminStats();
    });
});

// ==========================================================
// Профиль: аватар
// ==========================================================
(function () {
    const grid = document.getElementById('avatar-grid');
    const uploadInput = document.getElementById('avatar-upload');
    const preview = document.getElementById('profile-avatar-preview');

    async function applyAvatar(value, isImage) {
        const body = isImage
            ? 'avatar_data=' + encodeURIComponent(value)
            : 'avatar=' + encodeURIComponent(value);

        const data = await post('index.php?r=/profile/avatar', body);
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok && preview) {
            preview.innerHTML = isImage
                ? '<img src="' + value + '" alt="">'
                : '<span>' + value + '</span>';
        }
    }

    grid?.addEventListener('click', (e) => {
        const opt = e.target.closest('.avatar-option');
        if (!opt) return;
        grid.querySelectorAll('.avatar-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        applyAvatar(opt.dataset.avatar, false);
    });

    uploadInput?.addEventListener('change', () => {
        const file = uploadInput.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => {
                // Уменьшаем изображение до 200x200, чтобы не раздувать хранилище
                const size = 200;
                const canvas = document.createElement('canvas');
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext('2d');

                const scale = Math.max(size / img.width, size / img.height);
                const w = img.width * scale;
                const h = img.height * scale;
                ctx.drawImage(img, (size - w) / 2, (size - h) / 2, w, h);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                applyAvatar(dataUrl, true);
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });
})();

// ==========================================================
// Профиль: смена пароля
// ==========================================================
(function () {
    const form = document.getElementById('password-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        form.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        form.querySelectorAll('input').forEach(el => el.classList.remove('input-error'));

        const newPassword = form.querySelector('[name="new_password"]').value;
        const repeat = form.querySelector('[name="new_password_repeat"]').value;

        if (newPassword !== repeat) {
            const errEl = form.querySelector('[data-error-for="new_password_repeat"]');
            if (errEl) errEl.textContent = 'Пароли не совпадают';
            form.querySelector('[name="new_password_repeat"]').classList.add('input-error');
            return;
        }

        const btn = form.querySelector('button');
        btn.disabled = true;

        const body = 'old_password=' + encodeURIComponent(form.querySelector('[name="old_password"]').value) +
                     '&new_password=' + encodeURIComponent(newPassword);

        const data = await post('index.php?r=/profile/password', body);
        toast(data.message, data.ok ? 'success' : 'error');

        if (data.ok) {
            form.reset();
        }
        btn.disabled = false;
    });
})();

// ==========================================================
// Админ-панель: пользователи (AJAX + скелетон)
// ==========================================================
let usersLoaded = false;

async function loadAdminUsers() {
    if (usersLoaded) return;
    usersLoaded = true;

    const skeleton = document.getElementById('users-skeleton');
    const table = document.getElementById('users-table');
    const body = document.getElementById('admin-users-body');

    try {
        const res = await fetch('index.php?r=/admin/users');
        const data = await res.json();

        if (!data.ok) {
            toast(data.message || 'Не удалось загрузить пользователей', 'error');
            return;
        }

        body.innerHTML = '';
        data.users.forEach(u => {
            const tr = document.createElement('tr');
            tr.dataset.login = u.login;
            tr.innerHTML =
                '<td>' + escapeHtml(u.login) + '</td>' +
                '<td><span class="role-badge ' + (u.role === 'admin' ? 'admin' : '') + '">' + (u.role === 'admin' ? 'Администратор' : 'Пользователь') + '</span></td>' +
                '<td>' + u.balance.toLocaleString('ru-RU') + '</td>' +
                '<td>' + u.libraryCount + '</td>' +
                '<td>' + u.purchaseCount + '</td>' +
                '<td>' + u.totalSpent.toLocaleString('ru-RU') + '</td>' +
                '<td><button class="btn secondary btn-toggle-role">' + (u.role === 'admin' ? 'Снять права админа' : 'Сделать админом') + '</button></td>';
            body.appendChild(tr);
        });

        skeleton.classList.add('hidden');
        table.classList.remove('hidden');
    } catch (e) {
        toast('Не удалось связаться с сервером', 'error');
    }
}

document.getElementById('admin-users-body')?.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-toggle-role');
    if (!btn) return;

    const row = btn.closest('tr');
    const login = row.dataset.login;
    const badge = row.querySelector('.role-badge');
    const isAdmin = badge.classList.contains('admin');
    const newRole = isAdmin ? 'user' : 'admin';

    const sure = await customConfirm(
        (isAdmin ? 'Снять права администратора у «' : 'Назначить администратором «') + login + '»?',
        'Изменение роли'
    );
    if (!sure) return;

    btn.disabled = true;
    const data = await post('index.php?r=/admin/user/role', 'login=' + encodeURIComponent(login) + '&role=' + newRole);
    toast(data.message, data.ok ? 'success' : 'error');

    if (data.ok) {
        badge.classList.toggle('admin', newRole === 'admin');
        badge.textContent = newRole === 'admin' ? 'Администратор' : 'Пользователь';
        btn.textContent = newRole === 'admin' ? 'Снять права админа' : 'Сделать админом';
    }
    btn.disabled = false;
});

// ==========================================================
// Админ-панель: статистика продаж (AJAX + скелетон)
// ==========================================================
let statsLoaded = false;

async function loadAdminStats() {
    if (statsLoaded) return;
    statsLoaded = true;

    const skeleton = document.getElementById('stats-skeleton');
    const content = document.getElementById('stats-content');
    const cardsEl = document.getElementById('stats-cards');
    const chartEl = document.getElementById('top-games-chart');

    try {
        const res = await fetch('index.php?r=/admin/stats');
        const data = await res.json();

        if (!data.ok) {
            toast(data.message || 'Не удалось загрузить статистику', 'error');
            return;
        }

        const cards = [
            ['Выручка', data.totalRevenue.toLocaleString('ru-RU') + ' ₽'],
            ['Продаж', data.totalSales],
            ['Продано ключей', data.totalItems],
            ['Пользователей', data.usersCount],
            ['Администраторов', data.adminsCount],
            ['Игр в каталоге', data.gamesCount],
        ];
        cardsEl.innerHTML = cards.map(([label, value]) =>
            '<div class="stat-card"><div class="stat-value">' + value + '</div><div class="stat-label">' + label + '</div></div>'
        ).join('');

        if (data.topGames && data.topGames.length) {
            const max = Math.max(...data.topGames.map(g => g.count));
            chartEl.innerHTML = data.topGames.map(g =>
                '<div class="bar-row">' +
                    '<span class="bar-label">' + escapeHtml(g.title) + '</span>' +
                    '<div class="bar-track"><div class="bar-fill" style="width:' + Math.round((g.count / max) * 100) + '%"></div></div>' +
                    '<span class="bar-count">' + g.count + ' шт.</span>' +
                '</div>'
            ).join('');
        } else {
            chartEl.innerHTML = '<p class="muted">Продаж пока не было.</p>';
        }

        skeleton.classList.add('hidden');
        content.classList.remove('hidden');
    } catch (e) {
        toast('Не удалось связаться с сервером', 'error');
    }
}
