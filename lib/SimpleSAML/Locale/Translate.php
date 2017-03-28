<?php

/**
 * The translation-relevant bits from our original minimalistic XHTML PHP based template system.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

class Translate
{

    /**
     * The configuration to be used for this translator.
     *
     * @var \SimpleSAML_Configuration
     */
    private $configuration;

    private $langtext = array();

    /**
     * Associative array of dictionaries.
     */
    private $dictionaries = array();

    /**
     * The default dictionary.
     */
    private $defaultDictionary = null;

    /**
     * The language object we'll use internally.
     *
     * @var \SimpleSAML\Locale\Language
     */
    private $language;


    /**
     * Constructor
     *
     * @param \SimpleSAML_Configuration $configuration Configuration object
     * @param string|null               $defaultDictionary The default dictionary where tags will come from.
     */
    public function __construct(\SimpleSAML_Configuration $configuration, $defaultDictionary = null)
    {
        $this->configuration = $configuration;
        $this->language = new Language($configuration);

        if ($defaultDictionary !== null && substr($defaultDictionary, -4) === '.php') {
            // TODO: drop this entire if clause for 2.0
            // for backwards compatibility - print warning
            $backtrace = debug_backtrace();
            $where = $backtrace[0]['file'].':'.$backtrace[0]['line'];
            \SimpleSAML\Logger::warning(
                'Deprecated use of new SimpleSAML\Locale\Translate(...) at '.$where.
                '. The last parameter is now a dictionary name, which should not end in ".php".'
            );

            $this->defaultDictionary = substr($defaultDictionary, 0, -4);
        } else {
            $this->defaultDictionary = $defaultDictionary;
        }
    }


    /**
     * Return the internal language object used by this translator.
     *
     * @return \SimpleSAML\Locale\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * This method retrieves a dictionary with the name given.
     *
     * @param string $name The name of the dictionary, as the filename in the dictionary directory, without the
     * '.php' ending.
     *
     * @return array An associative array with the dictionary.
     */
    private function getDictionary($name)
    {
        assert('is_string($name)');

        if (!array_key_exists($name, $this->dictionaries)) {
            $sepPos = strpos($name, ':');
            if ($sepPos !== false) {
                $module = substr($name, 0, $sepPos);
                $fileName = substr($name, $sepPos + 1);
                $dictDir = \SimpleSAML\Module::getModuleDir($module).'/dictionaries/';
            } else {
                $dictDir = $this->configuration->getPathValue('dictionarydir', 'dictionaries/');
                $fileName = $name;
            }

            $this->dictionaries[$name] = $this->readDictionaryFile($dictDir.$fileName);
        }

        return $this->dictionaries[$name];
    }


    /**
     * This method retrieves a tag as an array with language => string mappings.
     *
     * @param string $tag The tag name. The tag name can also be on the form '{<dictionary>:<tag>}', to retrieve a tag
     * from the specific dictionary.
     *
     * @return array An associative array with language => string mappings, or null if the tag wasn't found.
     */
    public function getTag($tag)
    {
        assert('is_string($tag)');

        // first check translations loaded by the includeInlineTranslation and includeLanguageFile methods
        if (array_key_exists($tag, $this->langtext)) {
            return $this->langtext[$tag];
        }

        // check whether we should use the default dictionary or a dictionary specified in the tag
        if (substr($tag, 0, 1) === '{' && preg_match('/^{((?:\w+:)?\w+?):(.*)}$/D', $tag, $matches)) {
            $dictionary = $matches[1];
            $tag = $matches[2];
        } else {
            $dictionary = $this->defaultDictionary;
            if ($dictionary === null) {
                // we don't have any dictionary to load the tag from
                return null;
            }
        }

        $dictionary = $this->getDictionary($dictionary);
        if (!array_key_exists($tag, $dictionary)) {
            return null;
        }

        return $dictionary[$tag];
    }


    /**
     * Retrieve the preferred translation of a given text.
     *
     * @param array $translations The translations, as an associative array with language => text mappings.
     *
     * @return string The preferred translation.
     *
     * @throws \Exception If there's no suitable translation.
     */
    public function getPreferredTranslation($translations)
    {
        assert('is_array($translations)');

        // look up translation of tag in the selected language
        $selected_language = $this->language->getLanguage();
        if (array_key_exists($selected_language, $translations)) {
            return $translations[$selected_language];
        }

        // look up translation of tag in the default language
        $default_language = $this->language->getDefaultLanguage();
        if (array_key_exists($default_language, $translations)) {
            return $translations[$default_language];
        }

        // check for english translation
        if (array_key_exists('en', $translations)) {
            return $translations['en'];
        }

        // pick the first translation available
        if (count($translations) > 0) {
            $languages = array_keys($translations);
            return $translations[$languages[0]];
        }

        // we don't have anything to return
        throw new \Exception('Nothing to return from translation.');
    }


    /**
     * Translate the name of an attribute.
     *
     * @param string $name The attribute name.
     *
     * @return string The translated attribute name, or the original attribute name if no translation was found.
     */
    public function getAttributeTranslation($name)
    {
        // normalize attribute name
        $normName = strtolower($name);
        $normName = str_replace(":", "_", $normName);

        // check for an extra dictionary
        $extraDict = $this->configuration->getString('attributes.extradictionary', null);
        if ($extraDict !== null) {
            $dict = $this->getDictionary($extraDict);
            if (array_key_exists($normName, $dict)) {
                return $this->getPreferredTranslation($dict[$normName]);
            }
        }

        // search the default attribute dictionary
        $dict = $this->getDictionary('attributes');
        if (array_key_exists('attribute_'.$normName, $dict)) {
            return $this->getPreferredTranslation($dict['attribute_'.$normName]);
        }

        // no translations found
        return $name;
    }


    /**
     * Mark a string for translation without translating it.
     *
     * @param string  $tag A tag name to mark for translation.
     *
     * @return string The tag, unchanged.
     */
    public static function noop($tag)
    {
        return $tag;
    }


    /**
     * Translate a tag into the current language, with a fallback to english.
     *
     * This function is used to look up a translation tag in dictionaries, and return the translation into the current
     * language. If no translation into the current language can be found, english will be tried, and if that fails,
     * placeholder text will be returned.
     *
     * An array can be passed as the tag. In that case, the array will be assumed to be on the form (language => text),
     * and will be used as the source of translations.
     *
     * This function can also do replacements into the translated tag. It will search the translated tag for the keys
     * provided in $replacements, and replace any found occurrences with the value of the key.
     *
     * @param string|array $tag A tag name for the translation which should be looked up, or an array with
     * (language => text) mappings. The array version will go away in 2.0
     * @param array        $replacements An associative array of keys that should be replaced with values in the
     *     translated string.
     * @param boolean      $fallbackdefault Default translation to use as a fallback if no valid translation was found.
     * @deprecated Not used in twig, gettext
     *
     * @return string  The translated tag, or a placeholder value if the tag wasn't found.
     */
    public function t(
        $tag,
        $replacements = array(),
        $fallbackdefault = true, // TODO: remove this for 2.0. Assume true
        $oldreplacements = array(), // TODO: remove this for 2.0
        $striptags = false // TODO: remove this for 2.0
    ) {
        $backtrace = debug_backtrace();
        $where = $backtrace[0]['file'].':'.$backtrace[0]['line'];
        if (!$fallbackdefault) {
            \SimpleSAML\Logger::warning(
                'Deprecated use of new SimpleSAML\Locale\Translate::t(...) at '.$where.
                '. This parameter will go away, the fallback will become' .
                ' identical to the $tag in 2.0.'
            );
        }
        if (!is_array($replacements)) {
            // TODO: remove this entire if for 2.0

            // old style call to t(...). Print warning to log
            \SimpleSAML\Logger::warning(
                'Deprecated use of SimpleSAML\Locale\Translate::t(...) at '.$where.
                '. Please update the code to use the new style of parameters.'
            );

            // for backwards compatibility
            if (!$replacements && $this->getTag($tag) === null) {
                \SimpleSAML\Logger::warning(
                    'Code which uses $fallbackdefault === FALSE should be updated to use the getTag() method instead.'
                );
                return null;
            }

            $replacements = $oldreplacements;
        }

        if (is_array($tag)) {
            $tagData = $tag;
            \SimpleSAML\Logger::warning(
                'Deprecated use of new SimpleSAML\Locale\Translate::t(...) at '.$where.
                '. The $tag-parameter can only be a string in 2.0.'
            );
        } else {
            $tagData = $this->getTag($tag);
            if ($tagData === null) {
                // tag not found
                \SimpleSAML\Logger::info('Template: Looking up ['.$tag.']: not translated at all.');
                return $this->getStringNotTranslated($tag, $fallbackdefault);
            }
        }

        $translated = $this->getPreferredTranslation($tagData);

        foreach ($replacements as $k => $v) {
            // try to translate if no replacement is given
            if ($v == null) {
                $v = $this->t($k);
            }
            $translated = str_replace($k, $v, $translated);
        }
        return $translated;
    }


    /**
     * Return the string that should be used when no translation was found.
     *
     * @param string  $tag A name tag of the string that should be returned.
     * @param boolean $fallbacktag If set to true and string was not found in any languages, return the tag itself. If
     * false return null.
     *
     * @return string The string that should be used, or the tag name if $fallbacktag is set to false.
     */
    private function getStringNotTranslated($tag, $fallbacktag)
    {
        if ($fallbacktag) {
            return 'not translated ('.$tag.')';
        } else {
            return $tag;
        }
    }


    /**
     * Include a translation inline instead of putting translations in dictionaries. This function is recommended to be
     * used ONLU from variable data, or when the translation is already provided by an external source, as a database
     * or in metadata.
     *
     * @param string       $tag The tag that has a translation
     * @param array|string $translation The translation array
     *
     * @throws \Exception If $translation is neither a string nor an array.
     */
    public function includeInlineTranslation($tag, $translation)
    {
        if (is_string($translation)) {
            $translation = array('en' => $translation);
        } elseif (!is_array($translation)) {
            throw new \Exception("Inline translation should be string or array. Is ".gettype($translation)." now!");
        }

        \SimpleSAML\Logger::debug('Template: Adding inline language translation for tag ['.$tag.']');
        $this->langtext[$tag] = $translation;
    }


    /**
     * Include a language file from the dictionaries directory.
     *
     * @param string                         $file File name of dictionary to include
     * @param \SimpleSAML_Configuration|null $otherConfig Optionally provide a different configuration object than the
     * one provided in the constructor to be used to find the directory of the dictionary. This allows to combine
     * dictionaries inside the SimpleSAMLphp main code distribution together with external dictionaries. Defaults to
     * null.
     */
    public function includeLanguageFile($file, $otherConfig = null)
    {
        if (!empty($otherConfig)) {
            $filebase = $otherConfig->getPathValue('dictionarydir', 'dictionaries/');
        } else {
            $filebase = $this->configuration->getPathValue('dictionarydir', 'dictionaries/');
        }

        $lang = $this->readDictionaryFile($filebase.$file);
        \SimpleSAML\Logger::debug('Template: Merging language array. Loading ['.$file.']');
        $this->langtext = array_merge($this->langtext, $lang);
    }


    /**
     * Read a dictionary file in JSON format.
     *
     * @param string $filename The absolute path to the dictionary file, minus the .definition.json ending.
     *
     * @return array An array holding all the translations in the file.
     */
    private function readDictionaryJSON($filename)
    {
        $definitionFile = $filename.'.definition.json';
        assert('file_exists($definitionFile)');

        $fileContent = file_get_contents($definitionFile);
        $lang = json_decode($fileContent, true);

        if (empty($lang)) {
            \SimpleSAML\Logger::error('Invalid dictionary definition file ['.$definitionFile.']');
            return array();
        }

        $translationFile = $filename.'.translation.json';
        if (file_exists($translationFile)) {
            $fileContent = file_get_contents($translationFile);
            $moreTrans = json_decode($fileContent, true);
            if (!empty($moreTrans)) {
                $lang = array_merge_recursive($lang, $moreTrans);
            }
        }

        return $lang;
    }


    /**
     * Read a dictionary file in PHP format.
     *
     * @param string $filename The absolute path to the dictionary file.
     *
     * @return array An array holding all the translations in the file.
     */
    private function readDictionaryPHP($filename)
    {
        $phpFile = $filename.'.php';
        assert('file_exists($phpFile)');

        $lang = null;
        include($phpFile);
        if (isset($lang)) {
            return $lang;
        }

        return array();
    }


    /**
     * Read a dictionary file.
     *
     * @param string $filename The absolute path to the dictionary file.
     *
     * @return array An array holding all the translations in the file.
     */
    private function readDictionaryFile($filename)
    {
        assert('is_string($filename)');

        \SimpleSAML\Logger::debug('Template: Reading ['.$filename.']');

        $jsonFile = $filename.'.definition.json';
        if (file_exists($jsonFile)) {
            return $this->readDictionaryJSON($filename);
        }

        $phpFile = $filename.'.php';
        if (file_exists($phpFile)) {
            return $this->readDictionaryPHP($filename);
        }

        \SimpleSAML\Logger::error(
            $_SERVER['PHP_SELF'].' - Template: Could not find dictionary file at ['.$filename.']'
        );
        return array();
    }


    public static function translateSingularGettext($original)
    {
        $text = \Gettext\BaseTranslator::$current->gettext($original);

        if (func_num_args() === 1) {
            return $text;
        }

        $args = array_slice(func_get_args(), 1);

        return strtr($text, is_array($args[0]) ? $args[0] : $args);
    }


    public static function translatePluralGettext($original, $plural, $value)
    {
        $text = \Gettext\BaseTranslator::$current->ngettext($original, $plural, $value);

        if (func_num_args() === 3) {
            return $text;
        }

        $args = array_slice(func_get_args(), 3);

        return strtr($text, is_array($args[0]) ? $args[0] : $args);
    }
}
