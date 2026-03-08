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

$stmt = db()->prepare('SELECT is_published FROM announcements WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash_set('error', 'Data tidak ditemukan.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$newValue = ((int)$row['is_published'] === 1) ? 0 : 1;

db()->prepare('UPDATE announcements SET is_published = ? WHERE id = ?')->execute([$newValue, $id]);

flash_set('success', $newValue === 1 ? 'Berhasil dipublish.' : 'Berhasil di-unpublish.');
header('Location: ' . BASE_URL . '/admin/dashboard.php');
exit;
