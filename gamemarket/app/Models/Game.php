<?php
namespace App\Models;

/**
 * Каталог игр хранится в storage/games.json (а не в PHP-файле),
 * чтобы админ-панель могла редактировать его прямо во время работы сайта.
 *
 * Поле stock — остаток ключей:
 *   -1  — неограниченно,
 *    0  — ключи закончились (покупка недоступна),
 *   >0  — сколько ключей ещё можно продать.
 */
class Game
{
    private static string $file = __DIR__ . '/../../storage/games.json';

    public static function all(): array
    {
        if (!file_exists(self::$file)) {
            return [];
        }

        $games = json_decode(file_get_contents(self::$file), true) ?: [];

        foreach ($games as &$g) {
            $g['price'] = (int)($g['price'] ?? 0);
            $g['stock'] = isset($g['stock']) ? (int)$g['stock'] : -1;
            $g['screenshots'] = array_values(array_filter((array)($g['screenshots'] ?? [])));
        }

        return $games;
    }

    /**
     * URL обложки игры. Порядок приоритета:
     *  1) поле "cover" в games.json (если прописали вручную конкретный файл/ссылку);
     *  2) файл public/assets/images/covers/{id}.jpg|png|webp — кладёте картинку
     *     с именем = id игры, ничего в JSON редактировать не нужно;
     *  3) сгенерированная SVG-заглушка с названием и жанром игры.
     */
    public static function coverImage(array $game): string
    {
        if (!empty($game['cover'])) {
            return $game['cover'];
        }

        $local = self::findLocalImage('covers', $game['id']);
        if ($local) {
            return $local;
        }

        return self::svgPlaceholder($game, 500, 280);
    }

    /**
     * Скриншоты игры. Порядок приоритета:
     *  1) поле "screenshots" в games.json;
     *  2) файлы public/assets/images/screenshots/{id}-1.jpg, {id}-2.jpg, {id}-3.jpg...
     *     (можно от 1 до 6 штук, номер в конце имени файла обязателен);
     *  3) сгенерированные SVG-заглушки.
     */
    public static function screenshotUrls(array $game): array
    {
        if (!empty($game['screenshots'])) {
            return $game['screenshots'];
        }

        $local = self::findLocalScreenshots($game['id']);
        if ($local) {
            return $local;
        }

        $urls = [];
        for ($i = 1; $i <= 3; $i++) {
            $urls[] = self::svgPlaceholder($game, 700, 394, 'Скриншот ' . $i);
        }
        return $urls;
    }

    /**
     * Ищет в public/assets/images/{$subfolder}/ файл с именем "{id}.<ext>"
     * (png/jpg/jpeg/webp) и возвращает относительный URL для <img src>,
     * либо null, если файла нет.
     */
    private static function findLocalImage(string $subfolder, string $id): ?string
    {
        $dir = __DIR__ . '/../../public/assets/images/' . $subfolder;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file($dir . '/' . $id . '.' . $ext)) {
                return 'assets/images/' . $subfolder . '/' . $id . '.' . $ext;
            }
        }
        return null;
    }

    /**
     * Ищет в public/assets/images/screenshots/ файлы "{id}-1.<ext>",
     * "{id}-2.<ext>" и т.д. (до 6 штук) и возвращает их относительные URL
     * по порядку номеров. Как только очередной номер не найден — останавливается.
     */
    private static function findLocalScreenshots(string $id): array
    {
        $dir  = __DIR__ . '/../../public/assets/images/screenshots';
        $urls = [];

        for ($i = 1; $i <= 6; $i++) {
            $found = null;
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                if (is_file($dir . '/' . $id . '-' . $i . '.' . $ext)) {
                    $found = 'assets/images/screenshots/' . $id . '-' . $i . '.' . $ext;
                    break;
                }
            }
            if (!$found) break;
            $urls[] = $found;
        }

        return $urls;
    }

    /**
     * Генерирует SVG-заглушку (в виде data URI) с названием и жанром игры
     * на цветном градиенте. Цвет градиента стабильно зависит от id игры
     * (и метки, если это скриншот), поэтому у каждой игры — свой узнаваемый
     * цвет, но между перезагрузками страницы картинка не «прыгает».
     */
    private static function svgPlaceholder(array $game, int $w, int $h, ?string $label = null): string
    {
        $palettes = [
            ['#0ea5e9', '#6366f1'],
            ['#f97316', '#ef4444'],
            ['#22c55e', '#0ea5e9'],
            ['#a855f7', '#ec4899'],
            ['#f59e0b', '#dc2626'],
            ['#14b8a6', '#3b82f6'],
            ['#e11d48', '#7c3aed'],
            ['#84cc16', '#059669'],
        ];
        $key   = $game['id'] . '|' . ($label ?? '');
        [$c1, $c2] = $palettes[crc32($key) % count($palettes)];

        $lines    = self::wrapTitle((string)($game['title'] ?? ''), $w > 600 ? 34 : 20);
        $fontSize = count($lines) > 1 ? (int)round($w / 18) : (int)round($w / 15);
        $lineGap  = $fontSize + 6;
        $baseY    = $h - 34 - (count($lines) - 1) * $lineGap;

        $tspans = '';
        foreach ($lines as $idx => $line) {
            $y = $baseY + $idx * $lineGap;
            $safe = htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $tspans .= "<text x=\"22\" y=\"{$y}\" font-family=\"Arial, sans-serif\" font-size=\"{$fontSize}\" font-weight=\"700\" fill=\"#ffffff\">{$safe}</text>";
        }

        $topLabel = htmlspecialchars($label ?? (string)($game['genre'] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $circleCx = $w - 40;
        $circleCy2 = $h - 30;

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" width="{$w}" height="{$h}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#g)"/>
  <circle cx="{$circleCx}" cy="40" r="90" fill="rgba(255,255,255,0.08)"/>
  <circle cx="60" cy="{$circleCy2}" r="60" fill="rgba(0,0,0,0.10)"/>
  <text x="22" y="30" font-family="Arial, sans-serif" font-size="13" font-weight="700" letter-spacing="0.5" fill="rgba(255,255,255,0.85)">{$topLabel}</text>
  {$tspans}
</svg>
SVG;

        return 'data:image/svg+xml,' . rawurlencode($svg);
    }

    /**
     * Разбивает название игры на строки (максимум 3), чтобы длинные
     * названия помещались в SVG-заглушку и не вылезали за края.
     */
    private static function wrapTitle(string $title, int $maxChars, int $maxLines = 3): array
    {
        $words   = preg_split('/\s+/', trim($title)) ?: [];
        $lines   = [];
        $current = '';

        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($test) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
                if (count($lines) === $maxLines) {
                    $current = '';
                    break;
                }
            } else {
                $current = $test;
            }
        }
        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        return $lines ?: [$title];
    }

    /**
     * Сохраняет загруженные файлы (PNG/JPG) из $_FILES в public/assets/uploads/games
     * и возвращает массив относительных URL для сохранённых картинок.
     * Принимает как одиночный <input type="file">, так и multiple
     * (в этом случае PHP отдаёт под-массивы name/tmp_name/... вместо строк).
     */
    public static function storeUploadedImages($filesField, int $limit = 6): array
    {
        if (!$filesField || !is_array($filesField) || !isset($filesField['name'])) {
            return [];
        }

        // Нормализуем в список отдельных файлов независимо от структуры $_FILES
        $items = [];
        if (is_array($filesField['name'])) {
            foreach ($filesField['name'] as $i => $name) {
                if ($name === '') continue;
                $items[] = [
                    'name'     => $name,
                    'type'     => $filesField['type'][$i] ?? '',
                    'tmp_name' => $filesField['tmp_name'][$i] ?? '',
                    'error'    => $filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size'     => $filesField['size'][$i] ?? 0,
                ];
            }
        } elseif ($filesField['name'] !== '') {
            $items[] = $filesField;
        }

        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
        $maxSize = 5 * 1024 * 1024; // 5 МБ на файл
        $dir     = __DIR__ . '/../../public/assets/uploads/games';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $urls = [];
        foreach ($items as $file) {
            if (count($urls) >= $limit) break;
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (!is_uploaded_file($file['tmp_name'])) continue;
            if ((int)$file['size'] > $maxSize) continue;

            $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : $file['type'];
            if (!isset($allowed[$mime])) continue;

            $filename = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];

            if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
                $urls[] = 'assets/uploads/games/' . $filename;
            }
        }

        return $urls;
    }

    /**
     * Разбирает текстовое поле формы (по одной ссылке на строку или через запятую)
     * в массив URL.
     */
    public static function parseScreenshots(string $raw): array
    {
        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
        $parts = array_map('trim', $parts);
        return array_values(array_filter($parts, fn($u) => $u !== ''));
    }

    public static function find(string $id): ?array
    {
        foreach (self::all() as $g) {
            if ($g['id'] === $id) {
                return $g;
            }
        }
        return null;
    }

    public static function hasStock(string $id): bool
    {
        $g = self::find($id);
        if (!$g) return false;
        return $g['stock'] === -1 || $g['stock'] > 0;
    }

    public static function save(array $games): void
    {
        file_put_contents(
            self::$file,
            json_encode(array_values($games), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function create(array $data): array
    {
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => 'Укажите название игры'];
        }

        $price = (int)($data['price'] ?? 0);
        if ($price < 0) $price = 0;

        $games  = self::all();
        $ids    = array_column($games, 'id');
        $baseId = self::slug($title);
        $id     = $baseId;
        $i      = 2;
        while (in_array($id, $ids, true)) {
            $id = $baseId . '-' . $i;
            $i++;
        }

        $screenshots = self::parseScreenshots((string)($data['screenshots'] ?? ''));
        if (!empty($data['uploaded_screenshots']) && is_array($data['uploaded_screenshots'])) {
            $screenshots = array_merge($screenshots, $data['uploaded_screenshots']);
        }

        $game = [
            'id'    => $id,
            'title' => $title,
            'genre' => trim((string)($data['genre'] ?? '')) ?: 'Разное',
            'price' => $price,
            'desc'  => trim((string)($data['desc'] ?? '')),
            'stock' => isset($data['stock']) && $data['stock'] !== '' ? (int)$data['stock'] : -1,
            'screenshots' => $screenshots,
        ];

        if (!empty($data['uploaded_cover'])) {
            $game['cover'] = $data['uploaded_cover'];
        }

        $games[] = $game;
        self::save($games);

        return ['ok' => true, 'message' => "Игра «{$title}» добавлена", 'game' => $game];
    }

    public static function update(string $id, array $data): array
    {
        $games = self::all();

        foreach ($games as &$g) {
            if ($g['id'] !== $id) continue;

            if (isset($data['title']) && trim($data['title']) !== '') {
                $g['title'] = trim($data['title']);
            }
            if (isset($data['genre']) && trim($data['genre']) !== '') {
                $g['genre'] = trim($data['genre']);
            }
            if (isset($data['desc'])) {
                $g['desc'] = trim($data['desc']);
            }
            if (isset($data['price']) && $data['price'] !== '') {
                $g['price'] = max(0, (int)$data['price']);
            }
            if (isset($data['stock']) && $data['stock'] !== '') {
                $g['stock'] = (int)$data['stock'];
            }
            if (isset($data['screenshots'])) {
                $g['screenshots'] = self::parseScreenshots((string)$data['screenshots']);
            }
            if (!empty($data['uploaded_screenshots']) && is_array($data['uploaded_screenshots'])) {
                $g['screenshots'] = array_merge($g['screenshots'] ?? [], $data['uploaded_screenshots']);
            }
            if (!empty($data['uploaded_cover'])) {
                $g['cover'] = $data['uploaded_cover'];
            }

            self::save($games);
            return ['ok' => true, 'message' => "«{$g['title']}» обновлена", 'game' => $g];
        }

        return ['ok' => false, 'message' => 'Игра не найдена'];
    }

    public static function delete(string $id): array
    {
        $games    = self::all();
        $filtered = array_values(array_filter($games, fn($g) => $g['id'] !== $id));

        if (count($filtered) === count($games)) {
            return ['ok' => false, 'message' => 'Игра не найдена'];
        }

        self::save($filtered);
        return ['ok' => true, 'message' => 'Игра удалена из каталога'];
    }

    public static function decrementStock(string $id): void
    {
        $games = self::all();
        foreach ($games as &$g) {
            if ($g['id'] === $id && $g['stock'] > 0) {
                $g['stock']--;
            }
        }
        self::save($games);
    }

    private static function slug(string $title): string
    {
        $slug = mb_strtolower(trim($title));

        $translit = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
            'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
            'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch',
            'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        ];

        $slug = strtr($slug, $translit);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : uniqid('game-');
    }
}
