<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/view.php';

require_admin();

$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
  $page = 1;
}

$totalRows = (int)db()->query('SELECT COUNT(*) FROM announcements')->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
  "SELECT id, title, is_published, created_at
   FROM announcements
   ORDER BY created_at DESC
   LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$announcements = $stmt->fetchAll();

function pager_items(int $page, int $totalPages): array
{
  if ($totalPages <= 1) {
    return [1];
  }

  $items = [1];

  $start = max(2, $page - 1);
  $end = min($totalPages - 1, $page + 1);

  if ($start > 2) {
    $items[] = '…';
  }

  for ($p = $start; $p <= $end; $p++) {
    $items[] = $p;
  }

  if ($end < $totalPages - 1) {
    $items[] = '…';
  }

  $items[] = $totalPages;

  $unique = [];
  foreach ($items as $it) {
    if ($it === '…') {
      if (end($unique) !== '…') {
        $unique[] = '…';
      }
      continue;
    }

    if (!in_array($it, $unique, true)) {
      $unique[] = $it;
    }
  }
  return $unique;
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard Admin - <?= h(APP_NAME) ?></title>
  <?= tailwind_cdn_script() ?>
</head>
<body class="bg-slate-50 text-slate-900">
  <header class="border-b bg-white">
    <div class="mx-auto flex max-w-none flex-col items-start justify-between gap-4 px-4 py-5 sm:flex-row sm:items-center">
      <div>
        <div class="text-sm text-slate-600">Admin</div>
        <div class="text-lg font-semibold">Dashboard Pengumuman</div>
      </div>
      <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center sm:gap-3">
        <a class="inline-flex items-center justify-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800" href="<?= h(BASE_URL) ?>/admin/upload.php">Tambah Pengumuman</a>
        <a class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/settings.php">Setting WhatsApp</a>
        <a class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/logout.php">Logout</a>
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

    <div class="rounded-lg border bg-white">
      <div class="border-b p-4">
        <div class="text-sm font-semibold text-slate-700">Data Pengumuman</div>
      </div>

      <?php if (empty($announcements)): ?>
        <div class="p-4 text-sm text-slate-600">Belum ada data.</div>
      <?php else: ?>
        <div>
          <table class="w-full table-auto text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-600">
              <tr>
                <th class="px-4 py-3">Judul</th>
                <th class="px-4 py-3 whitespace-nowrap">Status</th>
                <th class="hidden px-4 py-3 whitespace-nowrap lg:table-cell">Tanggal</th>
                <th class="px-4 py-3 whitespace-nowrap">Link Share</th>
                <th class="px-4 py-3 text-right whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php foreach ($announcements as $a): ?>
                <tr>
                  <td class="px-4 py-3">
                    <div class="truncate font-medium text-slate-900" title="<?= h($a['title']) ?>"><?= h($a['title']) ?></div>
                    <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-500">
                      <span>ID: <?= (int)$a['id'] ?></span>
                      <span class="lg:hidden">Tanggal: <?= h(date('d M Y, H:i', strtotime($a['created_at']))) ?></span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <?php if ((int)$a['is_published'] === 1): ?>
                      <span class="inline-flex rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Published</span>
                    <?php else: ?>
                      <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">Draft</span>
                    <?php endif; ?>
                  </td>
                  <td class="hidden px-4 py-3 text-slate-700 lg:table-cell"><?= h(date('d M Y, H:i', strtotime($a['created_at']))) ?></td>
                  <td class="px-4 py-3">
                    <?php if ((int)$a['is_published'] === 1): ?>
                      <?php $shareUrl = announcement_url((int)$a['id']); ?>
                      <div class="flex flex-wrap items-center gap-2">
                        <a class="text-slate-700 underline hover:text-slate-900" href="<?= h(announcement_path((int)$a['id'])) ?>" target="_blank" rel="noopener">Buka Link</a>
                        <button
                          type="button"
                          class="rounded-md border px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                          data-copy-text="<?= h($shareUrl) ?>"
                          onclick="copyShareLink(this)"
                        >
                          Salin
                        </button>
                      </div>
                    <?php else: ?>
                      <div class="text-xs text-slate-500">Publish dulu untuk bisa dishare.</div>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex flex-col items-end justify-end gap-2 xl:flex-row xl:items-center">
                      <a class="rounded-md border px-3 py-2 text-xs font-semibold hover:bg-slate-50" href="<?= h(BASE_URL) ?>/admin/participants.php?announcement_id=<?= (int)$a['id'] ?>">Peserta</a>
                      <form method="post" action="<?= h(BASE_URL) ?>/admin/toggle_publish.php">
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                        <button class="rounded-md border px-3 py-2 text-xs font-semibold hover:bg-slate-50" type="submit">
                          <?= ((int)$a['is_published'] === 1) ? 'Unpublish' : 'Publish' ?>
                        </button>
                      </form>
                      <form method="post" action="<?= h(BASE_URL) ?>/admin/delete.php" onsubmit="return confirm('Hapus pengumuman ini?');">
                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
                        <button class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100" type="submit">Hapus</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <div class="border-t bg-white px-4 py-3">
            <nav class="flex items-center justify-end gap-1" aria-label="Pagination">
              <a
                class="rounded-md border px-3 py-1.5 text-xs font-semibold hover:bg-slate-50 <?= ($page <= 1) ? 'pointer-events-none opacity-50' : '' ?>"
                href="<?= h(BASE_URL) ?>/admin/dashboard.php?page=<?= max(1, $page - 1) ?>"
              >Prev</a>

              <?php foreach (pager_items($page, $totalPages) as $it): ?>
                <?php if ($it === '…'): ?>
                  <span class="px-2 text-xs font-semibold text-slate-400">…</span>
                <?php else: ?>
                  <?php $p = (int)$it; ?>
                  <a
                    class="rounded-md border px-3 py-1.5 text-xs font-semibold hover:bg-slate-50 <?= ($p === $page) ? 'bg-slate-900 text-white border-slate-900 hover:bg-slate-900' : '' ?>"
                    href="<?= h(BASE_URL) ?>/admin/dashboard.php?page=<?= (int)$p ?>"
                  ><?= (int)$p ?></a>
                <?php endif; ?>
              <?php endforeach; ?>

              <a
                class="rounded-md border px-3 py-1.5 text-xs font-semibold hover:bg-slate-50 <?= ($page >= $totalPages) ? 'pointer-events-none opacity-50' : '' ?>"
                href="<?= h(BASE_URL) ?>/admin/dashboard.php?page=<?= min($totalPages, $page + 1) ?>"
              >Next</a>
            </nav>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</body>
<script>
  function copyShareLink(buttonEl) {
    try {
      var text = buttonEl.getAttribute('data-copy-text') || '';
      if (!text) return;

      function markCopied() {
        var prev = buttonEl.textContent;
        buttonEl.textContent = 'Tersalin';
        buttonEl.disabled = true;
        setTimeout(function () {
          buttonEl.textContent = prev;
          buttonEl.disabled = false;
        }, 1000);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(markCopied).catch(function () {
          // Fallback
          var ta = document.createElement('textarea');
          ta.value = text;
          ta.setAttribute('readonly', '');
          ta.style.position = 'fixed';
          ta.style.left = '-9999px';
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
          markCopied();
        });
      } else {
        var ta2 = document.createElement('textarea');
        ta2.value = text;
        ta2.setAttribute('readonly', '');
        ta2.style.position = 'fixed';
        ta2.style.left = '-9999px';
        document.body.appendChild(ta2);
        ta2.select();
        document.execCommand('copy');
        document.body.removeChild(ta2);
        markCopied();
      }
    } catch (e) {
      // no-op
    }
  }
</script>
</html>
