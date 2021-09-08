<?php

return [
    // Don't remove this bundles
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    // Debug bundle
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    // Add modules below this line
    \SimpleSAML\Modules\ExpiryCheckModule\ExpiryCheckModule::class => ['dev' => true],
];
