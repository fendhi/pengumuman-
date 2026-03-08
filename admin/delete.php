<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash_set('error', 'ID tidak valid.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('SELECT id, pdf_stored_name FROM announcements WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash_set('error', 'Data tidak ditemukan.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// Delete row first
$db = db();
$db->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);

// Best-effort cleanup file
$stored = (string)($row['pdf_stored_name'] ?? '');
if ($stored !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $stored)) {
    $path = rtrim(UPLOAD_DIR, '/\\') . '/' . $stored;
    if (is_file($path)) {
        @unlink($path);
    }
}

flash_set('success', 'Pengumuman berhasil dihapus.');
header('Location: ' . BASE_URL . '/admin/dashboard.php');
exit;
