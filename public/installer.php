<?php
define('LARAVEL_START', microtime(true));

// Ensure autoload exists before running
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    die("Error: Please run 'composer install' in the project directory first before running the installer.");
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Bootstrap the console kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<html><head><title>Vela DMS Setup</title><style>body { font-family: sans-serif; padding: 40px; background: #0f172a; color: #f1f5f9; } pre { background: #1e293b; padding: 20px; border-radius: 8px; border: 1px solid #334155; overflow-x: auto; color: #38bdf8; }</style></head><body>";
echo "<h1>Vela DMS Database & Key Auto Setup</h1>";
echo "<p>Running Laravel setup tasks...</p>";
echo "<pre>";

// 1. Generate Application Key
echo "Running php artisan key:generate...\n";
try {
    $status = $kernel->call('key:generate');
    echo "Status: " . ($status === 0 ? "SUCCESS" : "FAILED (code $status)") . "\n";
    echo $kernel->output() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 2. Run Database Migrations and Seed
echo "Running php artisan migrate:fresh --seed...\n";
try {
    $status = $kernel->call('migrate:fresh', ['--seed' => true, '--force' => true]);
    echo "Status: " . ($status === 0 ? "SUCCESS" : "FAILED (code $status)") . "\n";
    echo $kernel->output() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "\nSetup completed successfully! Please delete this file (public/installer.php) for security.";
echo "</pre>";
echo "<br><a href='/' style='color: #10b981; text-decoration: none; font-weight: bold;'>&larr; Go to Home Page</a>";
echo "</body></html>";
