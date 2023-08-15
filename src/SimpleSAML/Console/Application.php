<?php

declare(strict_types=1);

namespace SimpleSAML\Console;

use SimpleSAML\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * @param \SimpleSAML\Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        parent::__construct($kernel);
    }
}
