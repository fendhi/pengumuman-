<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/view.php';

ensure_session_started();

if (is_admin_logged_in()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (admin_login($username, $password)) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
        exit;
    } else {
        $error = 'Login gagal. Cek username/password.';
    }
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login - <?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="bg-slate-50 text-slate-900">
  <main class="mx-auto flex min-h-screen max-w-md items-center px-4">
    <div class="w-full rounded-xl border bg-white p-6">
      <h1 class="text-lg font-semibold">Admin Login</h1>
      <p class="mt-1 text-sm text-slate-600">Kelola pengumuman (upload PDF).</p>

      <?php if ($error): ?>
        <div data-alert class="mt-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
          <div class="flex items-start justify-between gap-3">
            <div><?= h($error) ?></div>
            <button type="button" class="-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900" aria-label="Tutup" onclick="this.closest('[data-alert]').remove()">&times;</button>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($msg = flash_get('success')): ?>
        <div data-alert class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
          <div class="flex items-start justify-between gap-3">
            <div><?= h($msg) ?></div>
            <button type="button" class="-mr-1 -mt-1 rounded px-2 py-1 text-slate-600 hover:text-slate-900" aria-label="Tutup" onclick="this.closest('[data-alert]').remove()">&times;</button>
          </div>
        </div>
      <?php endif; ?>

      <form class="mt-5 space-y-4" method="post" action="">
        <div>
          <label class="block text-sm font-medium text-slate-700">Username</label>
          <input name="username" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" autocomplete="username" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Password</label>
          <input type="password" name="password" class="mt-1 w-full rounded-md border px-3 py-2 text-sm" autocomplete="current-password" />
        </div>
        <button class="w-full rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" type="submit">Masuk</button>
      </form>

      <div class="mt-4 text-xs text-slate-500">
        <a class="text-slate-700 hover:text-slate-900" href="<?= h(BASE_URL) ?>/index.php">Kembali ke landing page</a>
      </div>
    </div>
  </main>
</body>
</html>
