<?php
header('Content-Type: text/plain');
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap HTTP Kernel so all service providers are registered
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::create('/', 'GET'));

$compiler = $app['blade.compiler'];
$viewsDir = realpath(__DIR__ . '/../resources/views');

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
$errors = [];
$tested = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        $relativePath = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $path);
        $tested++;

        try {
            $contents = file_get_contents($path);
            $compiled = $compiler->compileString($contents);

            // Lint compiled code using php -l equivalent via tokenizing or linting
            $tokens = @token_get_all($compiled);
            
            $ifCount = 0;
            $endifCount = 0;
            foreach ($tokens as $token) {
                if (is_array($token)) {
                    if ($token[0] === T_IF) $ifCount++;
                    if ($token[0] === T_ENDIF) $endifCount++;
                }
            }
            if ($ifCount !== $endifCount) {
                $errors[] = "[DIRECTIVE MISMATCH] $relativePath -> T_IF: $ifCount, T_ENDIF: $endifCount";
            }
        } catch (\Throwable $e) {
            $errors[] = "[COMPILE ERROR] $relativePath -> " . $e->getMessage();
        }
    }
}

echo "TOTAL VIEWS TESTED: $tested\n\n";
if (empty($errors)) {
    echo "ALL BLADE VIEWS PASSED COMPILATION!";
} else {
    echo "ERRORS FOUND:\n" . implode("\n", $errors);
}
