<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use Gettext\Scanner\PhpScanner;
use Gettext\Loader\PoLoader;
use Gettext\Translation;
use Gettext\Translations;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\TestUtils\ArrayLogger;
use SimpleSAML\XHTML\Template;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

use function array_diff;
use function array_intersect;
use function array_merge;
use function dirname;
use function in_array;
use function sprintf;
use function substr;

class UnusedTranslatableStringsCommand extends Command
{
    /** @var string|null */
    protected static $defaultName = 'translations:unused';


    /**
     */
    protected function configure(): void
    {
        // We need the modules to be enabled, otherwise the Template class will complain
        Configuration::setPreloadedConfig(
            Configuration::loadFromArray([
                'module.enable' => array_fill_keys(Module::getModules(), true),
                'logging.handler' => ArrayLogger::class,
            ]),
            'config.php',
            'simplesaml'
        );

        $this->setDescription('Generates a list of translations that are no longer in used in PHP or Twig files');
        $this->addOption(
            'module',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Which modules to perform this action on',
        );
    }


    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputModules = $input->getOption('module');

        $registeredModules = Module::getModules();
        if (in_array('all', $inputModules) || $inputModules === []) {
            $modules = array_merge([''], $registeredModules);
        } elseif (in_array('main', $inputModules)) {
            $modules = array_merge([''], ['core', 'admin', 'cron', 'exampleauth', 'multiauth', 'saml']);
        } else {
            $known = array_intersect($registeredModules, $inputModules);
            $unknown = array_diff($inputModules, $registeredModules);

            if ($known === []) {
                $output->writeln('None of the provided modules were recognized.');
                return Command::FAILURE;
            }

            foreach ($unknown as $m) {
                $output->writeln(sprintf('Skipping module "%s"; unknown module.', $m));
            }
            $modules = $known;
        }

        // This is the base directory of the SimpleSAMLphp installation
        $baseDir = dirname(__FILE__, 4);

        $fileSystem = new Filesystem();

        $translationDomains = [];
        foreach ($modules as $module) {
            $domain = $module ?: 'messages';
            $translationDomains[] = Translations::create($domain);
        }

        $phpScanner = new PhpScanner(...$translationDomains);
        $phpScanner->setFunctions(['trans' => 'gettext', 'noop' => 'gettext']);

        $twigTranslations = [];
        // Scan files in base
        foreach ($modules as $module) {
            // Set the proper domain
            $phpScanner->setDefaultDomain($module ?: 'messages');

            $moduleDir = $baseDir . ($module === '' ? '' : '/modules/' . $module);
            $moduleSrcDir = $moduleDir . '/src/';
            $moduleTemplateDir = $moduleDir . '/templates/';

            // Scan PHP files
            $finder = new Finder();
            foreach ($finder->files()->in($moduleSrcDir)->name('*.php') as $file) {
                $phpScanner->scanFile($file->getPathName());
            }

            // Scan Twig-templates
            $finder = new Finder();
            foreach ($finder->files()->in($moduleTemplateDir)->depth('== 0')->name('*.twig') as $file) {
                $template = new Template(
                    Configuration::getInstance(),
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
        }

        // The catalogue returns an array with strings, while the php-scanner returns Translations-objects.
        // Migrate the catalogue results to match the php-scanner results.
        $migrated = [];
        foreach ($twigTranslations as $t) {
            foreach ($t as $domain => $translation) {
                $trans = Translations::create($domain);
                foreach ($translation as $s => $t) {
                    $trans->add(Translation::create(null, $s, $t));
                }
                $migrated[$domain][] = $trans;
            }
        }

        $loader = new PoLoader();

        $result = Command::SUCCESS;
        foreach ($phpScanner->getTranslations() as $domain => $template) {
            // If we also have results from the Twig-templates, merge them
            if (array_key_exists($domain, $migrated)) {
                foreach ($migrated[$domain] as $migratedTranslations) {
                    $template = $template->mergeWith($migratedTranslations);
                }
            }

            // If we have at least one translation, write it into a template file
            if ($template->count() > 0) {
                $moduleDir = $baseDir . ($domain === 'messages' ? '' : '/modules/' . $domain);
                $moduleLocalesDir = $moduleDir . '/locales/';
                $domain = $domain ?: 'messages';

                $finder = new Finder();
                foreach ($finder->files()->in($moduleLocalesDir . '**/LC_MESSAGES/')->name("{$domain}.po") as $poFile) {
                    $current = $loader->loadFile($poFile->getPathName());
                    foreach ($current->getTranslations() as $t) {
                        if (!$template->find(null, $t->getOriginal())) {
                            $output->writeln(sprintf(
                                "Unused translation '%s' found in file %s",
                                $t->getOriginal(),
                                $poFile->getPathName(),
                            ));
                            $result = Command::FAILURE;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
