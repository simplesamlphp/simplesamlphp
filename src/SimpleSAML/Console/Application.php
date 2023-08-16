<?php

declare(strict_types=1);

namespace SimpleSAML\Console;

use SimpleSAML\CommandLineKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * @param \SimpleSAML\CommandLineKernel $kernel
     */
    public function __construct(CommandLineKernel $kernel)
    {
        parent::__construct($kernel);
    }
}
