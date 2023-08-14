<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use Gettext\Generator\MoGenerator;
use Gettext\Loader\PoLoader;
use SimpleSAML\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function array_merge;
use function dirname;
use function substr;

class UpdateBinaryTranslationsCommand extends Command
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'translations:update:binary';


    /**
     */
    protected function configure(): void
    {
        $this->setDescription('Generates fresh .mo translation files based on the current .po files');
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
        } elseif (!in_array($inputModule, $registeredModules)) {
            $output->writeln(sprintf('Module "%s" was not found.', $inputModule));
            return Command::FAILURE;
        } else {
            $modules = [$inputModule];
        }

        // This is the base directory of the SimpleSAMLphp installation
        $baseDir = dirname(__FILE__, 4);

        $loader = new PoLoader();
        $generator = new MoGenerator();
        $fileSystem = new Filesystem();

        foreach ($modules as $module) {
            $moduleDir = $baseDir . ($module === '' ? '' : '/modules/' . $module);
            $moduleLocalesDir = $moduleDir . '/locales/';

            if ($fileSystem->exists($moduleLocalesDir)) {
                $finder = new Finder();
                foreach ($finder->files()->in($moduleLocalesDir . '**/LC_MESSAGES/')->name('*.po') as $poFile) {
                    $translations = $loader->loadFile($poFile->getPathName());
                    $moFileName = substr($poFile->getPathName(), 0, -3) . '.mo';

                    // Overwrite the current .mo file with a fresh one
                    $generator->generateFile($translations, $moFileName);
                }
            }
        }

        return Command::SUCCESS;
    }
}
