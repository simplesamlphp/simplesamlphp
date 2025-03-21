<?php

declare(strict_types=1);

namespace SimpleSAML\Command;

use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Kernel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Dumper\Preloader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\RebootableInterface;

#[AsCommand(
    name: 'ssp-cache:clear',
    description: 'SimpleSamlPHP cache clear command. Clear the SimpleSamlPHP and all modules cache.',
)]
class SspCacheClearCommand extends Command
{
    private CacheClearerInterface $cacheClearer;
    private Filesystem $filesystem;

    private array $enabledModules;

    public function __construct(CacheClearerInterface $cacheClearer, Filesystem $filesystem = null)
    {
        parent::__construct();

        $this->cacheClearer = $cacheClearer;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('no-warmup', '', InputOption::VALUE_NONE, 'Do not warm up the cache'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                new InputOption('no-optional-warmers', '', InputOption::VALUE_NONE, 'Skip optional cache warmers (faster)'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command clears and warms up the application cache for a given environment
and debug mode:

  <info>php %command.full_name% --env=dev</info>
  <info>php %command.full_name% --env=prod --no-debug</info>
EOF,);
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = $this->filesystem;
        try {
            $this->enabledModules = Configuration::getInstance()->getArray('module.enable');
        } catch (CriticalConfigurationError $e) {
            $io->comment('<error>Error:configuration file not found</error>');
            exit;
        }
        $io->comment('Starting the SimpleSamlPHP cache clearing process.');

        $application = $this->getApplication();

        if ($application === null) {
            $io->error('Application instance is not available.');
            return Command::FAILURE;
        }

        // Iterate and clean
        foreach ($this->enabledModules as $module => $enabled) {
            // Only work on enabled modules
            if (!filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }
            $io->comment(sprintf('Clearing cache for module: <info>"%s"</info>.', $module));

            $kernel = new Kernel($module);
            $kernel->boot();

            $realCacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
            $realBuildDir = $kernel->getContainer()->hasParameter('kernel.build_dir')
                ? $kernel->getContainer()->getParameter('kernel.build_dir') : $realCacheDir;
            // the old cache dir name must not be longer than the real one to avoid exceeding
            // the maximum length of a directory or file path within it (esp. Windows MAX_PATH)
            $oldCacheDir = substr($realCacheDir, 0, -1) . (str_ends_with($realCacheDir, '~') ? '+' : '~');
            $fs->remove($oldCacheDir);

            if (!is_writable($realCacheDir)) {
                throw new RuntimeException(sprintf('Unable to write in the "%s" directory.', $realCacheDir));
            }

            $useBuildDir = $realBuildDir !== $realCacheDir;
            $oldBuildDir = substr($realBuildDir, 0, -1) . (str_ends_with($realBuildDir, '~') ? '+' : '~');
            if ($useBuildDir) {
                $fs->remove($oldBuildDir);

                if (!is_writable($realBuildDir)) {
                    throw new RuntimeException(sprintf('Unable to write in the "%s" directory.', $realBuildDir));
                }

                if ($this->isNfs($realCacheDir)) {
                    $fs->remove($realCacheDir);
                } else {
                    $fs->rename($realCacheDir, $oldCacheDir);
                }
                $fs->mkdir($realCacheDir);
            }

            if ($useBuildDir) {
                $this->cacheClearer->clear($realBuildDir);
            }
            $this->cacheClearer->clear($realCacheDir);

            // The current event dispatcher is stale, let's not use it anymore
            $this->getApplication()->setDispatcher(new EventDispatcher());

            $containerFile = (new \ReflectionObject($kernel->getContainer()))->getFileName();
            $containerDir = basename(\dirname($containerFile));

            // the warmup cache dir name must have the same length as the real one
            // to avoid the many problems in serialized resources files
            $warmupDir = substr($realBuildDir, 0, -1) . (str_ends_with($realBuildDir, '_') ? '-' : '_');

            if ($output->isVerbose() && $fs->exists($warmupDir)) {
                $io->comment('Clearing outdated warmup directory...');
            }
            $fs->remove($warmupDir);

            if ($_SERVER['REQUEST_TIME'] <= filemtime($containerFile) && filemtime($containerFile) <= time()) {
                if ($output->isVerbose()) {
                    $io->comment('Cache is fresh.');
                }
                if (!$input->getOption('no-warmup') && !$input->getOption('no-optional-warmers')) {
                    if ($output->isVerbose()) {
                        $io->comment('Warming up optional cache...');
                    }
                    $this->warmupOptionals($realCacheDir, $realBuildDir, $io);
                }
            } else {
                $fs->mkdir($warmupDir);

                if (!$input->getOption('no-warmup')) {
                    if ($output->isVerbose()) {
                        $io->comment('Warming up cache...');
                    }
                    $this->warmup($warmupDir, $realBuildDir);

                    if (!$input->getOption('no-optional-warmers')) {
                        if ($output->isVerbose()) {
                            $io->comment('Warming up optional cache...');
                        }
                        $this->warmupOptionals($useBuildDir ? $realCacheDir : $warmupDir, $warmupDir, $io);
                    }

                    // fix references to cached files with the real cache directory name
                    $search = [
                        $warmupDir,
                        str_replace('/', '\\/', $warmupDir),
                        str_replace('\\', '\\\\', $warmupDir),
                        ];
                    $replace = str_replace('\\', '/', $realBuildDir);
                    foreach (Finder::create()->files()->in($warmupDir) as $file) {
                        $content = str_replace($search, $replace, file_get_contents($file), $count);
                        if ($count) {
                            file_put_contents($file, $content);
                        }
                    }
                }

                if (!$fs->exists($warmupDir . '/' . $containerDir)) {
                    $fs->rename($realBuildDir . '/' . $containerDir, $warmupDir . '/' . $containerDir);
                    touch($warmupDir . '/' . $containerDir . '.legacy');
                }

                if ($this->isNfs($realBuildDir)) {
                    $noteMessage = 'For better performance, you should move '
                    . 'the cache and log directories to a non-shared folder of the VM.';
                    $io->note($noteMessage);
                    $fs->remove($realBuildDir);
                } else {
                    $fs->rename($realBuildDir, $oldBuildDir);
                }

                $fs->rename($warmupDir, $realBuildDir);

                if ($output->isVerbose()) {
                    $io->comment('Removing old build and cache directory...');
                }

                if ($useBuildDir) {
                    try {
                        $fs->remove($oldBuildDir);
                    } catch (IOException $e) {
                        if ($output->isVerbose()) {
                            $io->warning($e->getMessage());
                        }
                    }
                }

                try {
                    $fs->remove($oldCacheDir);
                } catch (IOException $e) {
                    if ($output->isVerbose()) {
                        $io->warning($e->getMessage());
                    }
                }
            }

            if ($output->isVerbose()) {
                $io->comment('Finished');
            }

            $io->comment(
                sprintf('Cache for module: <info>"%s"</info> was <info>successfully</info> cleared.', $module),
            );


            // Shutdown and restart
            $kernel->shutdown();
        }

        $io->success('SimpleSAML and Modules cache clearing completed successfully!');

        return Command::SUCCESS;
    }

    private function isNfs(string $dir): bool
    {
        static $mounts = null;

        if (null === $mounts) {
            $mounts = [];
            if (
                '/' === \DIRECTORY_SEPARATOR
                && @is_readable('/proc/mounts') && $files = @file('/proc/mounts')
            ) {
                foreach ($files as $mount) {
                    $mount = \array_slice(explode(' ', $mount), 1, -3);
                    if (!\in_array(array_pop($mount), ['vboxsf', 'nfs'])) {
                        continue;
                    }
                    $mounts[] = implode(' ', $mount) . '/';
                }
            }
        }
        foreach ($mounts as $mount) {
            if (str_starts_with($dir, $mount)) {
                return true;
            }
        }

        return false;
    }

    private function warmup(string $warmupDir, string $realBuildDir): void
    {
        // create a temporary kernel
        $kernel = $this->getApplication()->getKernel();
        if (!$kernel instanceof RebootableInterface) {
            $throwMessage = 'Calling "cache:clear" with a kernel that does not implement '
            . '"Symfony\Component\HttpKernel\RebootableInterface" is not supported.';
            throw new \LogicException($throwMessage);
        }
        $kernel->reboot($warmupDir);
    }

    private function warmupOptionals(string $cacheDir, string $warmupDir, SymfonyStyle $io): void
    {
        $kernel = $this->getApplication()->getKernel();
        $warmer = $kernel->getContainer()->get('cache_warmer');
        // non optional warmers already ran during container compilation
        $warmer->enableOnlyOptionalWarmers();
        $preload = (array) $warmer->warmUp($cacheDir, $warmupDir, $io);

        $preloadFile = $warmupDir
            . '/'
            . $kernel->getContainer()->getParameter('kernel.container_class')
            . '.preload.php';
        if ($preload && file_exists($preloadFile)) {
            Preloader::append($preloadFile, $preload);
        }
    }
}
