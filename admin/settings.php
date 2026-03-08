<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/view.php';
require_once __DIR__ . '/../inc/settings.php';

require_admin();

$error = null;

$current = get_setting('wa_phone', wa_phone()) ?? wa_phone();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = trim((string)($_POST['wa_phone'] ?? ''));
  $norm = normalize_wa_phone($input);

  if ($input === '') {
    $error = 'Nomor WhatsApp wajib diisi.';
  } elseif ($norm === '') {
    $error = 'Nomor WhatsApp tidak valid.';
  } else {
    set_setting('wa_phone', $norm);
    flash_set('success', 'Nomor WhatsApp berhasil diupdate.');
    header('Location: ' . BASE_URL . '/admin/settings.php');
    exit;
  }

  $current = $input;
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Setting - <?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-5">
      <div>
        <div class="text-sm text-slate-600">Admin</div>
        <div class="text-lg font-semibold">Setting</div>
      </div>
      <div class="flex items-center gap-3">
        <a class="rounded-md border px-3 py-2 text-sm font-medium hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/dashboard.php">Kembali</a>
        <a class="text-sm font-medium text-slate-700 hover:text-slate-900" href="<?= h(BASE_URL) ?>/admin/logout.php">Logout</a>
      </div>
    </div>
  </header>

  <main class="mx-auto max-w-5xl px-4 py-6">
    <?php if ($msg = flash_get('success')): ?>
      <div data-alert class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
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
      <div>
        <label class="block text-sm font-medium text-slate-700">Nomor WhatsApp Admin</label>
        <div class="mt-1 text-xs text-slate-500">Boleh isi format: <span class="font-mono">08xx</span>, <span class="font-mono">+62xx</span>, atau <span class="font-mono">62xx</span>.</div>
        <input name="wa_phone" value="<?= h((string)$current) ?>" class="mt-2 w-full rounded-md border px-3 py-2 text-sm" placeholder="Contoh: 08123456789" required />
      </div>

      <div class="mt-5">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" type="submit">Simpan</button>
      </div>
    </form>

    <div class="mt-4 text-xs text-slate-500">
      Saat ini: <span class="font-mono"><?= h(wa_phone() !== '' ? ('https://wa.me/' . wa_phone()) : '-') ?></span>
    </div>
  </main>
</body>
</html>
