<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Asset;

use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function is_dir;
use function scandir;

class ModuleGitignoreTest extends TestCase
{
    public function testModuleAssetsAreNotIgnored(): void
    {
        $baseDir = dirname(__DIR__, 4);
        $gitignorePath = $baseDir . '/.gitignore';
        $modulesDir = $baseDir . '/modules';

        $this->assertFileExists($gitignorePath, '.gitignore file should exist');
        $gitignore = file_get_contents($gitignorePath);

        $modules = scandir($modulesDir);
        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            if (is_dir($modulesDir . '/' . $module . '/public/assets')) {
                $expectedLine1 = '!/public/assets/' . $module . '/';
                $expectedLine2 = '!/public/assets/' . $module . '/**';

                $this->assertStringContainsString(
                    $expectedLine1,
                    $gitignore,
                    "Module '$module' has public assets, but '$expectedLine1' is missing in .gitignore",
                );
                $this->assertStringContainsString(
                    $expectedLine2,
                    $gitignore,
                    "Module '$module' has public assets, but '$expectedLine2' is missing in .gitignore",
                );
            }
        }
    }
}
