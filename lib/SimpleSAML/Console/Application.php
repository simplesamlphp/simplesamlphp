<?php

declare(strict_types=1);

namespace SimpleSAML\Console;

use SimpleSAML\ModuleKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication
{
    /**
     * @param \SimpleSAML\ModuleKernel $kernel
     */
    public function __construct(ModuleKernel $kernel)
    {
        parent::__construct($kernel);

        $inputDefinition = $this->getDefinition();
        $inputDefinition->addOption(
            new InputOption('--module', '-m', InputOption::VALUE_REQUIRED, 'The module name', $kernel->getModule())
        );
    }
}
