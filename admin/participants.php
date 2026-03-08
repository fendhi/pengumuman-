<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/view.php';

require_admin();

$announcementId = isset($_GET['announcement_id'])
  ? (int)$_GET['announcement_id']
  : (isset($_POST['announcement_id']) ? (int)$_POST['announcement_id'] : 0);
if ($announcementId <= 0) {
    flash_set('error', 'ID pengumuman tidak valid.');
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

$error = null;

$q = trim((string)($_GET['q'] ?? ''));

$bulkDefault = '';

$bulkExample = "0008-03-ALL-08-2025, MUHAMMAD HANIF FATHURRAHMAN, 2025-08-13, 09:00, 12:00\n"
  . "0020-03-ALL-08-2025, FARID HERDIANSYAH PUTRA, 2025-08-13, 09:00, 12:00\n"
  . "0035-03-ALL-08-2025, DONI PERMANA, 2025-08-13, 09:00, 12:00\n"
  . "0037-03-ALL-08-2025, NELLA ENJELINA, 2025-08-13, 09:00, 12:00\n"
  . "0040-03-ALL-08-2025, SITI AMINAH, 2025-08-13, 13:00, 15:00";

function parse_participant_line(string $line): array
{
  $line = trim($line);
  if ($line === '') {
    return [];
  }

  // Accept comma-separated. Allow tab-separated (Excel) as well.
  if (str_contains($line, "\t")) {
    $parts = explode("\t", $line);
  } elseif (str_contains($line, ',')) {
    $parts = explode(',', $line);
  } else {
    $parts = [$line];
  }

  $parts = array_map(static fn($v) => trim((string)$v), $parts);
  $parts = array_values(array_filter($parts, static fn($v) => $v !== ''));

  // Expected fields:
  // 0 applicant_id, 1 participant_name, 2 schedule_date (YYYY-MM-DD), 3 start_time (HH:MM), 4 end_time (HH:MM)
  if (count($parts) === 4 && str_contains($parts[3], '-')) {
    // Allow time range in last column: 09:00-12:00
    [$st, $et] = array_map('trim', explode('-', $parts[3], 2));
    return [$parts[0], $parts[1], $parts[2], $st, $et];
  }

  if (count($parts) !== 5) {
    return ['__error__' => 'Format baris peserta harus 5 kolom: ID, NAMA, YYYY-MM-DD, HH:MM, HH:MM'];
  }

  return [$parts[0], $parts[1], $parts[2], $parts[3], $parts[4]];
}

$bulkText = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $bulkText = trim((string)($_POST['bulk_text'] ?? ''));

  if ($bulkText === '') {
    $error = 'Teks peserta masih kosong.';
  } else {
    try {
      $lines = preg_split('/\R/u', $bulkText) ?: [];
      $rows = [];
      foreach ($lines as $line) {
        $parsed = parse_participant_line((string)$line);
        if ($parsed === []) {
          continue;
        }
        if (isset($parsed['__error__'])) {
          throw new RuntimeException((string)$parsed['__error__']);
        }
        $rows[] = $parsed;
      }

      if (count($rows) > 50) {
        throw new RuntimeException('Maksimal 50 peserta sekali input.');
      }
      if (count($rows) === 0) {
        throw new RuntimeException('Tidak ada baris valid untuk disimpan.');
      }

      $db = db();
      $db->beginTransaction();

      $ins = $db->prepare(
        'INSERT INTO participants (announcement_id, applicant_id, participant_name, schedule_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)'
      );

      $insertedCount = 0;
      $i = 0;
      foreach ($rows as $r) {
        $i++;
        [$applicantId, $participantName, $scheduleDate, $startTime, $endTime] = $r;

        if ($applicantId === '' || $participantName === '' || $scheduleDate === '' || $startTime === '' || $endTime === '') {
          throw new RuntimeException('Baris #' . $i . ': ada kolom kosong.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduleDate)) {
          throw new RuntimeException('Baris #' . $i . ': format tanggal harus YYYY-MM-DD.');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
          throw new RuntimeException('Baris #' . $i . ': format waktu harus HH:MM.');
        }

        $ins->execute([
          $announcementId,
          $applicantId,
          $participantName,
          $scheduleDate,
          $startTime . ':00',
          $endTime . ':00',
        ]);
        $insertedCount++;
      }

      $db->commit();

      flash_set('success', 'Berhasil menambahkan ' . $insertedCount . ' peserta.');
      header('Location: ' . BASE_URL . '/admin/participants.php?announcement_id=' . $announcementId);
      exit;
    } catch (Throwable $e) {
      $db = db();
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      $error = $e->getMessage();
    }
  }
}

$bulkValue = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $bulkText : $bulkDefault;

$sql =
    'SELECT id, applicant_id, participant_name, schedule_date, start_time, end_time, created_at
     FROM participants
     WHERE announcement_id = ?';
$params = [$announcementId];

if ($q !== '') {
  $sql .= ' AND (applicant_id LIKE ? OR participant_name LIKE ?)';
  $like = '%' . $q . '%';
  $params[] = $like;
  $params[] = $like;
}

$sql .= ' ORDER BY schedule_date ASC, start_time ASC, applicant_id ASC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$participants = $stmt->fetchAll();

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Peserta - <?= h((string)$announcement['title']) ?> - <?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-none items-center justify-between gap-4 px-4 py-5">
      <div class="min-w-0">
        <div class="text-sm text-slate-600">Admin</div>
        <div class="truncate text-lg font-semibold">Kelola Peserta</div>
        <div class="mt-0.5 truncate text-xs text-slate-500" title="<?= h((string)$announcement['title']) ?>">Pengumuman: <?= h((string)$announcement['title']) ?></div>
      </div>
      <div class="flex items-center gap-3">
        <a class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/dashboard.php">Kembali</a>
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

      <div class="text-sm font-semibold text-slate-700">Tambah Peserta (Bulk, max 50)</div>
      <div class="mt-1 text-xs text-slate-500">Format per baris: ID Pelamar, Nama, YYYY-MM-DD, HH:MM, HH:MM (bisa juga dipaste dari Excel: kolom dipisah TAB).</div>
      <div class="mt-2 text-xs text-slate-500">Contoh penulisan:</div>
      <pre class="mt-1 overflow-x-auto rounded-md border bg-slate-50 p-3 text-xs text-slate-700"><code><?= h($bulkExample) ?></code></pre>

      <textarea
        name="bulk_text"
        rows="8"
        class="mt-3 w-full rounded-md border px-3 py-2 text-sm font-mono"
      ><?= h((string)$bulkValue) ?></textarea>

      <div class="mt-4 flex items-center gap-2">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" type="submit">Tambah Peserta</button>
      </div>
    </form>

    <div class="mt-6 rounded-lg border bg-white">
      <div class="border-b p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-sm font-semibold text-slate-700">Daftar Peserta</div>

          <form class="flex w-full items-center gap-2 sm:w-auto" method="get" action="">
            <input type="hidden" name="announcement_id" value="<?= (int)$announcementId ?>" />
            <input
              name="q"
              value="<?= h($q) ?>"
              class="w-full rounded-md border px-3 py-2 text-sm sm:w-72"
              placeholder="Cari ID pelamar / nama..."
              autocomplete="off"
            />
            <button class="rounded-md border bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" type="submit">Cari</button>
            <?php if ($q !== ''): ?>
              <a class="rounded-md border px-3 py-2 text-sm font-semibold hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/participants.php?announcement_id=<?= (int)$announcementId ?>">Reset</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <?php if (empty($participants)): ?>
        <div class="p-4 text-sm text-slate-600">Belum ada data peserta.</div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="min-w-[920px] w-full table-auto text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
              <tr>
                <th class="px-4 py-3">ID Pelamar</th>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Hari / Tanggal</th>
                <th class="px-4 py-3 whitespace-nowrap">Waktu (WIB)</th>
                <th class="px-4 py-3 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php foreach ($participants as $p): ?>
                <tr>
                  <td class="px-4 py-3 font-medium text-slate-900 break-all"><?= h((string)$p['applicant_id']) ?></td>
                  <td class="px-4 py-3 text-slate-900">
                    <div class="font-medium"><?= h((string)$p['participant_name']) ?></div>
                  </td>
                  <td class="px-4 py-3 text-slate-700"><?= h(format_date_id((string)$p['schedule_date'])) ?></td>
                  <td class="px-4 py-3 text-slate-700 whitespace-nowrap"><?= h(format_time_range((string)$p['start_time'], (string)$p['end_time'])) ?></td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex flex-col items-end justify-end gap-2 sm:flex-row sm:items-center whitespace-nowrap">
                      <a class="rounded-md border px-3 py-2 text-xs font-semibold hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/participant_edit.php?announcement_id=<?= (int)$announcementId ?>&id=<?= (int)$p['id'] ?>">Edit</a>
                      <form method="post" action="<?= h(BASE_URL) ?>/admin/participant_delete.php" onsubmit="return confirm('Hapus peserta ini?');">
                        <input type="hidden" name="announcement_id" value="<?= (int)$announcementId ?>" />
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                        <button class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
