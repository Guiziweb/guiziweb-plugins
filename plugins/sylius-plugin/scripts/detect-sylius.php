<?php

declare(strict_types=1);

// Detects a Sylius plugin project and exports env vars consumed by skills.
// Bails silently when the project is not a Sylius plugin.

$envFile = getenv('CLAUDE_ENV_FILE');
if (!$envFile || !is_file('composer.json')) {
    exit(0);
}

$composer = json_decode(file_get_contents('composer.json'), true);
if (!is_array($composer) || ($composer['type'] ?? '') !== 'sylius-plugin') {
    exit(0);
}

$exports = [];

// Namespace: first PSR-4 entry pointing to src/.
foreach ($composer['autoload']['psr-4'] ?? [] as $ns => $path) {
    $firstPath = is_array($path) ? $path[0] : $path;
    if (str_starts_with($firstPath, 'src')) {
        $exports['SYLIUS_NAMESPACE'] = rtrim($ns, '\\');
        break;
    }
}

// Template namespace: from src/*Plugin.php bundle class filename.
$bundleFiles = glob('src/*Plugin.php') ?: [];
if (count($bundleFiles) === 1) {
    $exports['SYLIUS_TEMPLATE_NS'] = '@' . basename($bundleFiles[0], '.php') . '/';
}

// Prefix: from src/DependencyInjection/*Extension.php, snake_case of class without "Extension".
$extensionFiles = glob('src/DependencyInjection/*Extension.php') ?: [];
if (count($extensionFiles) === 1) {
    $extensionClass = basename($extensionFiles[0], '.php');
    $alias = preg_replace('/Extension$/', '', $extensionClass);
    if ($alias !== '' && $alias !== null) {
        $exports['SYLIUS_PREFIX'] = strtolower(
            preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '_', $alias),
        );
    }
}

$body = '';
foreach ($exports as $k => $v) {
    $body .= "export $k=" . escapeshellarg($v) . "\n";
}
file_put_contents($envFile, $body, FILE_APPEND);

echo "Sylius plugin detected. Env vars exported:\n";
foreach ($exports as $k => $v) {
    echo "  - $k = $v\n";
}