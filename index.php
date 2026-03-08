<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/view.php';
require_once __DIR__ . '/inc/settings.php';

$q = trim((string)($_GET['q'] ?? ''));

$stmt = db()->query(
  "SELECT id, title, created_at
     FROM announcements
     WHERE is_published = 1
     ORDER BY created_at DESC
     LIMIT 1"
);
$announcement = $stmt->fetch();

$participants = [];
$totalParticipants = 0;
if ($announcement) {
  $stmt = db()->prepare('SELECT COUNT(*) FROM participants WHERE announcement_id = ?');
  $stmt->execute([(int)$announcement['id']]);
  $totalParticipants = (int)$stmt->fetchColumn();

  $sql =
    'SELECT applicant_id, participant_name, schedule_date, start_time, end_time
     FROM participants
     WHERE announcement_id = ?';
  $params = [(int)$announcement['id']];

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
}

$shownParticipants = count($participants);

$waUrl = wa_url('Halo Admin, saya ingin konfirmasi kehadiran interview.');

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
  <header class="sticky top-0 z-10 border-b bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-none items-center justify-between gap-4 px-4 py-4">
      <div class="min-w-0">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500"></div>
        <h1 class="truncate text-base font-semibold leading-tight sm:text-lg">Pengumuman Penerimaan Karyawan</h1>
      </div>
    
    </div>
  </header>

  <main class="mx-auto max-w-none px-4 py-8">
    <?php if (!$announcement): ?>
      <section class="rounded-xl border bg-white p-6">
        <div class="text-sm font-semibold text-slate-700">Belum ada pengumuman</div>
        <p class="mt-1 text-sm text-slate-600">Silakan kembali lagi nanti.</p>
      </section>
    <?php else: ?>
      <section class="rounded-2xl border bg-white p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Pengumuman</div>
            <h2 class="mt-3 text-xl font-semibold leading-tight sm:text-2xl"><?= h($announcement['title']) ?></h2>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
          <div class="lg:col-span-4">
            <div class="rounded-xl border bg-slate-50 p-5">
              <h3 class="text-base font-semibold">PENGUMUMAN TES INTERVIEW</h3>
              <p class="mt-2 text-sm leading-relaxed text-slate-700">
                Berdasarkan hasil seleksi administrasi, bersama ini kami sampaikan bahwa nama-nama berikut dinyatakan lolos tahap seleksi awal dan berhak mengikuti tes interview.
              </p>
              <?php if ($waUrl === ''): ?>
                <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">
                  Nomor WhatsApp admin belum diset.
                </div>
              <?php else: ?>
                <a class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100" href="<?= h($waUrl) ?>" target="_blank" rel="noopener">Konfirmasi via WhatsApp</a>
              <?php endif; ?>
              <div class="mt-4 text-xs text-slate-500">Tautan publik: pengumuman ini bisa dibagikan dari admin dashboard.</div>
            </div>
          </div>

          <div class="lg:col-span-8">
            <div class="overflow-hidden rounded-xl border bg-white">
              <div class="border-b bg-slate-50 px-4 py-3">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                  <div class="min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-600">Daftar Peserta</div>
                    <?php if ($announcement): ?>
                      <div class="mt-0.5 text-xs text-slate-500">
                        Menampilkan <?= (int)$shownParticipants ?> dari <?= (int)$totalParticipants ?> peserta<?= $q !== '' ? ' (hasil pencarian)' : '' ?>.
                      </div>
                    <?php endif; ?>
                  </div>

                  <form class="flex w-full items-center gap-2 sm:w-auto" method="get" action="">
                    <input
                      name="q"
                      value="<?= h($q) ?>"
                      class="w-full rounded-md border bg-white px-3 py-2 text-sm sm:w-72"
                      placeholder="Cari ID pelamar / nama..."
                      autocomplete="off"
                    />
                    <button class="rounded-md border bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" type="submit">Cari</button>
                    <?php if ($q !== ''): ?>
                      <a class="rounded-md border bg-white px-3 py-2 text-sm font-semibold hover:bg-slate-50" href="<?= h(BASE_URL) ?>/index.php">Reset</a>
                    <?php endif; ?>
                  </form>
                </div>
              </div>
              <?php if (empty($participants)): ?>
                <div class="p-4 text-sm text-slate-600">Belum ada data peserta.</div>
              <?php else: ?>
                <div class="overflow-x-auto">
                  <table class="min-w-[760px] w-full table-auto text-left text-sm">
                    <thead class="bg-white text-xs font-semibold text-slate-600">
                      <tr class="border-b">
                        <th class="px-3 py-3 sm:px-4">ID PELAMAR</th>
                        <th class="px-3 py-3 sm:px-4">NAMA</th>
                        <th class="px-3 py-3 sm:px-4 whitespace-nowrap">HARI / TANGGAL</th>
                        <th class="px-3 py-3 sm:px-4 whitespace-nowrap">WAKTU (WIB)</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y">
                      <?php foreach ($participants as $p): ?>
                        <tr>
                          <td class="px-3 py-3 font-medium text-slate-900 break-all sm:px-4"><?= h((string)$p['applicant_id']) ?></td>
                          <td class="px-3 py-3 text-slate-900 sm:px-4"><?= h((string)$p['participant_name']) ?></td>
                          <td class="px-3 py-3 text-slate-700 sm:px-4 whitespace-nowrap"><?= h(format_date_id((string)$p['schedule_date'])) ?></td>
                          <td class="px-3 py-3 text-slate-700 sm:px-4 whitespace-nowrap"><?= h(format_time_range((string)$p['start_time'], (string)$p['end_time'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <footer class="mx-auto mt-8 text-center text-xs text-slate-500">
      &copy; <?= h(date('Y')) ?> Hasil Seleksi
    </footer>
  </main>
</body>
</html>
