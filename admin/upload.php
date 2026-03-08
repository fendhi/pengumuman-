<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/view.php';

require_admin();

$error = null;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? ''));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
  $bulkText = trim((string)($_POST['bulk_text'] ?? ''));

    $pdfFile = $_FILES['pdf'] ?? null;

    if ($title === '') {
        $error = 'Judul wajib diisi.';
    } else {
      try {
      $db = db();
      $db->beginTransaction();

      $db->prepare(
        'INSERT INTO announcements (title, is_published) VALUES (?, ?)'
      )->execute([
        $title,
        (int)$isPublished,
      ]);

      $announcementId = (int)$db->lastInsertId();

      $storedPdfName = null;
      $storedPdfPath = null;
      if (is_array($pdfFile) && isset($pdfFile['error']) && (int)$pdfFile['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadErr = (int)$pdfFile['error'];
        if ($uploadErr !== UPLOAD_ERR_OK) {
          throw new RuntimeException('Gagal upload PDF (error code: ' . $uploadErr . ').');
        }

        $tmpName = (string)($pdfFile['tmp_name'] ?? '');
        $origName = trim((string)($pdfFile['name'] ?? ''));
        $sizeBytes = (int)($pdfFile['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
          throw new RuntimeException('File upload PDF tidak valid.');
        }
        if ($sizeBytes <= 0) {
          throw new RuntimeException('File PDF kosong.');
        }
        if ($sizeBytes > MAX_UPLOAD_BYTES) {
          throw new RuntimeException('Ukuran PDF maksimal ' . (int)(MAX_UPLOAD_BYTES / (1024 * 1024)) . 'MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if ($mime !== 'application/pdf') {
          throw new RuntimeException('File harus PDF (application/pdf).');
        }

        if (!is_dir(UPLOAD_DIR)) {
          if (!mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
            throw new RuntimeException('Folder uploads tidak bisa dibuat: ' . UPLOAD_DIR);
          }
        }

        $storedPdfName = 'ann_' . $announcementId . '_' . bin2hex(random_bytes(16)) . '.pdf';
        $storedPdfPath = rtrim(UPLOAD_DIR, '/\\') . '/' . $storedPdfName;

        if (!move_uploaded_file($tmpName, $storedPdfPath)) {
          throw new RuntimeException('Gagal menyimpan file PDF ke server.');
        }

        // Save metadata to DB
        $db->prepare(
          'UPDATE announcements SET pdf_original_name = ?, pdf_stored_name = ?, pdf_size_bytes = ? WHERE id = ?'
        )->execute([
          $origName !== '' ? $origName : null,
          $storedPdfName,
          $sizeBytes,
          $announcementId,
        ]);
      }

      $insertedCount = 0;
      if ($bulkText !== '') {
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

        if (count($rows) > 0) {
          $ins = $db->prepare(
            'INSERT INTO participants (announcement_id, applicant_id, participant_name, schedule_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)'
          );

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
        }
      }

      $db->commit();

      flash_set('success', 'Pengumuman berhasil ditambahkan' . ($insertedCount > 0 ? (' + ' . $insertedCount . ' peserta') : '') . '.');
      header('Location: ' . BASE_URL . '/admin/participants.php?announcement_id=' . $announcementId);
      exit;
      } catch (PDOException $e) {
      $db = db();
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      // Best-effort cleanup if file already moved.
      if (!empty($storedPdfPath) && is_string($storedPdfPath) && is_file($storedPdfPath)) {
        @unlink($storedPdfPath);
      }
        $msg = $e->getMessage();
        if (stripos($msg, 'Unknown column') !== false && stripos($msg, 'pdf_') !== false) {
          $error = 'Database kamu belum punya kolom PDF. Jalankan migrasi: /pengumuman/sql/migrate_add_pdf.sql.';
        } else {
          $error = 'Gagal menyimpan pengumuman: ' . $msg;
        }
    } catch (Throwable $e) {
      $db = db();
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      // Best-effort cleanup if file already moved.
      if (!empty($storedPdfPath) && is_string($storedPdfPath) && is_file($storedPdfPath)) {
        @unlink($storedPdfPath);
      }
      $error = $e->getMessage();
      }
    }
}

$bulkValue = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($bulkText ?? '') : $bulkDefault;

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tambah Pengumuman - <?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-5">
      <div>
        <div class="text-sm text-slate-600">Admin</div>
        <div class="text-lg font-semibold">Tambah Pengumuman</div>
      </div>
      <div class="flex items-center gap-3">
        <a class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/dashboard.php">Kembali</a>
        <a class="text-sm font-medium text-slate-700 hover:text-slate-900" href="<?= h(BASE_URL) ?>/admin/logout.php">Logout</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-5xl px-4 py-6">
    <?php if ($error): ?>
      <div data-alert class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <div class="flex items-start justify-between gap-3">
          <div><?= h($error) ?></div>
          <button type="button" class="-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900" aria-label="Tutup" onclick="this.closest('[data-alert]').remove()">&times;</button>
        </div>
      </div>
    <?php endif; ?>

    <form class="rounded-lg border bg-white p-5" method="post" action="" enctype="multipart/form-data">
      <div>
        <label class="block text-sm font-medium text-slate-700">Judul Pengumuman</label>
        <input name="title" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" placeholder="Contoh: Pengumuman Penerimaan Karyawan - Batch Januari 2026" required />
      </div>

      <div class="mt-4 flex items-center gap-2">
        <input id="is_published" type="checkbox" name="is_published" class="h-4 w-4" checked />
        <label for="is_published" class="text-sm text-slate-700">Publish langsung</label>
      </div>

      <div class="mt-5">
        <label class="block text-sm font-medium text-slate-700">Upload PDF Pengumuman (opsional)</label>
        <div class="mt-1 text-xs text-slate-500">Hanya PDF, maksimal <?= (int)(MAX_UPLOAD_BYTES / (1024 * 1024)) ?>MB.</div>
        <input name="pdf" type="file" accept="application/pdf" class="mt-2 block w-full text-sm" />
      </div>

      <div class="mt-5">
        <label class="block text-sm font-medium text-slate-700">Tambah Peserta (Bulk, max 50)</label>
        <div class="mt-1 text-xs text-slate-500">Format per baris: ID Pelamar, Nama, YYYY-MM-DD, HH:MM, HH:MM (bisa juga dipaste dari Excel: kolom dipisah TAB).</div>
        <div class="mt-2 text-xs text-slate-500">Contoh penulisan:</div>
        <pre class="mt-1 overflow-x-auto rounded-md border bg-slate-50 p-3 text-xs text-slate-700"><code><?= h($bulkExample) ?></code></pre>
        <textarea
          name="bulk_text"
          rows="10"
          class="mt-2 w-full rounded-md border px-3 py-2 text-sm font-mono"
        ><?= h((string)$bulkValue) ?></textarea>
      </div>

      <div class="mt-5">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" type="submit">Simpan</button>
      </div>
    </form>
  </main>
</body>
</html>
