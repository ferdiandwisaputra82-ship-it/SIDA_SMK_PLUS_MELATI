<?php
/**
 * Vela DMS - Auto Setup Web Utility
 * Handles Composer installation, environment config, SQLite initialization, key generation, and migrations.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes limit for composer install

// Recursive helper to delete a directory and its files
function delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

// Helper function to send JSON response
function json_resp($success, $message, $logs = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'logs' => $logs
    ]);
    exit;
}

// Handle AJAX actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $projectRoot = dirname(__DIR__);

    switch ($action) {
        case 'composer':
            // Change directory to project root
            chdir($projectRoot);
            
            // Clean vendor and composer.lock first to prevent broken/corrupted states
            $vendorPath = $projectRoot . '/vendor';
            $lockPath = $projectRoot . '/composer.lock';
            if (file_exists($vendorPath)) {
                delete_directory($vendorPath);
            }
            if (file_exists($lockPath)) {
                @unlink($lockPath);
            }

            // Check if composer is in PATH
            $composerCmd = 'composer';
            exec('where composer 2>&1', $whereOutput, $whereCode);
            if ($whereCode !== 0) {
                // Try common paths or local composer
                $composerCmd = 'php composer.phar';
            }

            $output = [];
            $code = 0;
            // Run composer install
            exec("$composerCmd install --no-interaction --optimize-autoloader 2>&1", $output, $code);
            
            if ($code === 0) {
                json_resp(true, 'Dependensi Composer berhasil diinstal.', $output);
            } else {
                json_resp(false, 'Gagal menginstal dependensi Composer. Pastikan Composer terinstal di sistem.', $output);
            }
            break;

        case 'env':
            $envPath = $projectRoot . '/.env';
            $envExPath = $projectRoot . '/.env.example';

            if (!file_exists($envPath)) {
                if (file_exists($envExPath)) {
                    if (copy($envExPath, $envPath)) {
                        json_resp(true, 'File .env berhasil dibuat dari .env.example.');
                    } else {
                        json_resp(false, 'Gagal menduplikasi .env.example menjadi .env.');
                    }
                } else {
                    json_resp(false, 'File .env.example tidak ditemukan.');
                }
            } else {
                json_resp(true, 'File .env sudah ada.');
            }
            break;

        case 'database':
            $dbPath = $projectRoot . '/database/database.sqlite';
            
            // Re-create the empty SQLite file
            if (file_exists($dbPath)) {
                @unlink($dbPath);
            }
            
            if (@file_put_contents($dbPath, '') !== false) {
                json_resp(true, 'Database SQLite kosong berhasil dibuat.');
            } else {
                json_resp(false, 'Gagal membuat file database SQLite di ' . $dbPath . '. Pastikan folder database memiliki izin menulis.');
            }
            break;

        case 'key':
            chdir($projectRoot);
            $output = [];
            $code = 0;
            exec('php artisan key:generate 2>&1', $output, $code);

            if ($code === 0) {
                json_resp(true, 'Application encryption key (APP_KEY) berhasil dibuat.', $output);
            } else {
                json_resp(false, 'Gagal membuat APP_KEY. Pastikan PHP CLI terinstal dan terdaftar di PATH.', $output);
            }
            break;

        case 'migrate':
            chdir($projectRoot);
            $output = [];
            $code = 0;
            exec('php artisan migrate:fresh --seed --force 2>&1', $output, $code);

            if ($code === 0) {
                json_resp(true, 'Migrasi database & data seeding berhasil dijalankan.', $output);
            } else {
                json_resp(false, 'Gagal menjalankan migrasi database.', $output);
            }
            break;

        default:
            json_resp(false, 'Aksi tidak dikenal.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vela DMS - Web Auto Installer & Setup</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .glass {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a 60%);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 flex flex-col items-center justify-center p-4">

    <div class="max-w-2xl w-full glass rounded-3xl p-8 shadow-2xl relative overflow-hidden">
        <!-- Glow Effect -->
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <div class="flex items-center gap-4 mb-8">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                <i data-lucide="cog" class="w-6 h-6 text-white animate-spin-slow"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">Vela DMS Web Installer</h1>
                <p class="text-sm text-slate-400">Utilitas konfigurasi otomatis aplikasi dan basis data</p>
            </div>
        </div>

        <!-- Tasks List -->
        <div class="space-y-4 mb-8">
            <!-- Step 1: Composer -->
            <div id="step-composer" class="flex items-center justify-between p-4 bg-slate-800/40 rounded-2xl border border-slate-700/30 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="step-icon w-8 h-8 rounded-lg bg-slate-700/50 flex items-center justify-center">
                        <i data-lucide="package" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-sm">Instal Dependensi Composer</div>
                        <div class="text-xs text-slate-500">Mendownload & menginstal library PHP vendor</div>
                    </div>
                </div>
                <div class="step-status text-slate-400 text-xs font-semibold">Menunggu</div>
            </div>

            <!-- Step 2: Env -->
            <div id="step-env" class="flex items-center justify-between p-4 bg-slate-800/40 rounded-2xl border border-slate-700/30 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="step-icon w-8 h-8 rounded-lg bg-slate-700/50 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-sm">Konfigurasi Berkas .env</div>
                        <div class="text-xs text-slate-500">Membuat file konfigurasi utama</div>
                    </div>
                </div>
                <div class="step-status text-slate-400 text-xs font-semibold">Menunggu</div>
            </div>

            <!-- Step 3: Database -->
            <div id="step-database" class="flex items-center justify-between p-4 bg-slate-800/40 rounded-2xl border border-slate-700/30 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="step-icon w-8 h-8 rounded-lg bg-slate-700/50 flex items-center justify-center">
                        <i data-lucide="database" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-sm">Inisialisasi Database SQLite</div>
                        <div class="text-xs text-slate-500">Membuat file database.sqlite kosong yang bersih</div>
                    </div>
                </div>
                <div class="step-status text-slate-400 text-xs font-semibold">Menunggu</div>
            </div>

            <!-- Step 4: Key -->
            <div id="step-key" class="flex items-center justify-between p-4 bg-slate-800/40 rounded-2xl border border-slate-700/30 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="step-icon w-8 h-8 rounded-lg bg-slate-700/50 flex items-center justify-center">
                        <i data-lucide="key-round" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-sm">Generate Application Key</div>
                        <div class="text-xs text-slate-500">Membuat kunci enkripsi unik di .env</div>
                    </div>
                </div>
                <div class="step-status text-slate-400 text-xs font-semibold">Menunggu</div>
            </div>

            <!-- Step 5: Migrate -->
            <div id="step-migrate" class="flex items-center justify-between p-4 bg-slate-800/40 rounded-2xl border border-slate-700/30 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <div class="step-icon w-8 h-8 rounded-lg bg-slate-700/50 flex items-center justify-center">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-400"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-sm">Migrasi Database & Seeding</div>
                        <div class="text-xs text-slate-500">Membuat tabel & memasukkan data contoh admin</div>
                    </div>
                </div>
                <div class="step-status text-slate-400 text-xs font-semibold">Menunggu</div>
            </div>
        </div>

        <!-- Terminal Output -->
        <div class="mb-8 hidden" id="terminal-section">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2 px-1">
                <span>Log Konsol Aktivitas</span>
                <button onclick="toggleTerminal()" class="hover:text-white transition-colors">Tutup</button>
            </div>
            <div id="terminal" class="bg-black/50 border border-slate-800 rounded-2xl p-4 h-48 overflow-y-auto font-mono text-[11px] leading-relaxed text-blue-400 whitespace-pre-wrap"></div>
        </div>

        <!-- Action Button / Success Link -->
        <div class="flex flex-col gap-4">
            <button id="btn-start" onclick="startSetup()" class="w-full py-4 bg-blue-600 hover:bg-blue-500 active:scale-[0.99] text-white font-semibold rounded-2xl transition-all shadow-lg shadow-blue-500/25 flex items-center justify-center gap-2">
                <i data-lucide="play" class="w-4 h-4"></i>
                Mulai Konfigurasi Otomatis
            </button>
            
            <a id="btn-complete" href="/" class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white font-semibold rounded-2xl transition-all text-center shadow-lg shadow-emerald-500/25 hidden flex items-center justify-center gap-2">
                <i data-lucide="check" class="w-4 h-4"></i>
                Buka Halaman Utama Website &rarr;
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center text-xs text-slate-500">
        Vela DMS v3.0 Setup Helper &bull; Setelah selesai, disarankan menghapus file <code>public/auto-setup.php</code> demi keamanan.
    </div>

    <script>
        // Init Lucide
        lucide.createIcons();

        function logToTerminal(text) {
            const term = document.getElementById('terminal');
            term.innerHTML += text + "\n";
            term.scrollTop = term.scrollHeight;
        }

        function updateStepUI(stepId, status, details = '') {
            const step = document.getElementById('step-' + stepId);
            const statusDiv = step.querySelector('.step-status');
            const iconContainer = step.querySelector('.step-icon');
            
            // Remove previous classes
            step.className = step.className.replace(/bg-[a-z0-9\/\-]+/g, '');
            iconContainer.className = iconContainer.className.replace(/bg-[a-z0-9\/\-]+/g, '');

            if (status === 'running') {
                step.classList.add('bg-blue-900/10', 'border-blue-500/30');
                statusDiv.innerHTML = '<span class="text-blue-400 flex items-center gap-1.5"><svg class="animate-spin h-3 w-3 text-blue-400" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...</span>';
                iconContainer.classList.add('bg-blue-500/20');
                iconContainer.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 text-blue-400 animate-spin"></i>';
            } else if (status === 'success') {
                step.classList.add('bg-emerald-900/10', 'border-emerald-500/30');
                statusDiv.innerHTML = '<span class="text-emerald-400 flex items-center gap-1"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Selesai</span>';
                iconContainer.classList.add('bg-emerald-500/20');
                iconContainer.innerHTML = '<i data-lucide="check" class="w-4 h-4 text-emerald-400"></i>';
            } else if (status === 'failed') {
                step.classList.add('bg-rose-900/10', 'border-rose-500/30');
                statusDiv.innerHTML = '<span class="text-rose-400 flex items-center gap-1"><i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Gagal</span>';
                iconContainer.classList.add('bg-rose-500/20');
                iconContainer.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 text-rose-400"></i>';
            }
            lucide.createIcons();
        }

        async function runAction(actionName) {
            logToTerminal(`[${actionName.toUpperCase()}] Memulai tugas...`);
            const res = await fetch(`auto-setup.php?action=${actionName}`);
            const data = await res.json();
            
            if (data.logs && data.logs.length > 0) {
                logToTerminal(data.logs.join("\n"));
            }
            
            if (data.success) {
                logToTerminal(`[SUCCESS] ${data.message}\n`);
                return true;
            } else {
                logToTerminal(`[ERROR] ${data.message}\n`);
                alert(`Error: ${data.message}`);
                return false;
            }
        }

        async function startSetup() {
            document.getElementById('btn-start').disabled = true;
            document.getElementById('btn-start').classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('terminal-section').classList.remove('hidden');
            
            logToTerminal("Memulai konfigurasi otomatis Vela DMS...");
            logToTerminal("=========================================\n");

            const steps = ['composer', 'env', 'database', 'key', 'migrate'];
            
            for (let step of steps) {
                updateStepUI(step, 'running');
                const ok = await runAction(step);
                if (ok) {
                    updateStepUI(step, 'success');
                } else {
                    updateStepUI(step, 'failed');
                    document.getElementById('btn-start').disabled = false;
                    document.getElementById('btn-start').classList.remove('opacity-50', 'cursor-not-allowed');
                    return;
                }
            }

            logToTerminal("=========================================");
            logToTerminal("SETUP SELESAI DENGAN SUKSES! APLIKASI SIAP DIGUNAKAN.");
            
            document.getElementById('btn-start').classList.add('hidden');
            document.getElementById('btn-complete').classList.remove('hidden');
        }
    </script>
</body>
</html>
