<?php

namespace SimpleSAML\Console;

use SimpleSAML\Kernel;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application extends BaseApplication
{
    /**
     * @var Kernel
     */
    private $kernel;

    public function __construct(HttpKernelInterface $kernel)
    {
        $this->kernel = $kernel;

        parent::__construct('SimpleSAML', Kernel::VERSION);

        $inputDefinition = $this->getDefinition();
        $inputDefinition->addOption(new InputOption('--module', '-m', InputOption::VALUE_REQUIRED, 'The module name', $kernel->getModule()));
    }

    /**
     * @return Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultCommands()
    {
        $this->kernel->boot();

        $container = $this->kernel->getContainer();
        if ($container->has('console.command_loader')) {
            $this->setCommandLoader($container->get('console.command_loader'));
        }

        return parent::getDefaultCommands();
    }
}
