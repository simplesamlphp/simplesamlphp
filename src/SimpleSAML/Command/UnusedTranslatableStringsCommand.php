<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use Gettext\Loader\PoLoader;
use Gettext\Scanner\PhpScanner;
use Gettext\Translation;
use Gettext\Translations;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\TestUtils\ArrayLogger;
use SimpleSAML\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use function array_diff;
use function array_fill_keys;
use function array_intersect;
use function array_key_exists;
use function array_key_first;
use function array_merge;
use function dirname;
use function in_array;
use function sprintf;

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
            'simplesaml',
        );

        $this->setDescription('Generates a list of translations that are no longer in used in PHP or Twig files');
        $this->addOption(
            'module',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Which modules to perform this action on',
        );
        $this->addOption(
            'themes',
            null,
            InputOption::VALUE_NEGATABLE,
            'Include/exclude templates from themes directories of modules (default --themes)',
            true, /* safe default, so we don't lose translations */
        );
        $this->addOption(
            'theme-controller',
            null,
            InputOption::VALUE_REQUIRED,
            'Specify a custom TemplateControllerInterface class (usually for use with themes)',
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

        $translationDomains = [];
        foreach ($modules as $module) {
            $domain = $module ?: 'messages';
            $translationDomains[] = Translations::create($domain);
        }

        $phpScanner = new PhpScanner(...$translationDomains);
        $phpScanner->setFunctions(['trans' => 'gettext', 'noop' => 'gettext']);

        if ($input->getOption('theme-controller') !== null) {
            Configuration::setPreLoadedConfig(
                Configuration::loadFromArray(
                    array_merge(
                        Configuration::getInstance()->toArray(),
                        ['theme.controller' => $input->getOption('theme-controller'),],
                    ),
                ),
            );
        }

        $translationUtils = new Utils\Translate(Configuration::getInstance());
        $twigTranslations = [];
        // Scan files in base
        foreach ($modules as $module) {
            // Set the proper domain
            $phpScanner->setDefaultDomain($module ?: 'messages');

            // Scan PHP files
            $phpScanner = $translationUtils->getTranslationsFromPhp($module, $phpScanner);

            // Scan Twig-templates
            $twigTranslations = array_merge(
                $twigTranslations,
                $translationUtils->getTranslationsFromTwig($module, $input->getOption('themes')),
            );
        }

        // The catalogue returns an array with strings, while the php-scanner returns Translations-objects.
        // Migrate the catalogue results to match the php-scanner results.
        $migrated = [];
        foreach ($twigTranslations as $t) {
            $domain = array_key_first($t);
            $translation = $t[$domain];
            $trans = Translations::create($domain);
            foreach ($translation as $s) {
                $trans->add(Translation::create(null, $s));
            }
            $migrated[$domain][] = $trans;
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
