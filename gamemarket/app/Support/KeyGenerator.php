<?php
namespace App\Support;

/**
 * Генератор демонстрационных ключей активации.
 *
 * ВАЖНО: это НЕ настоящие ключи Steam. Выдавать реальные, работающие
 * ключи активации Steam может только сама Valve через партнёрскую
 * программу Steamworks — сторонний сайт технически не может их
 * "сгенерировать". Здесь создаётся случайный код в привычном формате
 * (XXXXX-XXXXX-XXXXX), который имитирует внешний вид ключа для целей
 * учебного/демонстрационного проекта.
 */
class KeyGenerator
{
    // Без похожих символов 0/O, 1/I, чтобы ключ было легко прочитать и ввести.
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        $groups = [];
        for ($g = 0; $g < 3; $g++) {
            $chunk = '';
            for ($i = 0; $i < 5; $i++) {
                $chunk .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $groups[] = $chunk;
        }
        return implode('-', $groups);
    }
}
