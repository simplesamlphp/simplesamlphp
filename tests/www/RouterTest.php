<?php

namespace SimpleSAML\Test\Web;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class RouterTest extends TestCase
{
    /**
     * @return void
     */
    public function testSyntax()
    {
        $config = Configuration::loadFromArray([
            'module.enable' => array_fill_keys(Module::getModules(), true),
        ]);
        Configuration::setPreLoadedConfig($config);

        $yaml = new Parser();

        // Module templates
        foreach (Module::getModules() as $module) {
            $basedir = Module::getModuleDir($module);
            if (file_exists($basedir)) {
                $files = array_diff(scandir($basedir), ['.', '..']);
                foreach ($files as $file) {
                    if (preg_match('/.(yml|yaml)$/', $file)) {
                        try {
                            $value = $yaml->parse(file_get_contents('modules/' . $module . '/' . $file));
                            $this->addToAssertionCount(1);
                        } catch (ParseException $e) {
                            $this->fail($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                        }
                    }
                }
            }
        }
    }
}
