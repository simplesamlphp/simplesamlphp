<?php

declare(strict_types=1);

namespace SimpleSAML\XHTML;

use InvalidArgumentException;
use SimpleSAML\Module;

use function explode;
use function in_array;
use function is_dir;
use function strpos;

/**
 * This class extends the Twig\Loader\FilesystemLoader so that we can load templates from modules in twig, even
 * when the main template is not part of a module (or the same one).
 *
 * @package simplesamlphp/simplesamlphp
 *
 * @psalm-suppress DeprecatedInterface  This suppress may be removed when Twig 3.0 becomes the default
 */
class TemplateLoader extends \Twig\Loader\FilesystemLoader
{
    /**
     * This method adds a namespace dynamically so that we can load templates from modules whenever we want.
     *
     * {@inheritdoc}
     *
     * @param string $name
     * @param bool $throw
     * @return string|null
     *
     * NOTE: cannot typehint due to upstream restrictions
     */
    protected function findTemplate(string $name, bool $throw = true)
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
     * @param string $default
     *
     * @return array An array with the corresponding namespace and name of the template. The namespace defaults to
     * \Twig\Loader\FilesystemLoader::MAIN_NAMESPACE, if none was specified in $name.
     */
    protected function parseModuleName(string $name, string $default = self::MAIN_NAMESPACE): array
    {
        if (strpos($name, ':')) {
            // we have our old SSP format
            list($namespace, $shortname) = explode(':', $name, 2);
            return [$namespace, $shortname];
        }
        return [$default, $name];
    }


    /**
     * Get the template directory of a module, if it exists.
     *
     * @param string $module
     * @return string The templates directory of a module.
     *
     * @throws \InvalidArgumentException If the module is not enabled or it has no templates directory.
     */
    public static function getModuleTemplateDir(string $module): string
    {
        if (!Module::isModuleEnabled($module)) {
            throw new InvalidArgumentException('The module \'' . $module . '\' is not enabled.');
        }
        $moduledir = Module::getModuleDir($module);
        // check if module has a /templates dir, if so, append
        $templatedir = $moduledir . '/templates';
        if (!is_dir($templatedir)) {
            throw new InvalidArgumentException('The module \'' . $module . '\' has no templates directory.');
        }
        return $templatedir;
    }
}
