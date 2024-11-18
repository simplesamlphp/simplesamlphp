<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use Gettext\Scanner\PhpScanner;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;

use function array_merge;
use function explode;
use function is_dir;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * @package SimpleSAMLphp
 */
class Translate
{
    protected string $baseDir;


    public function __construct(
        protected Configuration $configuration,
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
        if (is_dir($moduleDir . 'hooks/')) {
            foreach ($finder->files()->in($moduleDir . 'hooks/')->name('*.php') as $file) {
                $phpScanner->scanFile($file->getPathName());
            }
        }

        return $phpScanner;
    }


    public function getTranslationsFromTwig(string $module, bool $includeThemes = false): array
    {
        $twigTranslations = [];
        $moduleDir = $this->baseDir . ($module === '' ? '' : 'modules/' . $module . '/');
        $moduleTemplateDir = $moduleDir . 'templates/';
        $moduleThemeDir = $moduleDir . 'themes/';
        $moduleDirs = [];
        if (is_dir($moduleTemplateDir)) {
            $moduleDirs[] = $moduleTemplateDir;
        }
        if ($includeThemes && is_dir($moduleThemeDir)) {
            $moduleDirs[] = $moduleThemeDir;
        }

        // Scan Twig-templates
        $finder = new Finder();
        foreach ($finder->files()->in($moduleDirs)->name('*.twig') as $file) {
            if (!($includeThemes && str_starts_with($file->getPathname(), $moduleThemeDir))) {
                /* process templates/ directory */
                $template = new Template(
                    $this->configuration,
                    ($module ? ($module . ':') : '') . $file->getRelativePathname(),
                );
            } else {
                /* process themed templates from other modules */
                list($theme, $themedModule) = explode(
                    DIRECTORY_SEPARATOR,
                    substr($file->getPath(), strlen($moduleThemeDir)),
                    2,
                );
                if ($themedModule !== 'default' && !Module::isModuleEnabled($themedModule)) {
                    throw new Exception(
                        'The module \'' . $themedModule . '\' (themed by \'' . $module . ':' . $theme . '\') ' .
                        'is not enabled. Perhaps you need to need to require --dev simplesamlphp-module-' .
                        $themedModule . ' within the ' . $module . ' module?',
                    );
                }
                $template = new Template(
                    Configuration::loadFromArray(
                        array_merge(
                            $this->configuration->toArray(),
                            ['theme.use' => $module . ':' . $theme,],
                        ),
                    ),
                    ($themedModule !== 'default' ? ($themedModule . ':') : '') .
                    substr($file->getRelativePathname(), strlen($theme . DIRECTORY_SEPARATOR . $themedModule) + 1),
                );
            }

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
