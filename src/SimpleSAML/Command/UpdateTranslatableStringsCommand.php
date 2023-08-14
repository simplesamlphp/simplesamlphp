<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use Gettext\Scanner\PhpScanner;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Merge;
use Gettext\Translation;
use Gettext\Translations;
use SimpleSAML\Module;
use SimpleSAML\XHTML\TemplateLoader;
use Symfony\Bridge\Twig\Translation\TwigExtractor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

use function array_merge;
use function dirname;
use function substr;

class UpdateTranslatableStringsCommand extends Command
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'translations:update:translatable';


    /**
     */
    protected function configure(): void
    {
        $this->setDescription('Generates fresh .po translation files based on the translatable strings from PHP and Twig files');
        $this->addArgument('module', InputArgument::REQUIRED, 'Module');
    }


    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputModule = $input->getArgument('module');
        $registeredModules = Module::getModules();
        if ($inputModule === 'all') {
            $modules = Module::getModules();
            $modules = array_merge([''], $modules);
        } elseif ($inputModule === 'main') {
            $modules = ['core', 'admin', 'cron', 'exampleauth', 'multiauth', 'saml'];
        } elseif (!in_array($inputModule, $registeredModules)) {
            $output->writeln(sprintf('Module "%s" was not found.', $inputModule));
            return Command::FAILURE;
        } else {
            $modules = [$inputModule];
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
            foreach ($finder->files()->in($moduleTemplateDir)->name('*.twig') as $file) {
                $loader = new TemplateLoader([$moduleTemplateDir]);
                $env = new Environment($loader);

                $catalogue = new MessageCatalogue('en', []);
                $extractor = new TwigExtractor($env);
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
        $poGenerator = new PoGenerator();

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
                    $merged = $template->mergeWith(
                        $current,
                        Merge::TRANSLATIONS_THEIRS | Merge::COMMENTS_OURS | Merge::HEADERS_OURS,
                    );

                    $poGenerator->generateFile($merged, $poFile->getPathName());
                }
            }
        }

        return Command::SUCCESS;
    }
}
