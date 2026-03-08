<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, $port, DB_NAME, DB_CHARSET);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Provide a friendly message in the browser (common first-run issue on XAMPP).
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
            $safeMsg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            echo "<!doctype html><html lang=\"id\"><head><meta charset=\"utf-8\"/><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"/>";
            echo "<title>Database Error</title><script src=\"https://cdn.tailwindcss.com\"></script></head>";
            echo "<body class=\"bg-slate-50 text-slate-900\">";
            echo "<main class=\"mx-auto max-w-2xl px-4 py-10\">";
            echo "<div class=\"rounded-xl border bg-white p-6\">";
            echo "<h1 class=\"text-lg font-semibold\">Aplikasi belum bisa berjalan</h1>";
            echo "<p class=\"mt-1 text-sm text-slate-600\">Penyebab paling umum: database MySQL belum dibuat / belum di-import.</p>";
            echo "<div data-alert class=\"mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700\">";
            echo "<div class=\"flex items-start justify-between gap-3\">";
            echo "<div>Error: <span class=\"font-mono\">{$safeMsg}</span></div>";
            echo "<button type=\"button\" class=\"-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900\" aria-label=\"Tutup\" onclick=\"this.closest('\\\"[data-alert]\\\"').remove()\">&times;</button>";
            echo "</div>";
            echo "</div>";
            echo "<div class=\"mt-5 text-sm text-slate-700\">";
            echo "<div class=\"font-semibold\">Perbaiki dengan:</div>";
            echo "<ol class=\"mt-2 list-decimal pl-5 space-y-1\">";
            echo "<li>Buka phpMyAdmin: <span class=\"font-mono\">http://localhost/phpmyadmin</span></li>";
            echo "<li>Buat database: <span class=\"font-mono\">" . htmlspecialchars(DB_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</span></li>";
            echo "<li>Import file: <span class=\"font-mono\">/pengumuman/sql/schema.sql</span></li>";
            echo "</ol>";
            echo "</div>";
            echo "<div class=\"mt-5 text-xs text-slate-500\">Konfigurasi DB bisa dicek di <span class=\"font-mono\">inc/config.php</span>.</div>";
            echo "</div></main></body></html>";
            exit;
        }

        throw $e;
    }

    return $pdo;
}
