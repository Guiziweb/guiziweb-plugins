<?php

declare(strict_types=1);

// Reads composer.json + filesystem in cwd, writes `export X=Y` lines to $CLAUDE_ENV_FILE.
// For plugin context, source-of-truth is the filesystem (bundle class file, DI extension class file)
// — same names as rename-plugin.php produced, no risk of drift.

$envFile = getenv('CLAUDE_ENV_FILE');
if (!$envFile || !is_file('composer.json')) {
    exit(0);
}

$composer = json_decode(file_get_contents('composer.json'), true);
if (!is_array($composer)) {
    exit(0);
}

// --- context ---
$isPlugin = ($composer['type'] ?? '') === 'sylius-plugin';
$isApp = !$isPlugin && isset($composer['require']['sylius/sylius']);

if (!$isPlugin && !$isApp) {
    exit(0);
}

// --- namespace (first PSR-4 entry pointing to src/) ---
$namespace = null;
foreach ($composer['autoload']['psr-4'] ?? [] as $ns => $path) {
    $firstPath = is_array($path) ? $path[0] : $path;
    if (str_starts_with($firstPath, 'src')) {
        $namespace = rtrim($ns, '\\');
        break;
    }
}

// --- build exports ---
$exports = [
    'SYLIUS_CONTEXT' => $isPlugin ? 'plugin' : 'app',
    'SYLIUS_CONSOLE' => $isPlugin
        ? 'docker compose exec php vendor/bin/console'
        : 'docker compose exec php bin/console',
];

if ($namespace !== null) {
    $exports['SYLIUS_NAMESPACE'] = $namespace;
}

if ($isPlugin) {
    // Bundle class: read filename from src/*Plugin.php (set by rename-plugin.php).
    $bundleFiles = glob('src/*Plugin.php') ?: [];
    if (count($bundleFiles) === 1) {
        $bundleClass = basename($bundleFiles[0], '.php');
        $exports['SYLIUS_TEMPLATE_NS'] = '@' . $bundleClass . '/';
    }

    // Extension alias: read filename from src/DependencyInjection/*Extension.php (set by rename-plugin.php).
    // The DI extension's getAlias() returns snake_case of class name without "Extension" suffix.
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
} else {
    // App: hardcoded conventions.
    $exports['SYLIUS_PREFIX'] = 'app';
    $exports['SYLIUS_TEMPLATE_NS'] = '';
}

// --- write ---
$body = '';
foreach ($exports as $k => $v) {
    $body .= "export $k=" . escapeshellarg($v) . "\n";
}
file_put_contents($envFile, $body, FILE_APPEND);

echo "Sylius project detected. Env vars exported:\n";
foreach ($exports as $k => $v) {
    echo "  - $k = $v\n";
}