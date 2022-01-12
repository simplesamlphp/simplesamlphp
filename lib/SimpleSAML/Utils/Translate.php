<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

/**
 * @package SimpleSAMLphp
 */
class Translate
{
    /**
     * Compile all Twig templates for the given $module into the given $outputDir.
     * This is used by the translation extraction tool to find the translatable
     * strings for this module in the compiled templates.
     * $module can be '' for the main SimpleSAMLphp templates.
     */
    public function compileAllTemplates(string $module, string $outputDir): void
    {
        $config = Configuration::loadFromArray(['template.cache' => $outputDir, 'module.enable' => [$module => true]]);
        $baseDir = $config->getBaseDir();
        $tplSuffix = DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;

        $tplDir = $baseDir . ($module === '' ? '' : 'modules' . DIRECTORY_SEPARATOR . $module) . $tplSuffix;
        $templateprefix = ($module === '' ? '' : $module . ":");

        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tplDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ) as $file
        ) {
            if ($file->isFile()) {
                $p = new Template($config, $templateprefix . str_replace($tplDir, '', $file->getPathname()));
                $p->compile();
            }
        }
    }
}
