<?php

namespace SimpleSAML\Console;

use SimpleSAML\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application extends BaseApplication
{
    /**
     * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
     */
    public function __construct(HttpKernelInterface $kernel)
    {
        parent::__construct($kernel, Kernel::VERSION);

        $inputDefinition = $this->getDefinition();
        $inputDefinition->addOption(
            new InputOption('--module', '-m', InputOption::VALUE_REQUIRED, 'The module name', $kernel->getModule())
        );
    }
}
