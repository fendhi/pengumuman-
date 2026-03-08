<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/view.php';
require_once __DIR__ . '/inc/settings.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$stmt = db()->prepare(
  "SELECT id, title, created_at, pdf_original_name, pdf_stored_name
     FROM announcements
     WHERE is_published = 1 AND id = ?
     LIMIT 1"
);
$stmt->execute([$id]);
$announcement = $stmt->fetch();

if (!$announcement) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$hasPdf = !empty($announcement['pdf_stored_name']);

$waUrl = wa_url('Halo Admin, saya ingin konfirmasi pembayaran agar mendapatkan ID/Code Zoom.');

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h((string)$announcement['title']) ?> - Dokumen</title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
  <header class="sticky top-0 z-10 border-b bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-none items-center justify-between gap-4 px-4 py-4">
      <div class="min-w-0">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Primaya Hospital</div>
        <h1 class="truncate text-base font-semibold leading-tight sm:text-lg">Dokumen Pengumuman</h1>
      </div>
      <div class="flex items-center gap-2">
        <a class="rounded-md border bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" href="<?= h(BASE_URL) ?>/announcement.php?id=<?= (int)$announcement['id'] ?>">Kembali</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-none px-4 py-6">
    <section class="rounded-2xl border bg-white p-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Pengumuman</div>
          <h2 class="mt-3 text-xl font-semibold leading-tight sm:text-2xl"><?= h((string)$announcement['title']) ?></h2>
          <?php if (!empty($announcement['pdf_original_name'])): ?>
            <div class="mt-1 text-xs text-slate-500">File: <?= h((string)$announcement['pdf_original_name']) ?></div>
          <?php endif; ?>
        </div>
        <div class="mt-4 flex w-full flex-col gap-2 sm:mt-0 sm:w-auto">
          <div class="rounded-md border bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">
            SILAHKAN KONTAK WHATSAPP ADMIN UNTUK MELAKUKAN PEMBAYARAN AGAR MENDAPATKAN ID CODE ZOOM
          </div>
          <?php if ($waUrl === ''): ?>
            <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
              Nomor WhatsApp admin belum diset.
            </div>
          <?php else: ?>
            <a class="inline-flex w-full items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">Hubungi via WhatsApp</a>
          <?php endif; ?>
          <?php if ($hasPdf): ?>
            <a class="inline-flex w-full items-center justify-center rounded-md border bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= h(BASE_URL) ?>/document_file.php?id=<?= (int)$announcement['id'] ?>" target="_blank" rel="noopener">Buka PDF di Tab Baru</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-6">
        <?php if (!$hasPdf): ?>
          <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Dokumen PDF belum diupload oleh admin.
          </div>
        <?php else: ?>
          <div class="overflow-hidden rounded-xl border bg-slate-50">
            <iframe
              title="Dokumen PDF"
              src="<?= h(BASE_URL) ?>/document_file.php?id=<?= (int)$announcement['id'] ?>"
              class="h-[75vh] w-full"
            ></iframe>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <footer class="mx-auto mt-8 text-center text-xs text-slate-500">
      &copy; <?= h(date('Y')) ?> Hasil Seleksi
    </footer>
  </main>
</body>
</html>
