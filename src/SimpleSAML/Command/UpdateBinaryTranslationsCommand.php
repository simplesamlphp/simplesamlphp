<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use Gettext\Generator\MoGenerator;
use Gettext\Loader\PoLoader;
use SimpleSAML\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function array_diff;
use function array_intersect;
use function array_merge;
use function dirname;
use function in_array;
use function sprintf;
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
