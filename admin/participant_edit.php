<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/view.php';

require_admin();

$announcementId = isset($_GET['announcement_id']) ? (int)$_GET['announcement_id'] : (isset($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0);
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($announcementId <= 0 || $id <= 0) {
    flash_set('error', 'Data tidak valid.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('SELECT id, title FROM announcements WHERE id = ? LIMIT 1');
$stmt->execute([$announcementId]);
$announcement = $stmt->fetch();

if (!$announcement) {
    flash_set('error', 'Pengumuman tidak ditemukan.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$stmt = db()->prepare('SELECT id, announcement_id, applicant_id, participant_name, schedule_date, start_time, end_time FROM participants WHERE id = ? AND announcement_id = ? LIMIT 1');
$stmt->execute([$id, $announcementId]);
$participant = $stmt->fetch();

if (!$participant) {
    flash_set('error', 'Peserta tidak ditemukan.');
    header('Location: ' . BASE_URL . '/admin/participants.php?announcement_id=' . $announcementId);
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicantId = trim((string)($_POST['applicant_id'] ?? ''));
    $participantName = trim((string)($_POST['participant_name'] ?? ''));
    $scheduleDate = trim((string)($_POST['schedule_date'] ?? ''));
    $startTime = trim((string)($_POST['start_time'] ?? ''));
    $endTime = trim((string)($_POST['end_time'] ?? ''));

    if ($applicantId === '' || $participantName === '' || $scheduleDate === '' || $startTime === '' || $endTime === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduleDate)) {
        $error = 'Format tanggal harus YYYY-MM-DD.';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        $error = 'Format waktu harus HH:MM.';
    } else {
        db()->prepare(
            'UPDATE participants SET applicant_id = ?, participant_name = ?, schedule_date = ?, start_time = ?, end_time = ? WHERE id = ? AND announcement_id = ?'
        )->execute([
            $applicantId,
            $participantName,
            $scheduleDate,
            $startTime . ':00',
            $endTime . ':00',
            $id,
            $announcementId,
        ]);

        flash_set('success', 'Peserta berhasil diupdate.');
        header('Location: ' . BASE_URL . '/admin/participants.php?announcement_id=' . $announcementId);
        exit;
    }
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Peserta - <?= h((string)$announcement['title']) ?> - <?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-none items-center justify-between gap-4 px-4 py-5">
      <div class="min-w-0">
        <div class="text-sm text-slate-600">Admin</div>
        <div class="truncate text-lg font-semibold">Edit Peserta</div>
        <div class="mt-0.5 truncate text-xs text-slate-500" title="<?= h((string)$announcement['title']) ?>">Pengumuman: <?= h((string)$announcement['title']) ?></div>
      </div>
      <div class="flex items-center gap-3">
        <a class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/participants.php?announcement_id=<?= (int)$announcementId ?>">Kembali</a>
        <a class="text-sm font-medium text-slate-700 hover:text-slate-900" href="<?= h(BASE_URL) ?>/admin/logout.php">Logout</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-none px-4 py-6">
    <?php if ($msg = flash_get('success')): ?>
      <div data-alert class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
        <div class="flex items-start justify-between gap-3">
          <div><?= h($msg) ?></div>
          <button type="button" class="-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900" aria-label="Tutup" onclick="this.closest('[data-alert]').remove()">&times;</button>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
      <div data-alert class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <div class="flex items-start justify-between gap-3">
          <div><?= h($msg) ?></div>
          <button type="button" class="-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900" aria-label="Tutup" onclick="this.closest('[data-alert]').remove()">&times;</button>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div data-alert class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <div class="flex items-start justify-between gap-3">
          <div><?= h($error) ?></div>
          <button type="button" class="-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900" aria-label="Tutup" onclick="this.closest('[data-alert]').remove()">&times;</button>
        </div>
      </div>
    <?php endif; ?>

    <form class="rounded-lg border bg-white p-5" method="post" action="">
      <input type="hidden" name="announcement_id" value="<?= (int)$announcementId ?>" />
      <input type="hidden" name="id" value="<?= (int)$id ?>" />

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-slate-700">ID Pelamar</label>
          <input name="applicant_id" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" value="<?= h((string)$participant['applicant_id']) ?>" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Nama</label>
          <input name="participant_name" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" value="<?= h((string)$participant['participant_name']) ?>" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Tanggal</label>
          <input type="date" name="schedule_date" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" value="<?= h((string)$participant['schedule_date']) ?>" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Waktu (WIB)</label>
          <div class="mt-1 grid grid-cols-2 gap-2">
            <input type="time" name="start_time" class="w-full rounded-md border px-3 py-2 text-sm" value="<?= h(substr((string)$participant['start_time'], 0, 5)) ?>" required />
            <input type="time" name="end_time" class="w-full rounded-md border px-3 py-2 text-sm" value="<?= h(substr((string)$participant['end_time'], 0, 5)) ?>" required />
          </div>
        </div>
      </div>

      <div class="mt-5 flex items-center gap-2">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" type="submit">Simpan Perubahan</button>
        <a class="rounded-md border px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/participants.php?announcement_id=<?= (int)$announcementId ?>">Batal</a>
      </div>
    </form>
  </main>
</body>
</html>
