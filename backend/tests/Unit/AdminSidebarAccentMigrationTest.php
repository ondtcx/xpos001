<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminSidebarAccentMigrationTest extends TestCase
{
    private const INDIGO_REGEX = '/indigo-(50|100|200|300|400|500|600|700|800|900)/';

    private const OOS_FILE = 'welcome.blade.php';

    // Already migrated in PR #2 (sidebar mode uses emerald; these classes
    // remain only for backward-compatible default mode, not actively used)
    private const LEGACY_FILES = [
        'components/nav-link.blade.php',
        'components/responsive-nav-link.blade.php',
    ];

    #[Test]
    public function test_no_indigo_classes_outside_oos_paths(): void
    {
        $viewsDir = realpath(__DIR__ . '/../../resources/views');
        $this->assertNotFalse($viewsDir, 'Views directory not found');

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $offending = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace('\\', '/', $file->getPathname());
            $relativePath = substr($relativePath, strlen(str_replace('\\', '/', $viewsDir)) + 1);

            // Skip OOS paths and files
            if ($relativePath === self::OOS_FILE) {
                continue;
            }

            // Skip legacy files already migrated in PR #2
            if (in_array($relativePath, self::LEGACY_FILES, true)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if (preg_match_all(self::INDIGO_REGEX, $content, $matches)) {
                $offending[] = $relativePath . ' (' . implode(', ', $matches[0]) . ')';
            }
        }

        $this->assertEmpty(
            $offending,
            "Found " . count($offending) . " file(s) with indigo-* classes outside OOS paths:\n" . implode("\n", $offending)
        );
    }
}
