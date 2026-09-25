<?php
namespace App\Support;

/**
 * Проверка формата банковской карты для демо-пополнения баланса.
 *
 * Это НЕ настоящий платёжный шлюз: никакие реальные списания не
 * производятся и полный номер карты нигде не сохраняется — только
 * проверяется, что введённые данные похожи на настоящую карту
 * (номер, срок действия, CVV), и после проверки запоминаются лишь
 * последние 4 цифры для показа пользователю.
 */
class CardValidator
{
    public static function validate(array $data): array
    {
        $errors = [];

        $number = preg_replace('/\D/', '', $data['card_number'] ?? '');
        $name   = trim((string)($data['card_name'] ?? ''));
        $expiry = trim((string)($data['card_expiry'] ?? ''));
        $cvv    = trim((string)($data['card_cvv'] ?? ''));

        // ---- Номер карты ----
        $brand = null;
        if (!preg_match('/^\d{16}$/', $number)) {
            $errors['card_number'] = 'Номер карты должен содержать 16 цифр';
        } elseif (!self::luhnValid($number)) {
            $errors['card_number'] = 'Неверный номер карты (не проходит проверку)';
        } else {
            $brand = self::detectBrand($number);
            if (!$brand) {
                $errors['card_number'] = 'Поддерживаются только карты МИР, Visa и Mastercard';
            }
        }

        // ---- Имя держателя ----
        if ($name === '' || !preg_match('/^[A-Za-zА-ЯЁа-яё\'\-\s]{3,50}$/u', $name)) {
            $errors['card_name'] = 'Введите имя держателя карты (как на карте)';
        }

        // ---- Срок действия ----
        if (!preg_match('/^(\d{2})\/(\d{2})$/', $expiry, $m)) {
            $errors['card_expiry'] = 'Формат срока действия — ММ/ГГ';
        } else {
            $month = (int)$m[1];
            $year  = (int)$m[2] + 2000;

            if ($month < 1 || $month > 12) {
                $errors['card_expiry'] = 'Некорректный месяц';
            } else {
                // Карта действительна до последнего дня месяца включительно.
                $expiresAt = mktime(23, 59, 59, $month + 1, 0, $year);
                if ($expiresAt < time()) {
                    $errors['card_expiry'] = 'Срок действия карты истёк';
                }
            }
        }

        // ---- CVV ----
        if (!preg_match('/^\d{3}$/', $cvv)) {
            $errors['card_cvv'] = 'CVV/CVC — 3 цифры';
        }

        if (!empty($errors)) {
            return [
                'ok'      => false,
                'errors'  => $errors,
                'message' => 'Проверьте правильность данных карты',
            ];
        }

        return [
            'ok'    => true,
            'brand' => $brand,
            'last4' => substr($number, -4),
        ];
    }

    private static function luhnValid(string $number): bool
    {
        $sum = 0;
        $alt = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int)$number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return $sum % 10 === 0;
    }

    private static function detectBrand(string $number): ?string
    {
        // МИР: БИН 2200–2204
        if (preg_match('/^220[0-4]/', $number)) {
            return 'МИР';
        }
        // Visa: начинается с 4
        if (preg_match('/^4/', $number)) {
            return 'Visa';
        }
        // Mastercard: 51–55 и 2221–2720
        if (preg_match('/^5[1-5]/', $number)) {
            return 'Mastercard';
        }
        if (preg_match('/^(222[1-9]|22[3-9]\d|2[3-6]\d{2}|27[01]\d|2720)/', $number)) {
            return 'Mastercard';
        }

        return null;
    }
}
