<?php
// Basic config for the Pengumuman app.
// Adjust DB_* values for your local MySQL in XAMPP.

declare(strict_types=1);

// === Database ===
// NOTE: On macOS it's common to have multiple MySQL instances.
// XAMPP MariaDB often runs on port 3308 and provides a unix socket.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3308));
define('DB_NAME', getenv('DB_NAME') ?: 'pengumuman');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// === App ===
define('APP_NAME', 'Pengumuman Penerimaan Karyawan - Primaya Hospital');

// Base URL (path only). Examples: '' (root) or '/pengumuman' (subfolder).
// You can override with env var APP_BASE_URL.
$__baseUrl = getenv('APP_BASE_URL');
if ($__baseUrl === false) {
    $docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRootFs = (string)(realpath(dirname(__DIR__)) ?: dirname(__DIR__));

    $docRootFs = $docRoot !== '' ? (string)(realpath($docRoot) ?: $docRoot) : '';
    $docRootFs = rtrim(str_replace('\\', '/', $docRootFs), '/');
    $projectRootFs = rtrim(str_replace('\\', '/', $projectRootFs), '/');

    if ($docRootFs !== '' && str_starts_with($projectRootFs, $docRootFs)) {
        $rel = substr($projectRootFs, strlen($docRootFs));
        $rel = '/' . ltrim(str_replace('\\', '/', $rel), '/');
        $rel = rtrim($rel, '/');
        $__baseUrl = ($rel === '/') ? '' : $rel;
    } else {
        // Fallback: best-effort based on current script directory.
        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName === '' || $scriptName === '.') {
            $__baseUrl = '';
        } else {
            $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            $__baseUrl = ($dir === '/' ? '' : $dir);
        }
    }
}
define('BASE_URL', $__baseUrl);

// Legacy (fitur PDF sudah dihapus). Tetap dibiarkan agar tidak memecahkan konfigurasi lama.
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('MAX_UPLOAD_BYTES', 15 * 1024 * 1024); // 15MB

// Debug
// If you need to open debug.php on a hosting, set this to a random string
// then open: /debug.php?key=YOUR_KEY
define('DEBUG_KEY', getenv('DEBUG_KEY') ?: '');

// Session
ini_set('session.cookie_httponly', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}
ini_set('session.use_strict_mode', '1');
