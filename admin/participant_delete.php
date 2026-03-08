<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$announcementId = isset($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0;
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($announcementId <= 0 || $id <= 0) {
    flash_set('error', 'Data tidak valid.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('DELETE FROM participants WHERE id = ? AND announcement_id = ?');
$stmt->execute([$id, $announcementId]);

flash_set('success', 'Peserta berhasil dihapus.');
header('Location: ' . BASE_URL . '/admin/participants.php?announcement_id=' . $announcementId);
exit;
