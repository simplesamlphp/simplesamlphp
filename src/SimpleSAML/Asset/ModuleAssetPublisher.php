<?php

declare(strict_types=1);

namespace SimpleSAML\Asset;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function is_dir;
use function rtrim;
use function sprintf;

final class ModuleAssetPublisher
{
    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }


    /**
     * Publish module assets from module public asset directories into public/assets/<module>.
     *
     * @param string $baseDir
     * @return string[] The list of modules whose published asset trees were updated.
     */
    public function publish(string $baseDir): array
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
        $modulesDir = $baseDir . '/modules';
        $publicAssetsDir = $baseDir . '/public/assets';

        $this->filesystem->mkdir($publicAssetsDir);

        $finder = new Finder();
        $finder->directories()->in($modulesDir)->depth(0);

        $published = [];
        foreach ($finder as $moduleDir) {
            $module = $moduleDir->getFilename();
            $sourceDir = $moduleDir->getRealPath() . '/public/assets';
            $targetDir = sprintf('%s/%s', $publicAssetsDir, $module);

            if (is_dir($sourceDir)) {
                $this->filesystem->mirror($sourceDir, $targetDir, null, [
                    'override' => true,
                    'delete' => true,
                ]);
                $published[] = $module;
            } elseif (is_dir($targetDir)) {
                $this->filesystem->remove($targetDir);
            }
        }

        return $published;
    }
}
