<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use Gettext\Scanner\PhpScanner;
use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @package SimpleSAMLphp
 */
class Translate
{
    protected string $baseDir;


    public function __construct(
        protected Configuration $configuration
    ) {
        $this->baseDir = $configuration->getBaseDir();
    }


    public function getTranslationsFromPhp(string $module, PhpScanner $phpScanner): PhpScanner
    {
        $moduleDir = $this->baseDir . ($module === '' ? '' : 'modules/' . $module . '/');
        $moduleSrcDir = $moduleDir . 'src/';

        $finder = new Finder();
        foreach ($finder->files()->in($moduleSrcDir)->name('*.php') as $file) {
            $phpScanner->scanFile($file->getPathName());
        }

        return $phpScanner;
    }


    public function getTranslationsFromTwig(string $module): array
    {
        $twigTranslations = [];
        $moduleDir = $this->baseDir . ($module === '' ? '' : 'modules/' . $module . '/');
        $moduleTemplateDir = $moduleDir . 'templates/';

        // Scan Twig-templates
        $finder = new Finder();
        foreach ($finder->files()->in($moduleTemplateDir)->depth('== 0')->name('*.twig') as $file) {
            $template = new Template(
                $this->configuration,
                ($module ? ($module . ':') : '') . $file->getFileName(),
            );

            $catalogue = new MessageCatalogue('en', []);
            $extractor = new TwigExtractor($template->getTwig());
            $extractor->extract($file, $catalogue);

            $tmp = $catalogue->all();
            if ($tmp === []) {
                // This template has no translation strings
                continue;
            }

            // The catalogue always uses 'messages' for the domain and it's not configurable.
            // Manually replace it with the module-name
            if ($module !== '') {
                $tmp[$module] = $tmp['messages'];
                unset($tmp['messages']);
            }
            $twigTranslations[] = $tmp;
        }

        return $twigTranslations;
    }
}
