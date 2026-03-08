<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function normalize_wa_phone(string $input): string
{
    $digits = preg_replace('/\D+/', '', $input) ?? '';
    $digits = trim($digits);

    if ($digits === '') {
        return '';
    }

    // Common Indonesian formats:
    // 08xxxx -> 62xxxx
    // 8xxxx  -> 62xxxx
    // 62xxxx -> 62xxxx
    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (str_starts_with($digits, '8')) {
        $digits = '62' . $digits;
    }

    return $digits;
}

function get_setting(string $key, ?string $default = null): ?string
{
    try {
        $stmt = db()->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        if ($val === false) {
            return $default;
        }
        $val = (string)$val;
        return $val !== '' ? $val : $default;
    } catch (Throwable $e) {
        // If table doesn't exist yet, fall back.
        return $default;
    }
}

function set_setting(string $key, string $value): void
{
    db()->prepare(
        'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)' 
    )->execute([$key, $value]);
}

function wa_phone(): string
{
    $stored = get_setting('wa_phone', '') ?? '';
    return normalize_wa_phone($stored);
}

function wa_url(string $message): string
{
    $phone = wa_phone();
    if ($phone === '') {
        return '';
    }

    $waText = rawurlencode($message);
    return 'https://wa.me/' . $phone . '?text=' . $waText;
}

function normalize_tele_username(string $input): string
{
    $input = trim($input);
    if ($input === '') {
        return '';
    }

    // Accept common formats:
    // @username
    // username
    // https://t.me/username
    // t.me/username
    if (preg_match('~t\.me/([A-Za-z0-9_]{5,32})~', $input, $m)) {
        return $m[1];
    }

    if ($input[0] === '@') {
        $input = substr($input, 1);
    }

    // Telegram username rules (simplified): 5-32 chars, letters/numbers/underscore
    if (!preg_match('/^[A-Za-z0-9_]{5,32}$/', $input)) {
        return '';
    }

    return $input;
}

function tele_username(): string
{
    $stored = get_setting('tele_username', '') ?? '';
    $norm = normalize_tele_username($stored);
    return $norm;
}

function tele_url(): string
{
    $u = tele_username();
    return $u !== '' ? ('https://t.me/' . $u) : '';
}
