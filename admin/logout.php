<?php

declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';

admin_logout();
flash_set('success', 'Anda berhasil logout.');
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
