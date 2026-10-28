<?php
/**
 * Emergency Cache Cleaner - VELA DMS
 * Hapus file ini setelah digunakan!
 * Akses via: http://localhost/vela-dms-laravel/public/clear-cache.php
 */

$viewCacheDir = __DIR__ . '/../storage/framework/views';
$deleted = 0;
$errors  = [];

if (is_dir($viewCacheDir)) {
    foreach (glob($viewCacheDir . '/*.php') as $file) {
        if (@unlink($file)) {
            $deleted++;
        } else {
            $errors[] = basename($file);
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Clear Cache - VELA DMS</title>
<style>
  body { font-family: monospace; background: #0B1120; color: #e2e8f0; padding: 40px; }
  .ok  { color: #34d399; }
  .err { color: #f87171; }
  h1   { color: #38bdf8; }
  pre  { background: #020617; padding: 16px; border-radius: 8px; border: 1px solid #1e293b; }
</style>
</head>
<body>
<h1>🗑️ VELA DMS — View Cache Cleaner</h1>
<pre>
<?php if (empty($errors)): ?>
<span class="ok">✅ Berhasil menghapus <?= $deleted ?> file compiled view.</span>
<span class="ok">   Laravel akan re-compile semua view pada request berikutnya.</span>
<?php else: ?>
<span class="ok">✅ Berhasil dihapus: <?= $deleted ?> file</span>
<span class="err">❌ Gagal dihapus   : <?= count($errors) ?> file</span>
<?= implode("\n", $errors) ?>
<?php endif; ?>

<span style="color:#94a3b8">⚠️  HAPUS FILE INI SETELAH SELESAI!</span>
<span style="color:#94a3b8">   Path: /public/clear-cache.php</span>
</pre>

<p style="margin-top:20px;">
  <a href="/vela-dms-laravel/public/admin" style="color:#38bdf8">→ Buka Admin Panel</a> &nbsp;|&nbsp;
  <a href="/vela-dms-laravel/public/" style="color:#38bdf8">→ Buka Halaman Publik</a>
</p>
</body>
</html>
