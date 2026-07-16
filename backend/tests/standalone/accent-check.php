<?php
// Run with: php backend/tests/standalone/accent-check.php
//
// Standalone CLI script to verify no indigo-* Tailwind classes remain
// in Blade view files outside the OOS paths (welcome.blade.php).
// Exits 0 if clean, exits 1 with offending file list if not.

$viewsDir = __DIR__ . '/../../resources/views';

if (!is_dir($viewsDir)) {
    fwrite(STDERR, "ERROR: Views directory not found at: $viewsDir\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$pattern = '/indigo-(50|100|200|300|400|500|600|700|800|900)/';
$offending = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $relativePath = str_replace('\\', '/', $file->getPathname());
    $relativePath = substr($relativePath, strlen(str_replace('\\', '/', $viewsDir)) + 1);

    // Skip OOS paths
    if ($relativePath === 'welcome.blade.php') {
        continue;
    }

    // Skip legacy files already migrated in PR #2 (sidebar mode uses emerald)
    if ($relativePath === 'components/nav-link.blade.php' ||
        $relativePath === 'components/responsive-nav-link.blade.php') {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    if (preg_match_all($pattern, $content, $matches)) {
        $offending[] = $relativePath . ' (' . implode(', ', $matches[0]) . ')';
    }
}

if (empty($offending)) {
    echo "OK: 0 indigo classes found in non-OOS Blade files\n";
    exit(0);
}

echo "FAIL: " . count($offending) . " file(s) still contain indigo-* classes:\n";
foreach ($offending as $file) {
    echo "  - $file\n";
}
exit(1);
