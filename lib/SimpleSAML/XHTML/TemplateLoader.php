<?php


namespace SimpleSAML\XHTML;

/**
 * This class extends the Twig\Loader\FilesystemLoader so that we can load templates from modules in twig, even
 * when the main template is not part of a module (or the same one).
 *
 * @package simplesamlphp/simplesamlphp
 */
class TemplateLoader extends \Twig\Loader\FilesystemLoader
{
    /**
     * This method adds a namespace dynamically so that we can load templates from modules whenever we want.
     *
     * @inheritdoc
     */
    protected function findTemplate($name, $throw = true)
    {
        list($namespace, $shortname) = $this->parseModuleName($name);
        if (!in_array($namespace, $this->paths, true) && $namespace !== self::MAIN_NAMESPACE) {
            $this->addPath(self::getModuleTemplateDir($namespace), $namespace);
        }
        return parent::findTemplate($name, $throw);
    }


    /**
     * Parse the name of a template in a module.
     *
     * @param string $name The full name of the template, including namespace and template name / path.
     *
     * @return array An array with the corresponding namespace and name of the template. The namespace defaults to
     * \Twig\Loader\FilesystemLoader::MAIN_NAMESPACE, if none was specified in $name.
     */
    protected function parseModuleName($name, $default = self::MAIN_NAMESPACE)
    {
        if (strpos($name, ':')) {
            // we have our old SSP format
            list($namespace, $shortname) = explode(':', $name, 2);
            $shortname = strtr($shortname, [
                '.tpl.php' => '.twig',
                '.php' => '.twig',
            ]);
            return [$namespace, $shortname];
        }
        return [$default, $name];
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
