<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tailwind_cdn_script(): string
{
    // Tailwind Play CDN (suitable for simple/internal apps; for production you might compile Tailwind).
    return '<script src="https://cdn.tailwindcss.com"></script>';
}

function app_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (!$https && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $https = strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
    }

    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return '';
    }

    return $scheme . '://' . $host;
}

function announcement_path(int $id): string
{
    return BASE_URL . '/announcement.php?id=' . $id;
}

function announcement_url(int $id): string
{
    $origin = app_origin();
    $path = announcement_path($id);
    return $origin !== '' ? ($origin . $path) : $path;
}

function format_date_id(string $dateYmd): string
{
    // Input: YYYY-MM-DD
    $ts = strtotime($dateYmd);
    if ($ts === false) {
        return $dateYmd;
    }

    $dayNames = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
    $monthNames = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $dayOfWeek = (int)date('N', $ts);
    $day = (int)date('j', $ts);
    $month = (int)date('n', $ts);
    $year = (int)date('Y', $ts);

    $dn = $dayNames[$dayOfWeek] ?? date('l', $ts);
    $mn = $monthNames[$month] ?? date('F', $ts);

    return sprintf('%s, %d %s %d', $dn, $day, $mn, $year);
}

function format_time_range(string $startTime, string $endTime): string
{
    // Input: HH:MM:SS or HH:MM
    $s = substr($startTime, 0, 5);
    $e = substr($endTime, 0, 5);
    return $s . '-' . $e;
}
