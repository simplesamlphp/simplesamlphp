<?php


namespace SimpleSAML\XHTML;


class TemplateLoader extends \Twig\Loader\FilesystemLoader
{
    /**
     * This method adds a namespace dynamically so that we can load templates from modules whenever we want.
     *
     * @inheritdoc
     */
    protected function findTemplate($name)
    {
        list($namespace, $shortname) = $this->parseName($name);
        if (!in_array($namespace, $this->paths, true) && $namespace !== self::MAIN_NAMESPACE) {
            $this->addPath(self::getModuleTemplateDir($namespace), $namespace);
        }
        return parent::findTemplate($name);
    }

    /**
     * Get the template directory of a module, if it exists.
     *
     * @return string The templates directory of a module.
     *
     * @throws \InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    public static function getModuleTemplateDir($module)
    {
        if (!\SimpleSAML\Module::isModuleEnabled($module)) {
            throw new \InvalidArgumentException('The module \''.$module.'\' is not enabled.');
        }
        $moduledir = \SimpleSAML\Module::getModuleDir($module);
        // check if module has a /templates dir, if so, append
        $templatedir = $moduledir.'/templates';
        if (!is_dir($templatedir)) {
            throw new \InvalidArgumentException('The module \''.$module.'\' has no templates directory.');

        }
        return $templatedir;
    }
}