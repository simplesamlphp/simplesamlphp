<?php

/**
 * The translation-relevant bits from our original minimalistic XHTML PHP based template system.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Hanne Moa, UNINETT AS. <hanne.moa@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Locale;

class Translate {

    private $configuration = null;

    private $langtext = array();


    /**
     * Associative array of dictionaries.
     */
    private $dictionaries = array();


    /**
     * The default dictionary.
     */
    private $defaultDictionary = NULL;


    /**
     * Constructor
     *
     * @param $configuration   Configuration object
     * @param $defaultDictionary  The default dictionary where tags will come from.
     */
    function __construct(\SimpleSAML_Configuration $configuration, $defaultDictionary = NULL) {
        $this->configuration = $configuration;
        $this->language = new Language($configuration);

        if($defaultDictionary !== NULL && substr($defaultDictionary, -4) === '.php') {
            /* For backwards compatibility - print warning. */
            $backtrace = debug_backtrace();
            $where = $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
            \SimpleSAML_Logger::warning('Deprecated use of new SimpleSAML\Locale\Translate(...) at ' . $where .
                '. The last parameter is now a dictionary name, which should not end in ".php".');

            $this->defaultDictionary = substr($defaultDictionary, 0, -4);
        } else {
            $this->defaultDictionary = $defaultDictionary;
        }
    }


    /**
     * Retrieve a dictionary.
     *
     * This function retrieves a dictionary with the given name.
     *
     * @param $name  The name of the dictionary, as the filename in the dictionary directory,
     *               without the '.php'-ending.
     * @return  An associative array with the dictionary.
     */
    private function getDictionary($name) {
        assert('is_string($name)');

        if(!array_key_exists($name, $this->dictionaries)) {
            $sepPos = strpos($name, ':');
            if($sepPos !== FALSE) {
                $module = substr($name, 0, $sepPos);
                $fileName = substr($name, $sepPos + 1);
                $dictDir = \SimpleSAML_Module::getModuleDir($module) . '/dictionaries/';
            } else {
                $dictDir = $this->configuration->getPathValue('dictionarydir', 'dictionaries/');
                $fileName = $name;
            }

            $this->dictionaries[$name] = $this->readDictionaryFile($dictDir . $fileName);
        }

        return $this->dictionaries[$name];
    }


    /**
     * Retrieve a tag.
     *
     * This function retrieves a tag as an array with language => string mappings.
     *
     * @param $tag  The tag name. The tag name can also be on the form '{<dictionary>:<tag>}', to retrieve
     *              a tag from the specific dictionary.
     * @return As associative array with language => string mappings, or NULL if the tag wasn't found.
     */
    public function getTag($tag) {
        assert('is_string($tag)');

        /* First check translations loaded by the includeInlineTranslation and includeLanguageFile methods. */
        if(array_key_exists($tag, $this->langtext)) {
            return $this->langtext[$tag];
        }

        /* Check whether we should use the default dictionary or a dictionary specified in the tag. */
        if(substr($tag, 0, 1) === '{' && preg_match('/^{((?:\w+:)?\w+?):(.*)}$/D', $tag, $matches)) {
            $dictionary = $matches[1];
            $tag = $matches[2];
        } else {
            $dictionary = $this->defaultDictionary;
            if($dictionary === NULL) {
                /* We don't have any dictionary to load the tag from. */
                return NULL;
            }
        }

        $dictionary = $this->getDictionary($dictionary);
        if(!array_key_exists($tag, $dictionary)) {
            return NULL;
        }

        return $dictionary[$tag];
    }


    /**
     * Retrieve the preferred translation of a given text.
     *
     * @param $translations  The translations, as an associative array with language => text mappings.
     * @return The preferred translation.
     */
    public function getPreferredTranslation($translations) {
        assert('is_array($translations)');

        /* Look up translation of tag in the selected language. */
        $selected_language = $this->language->getLanguage();
        if (array_key_exists($selected_language, $translations)) {
            return $translations[$selected_language];
        }

        /* Look up translation of tag in the default language. */
        $default_language = $this->language->getDefaultLanguage();
        if(array_key_exists($default_language, $translations)) {
            return $translations[$default_language];
        }

        /* Check for english translation. */
        if(array_key_exists('en', $translations)) {
            return $translations['en'];
        }

        /* Pick the first translation available. */
        if(count($translations) > 0) {
            $languages = array_keys($translations);
            return $translations[$languages[0]];
        }

        /* We don't have anything to return. */
        throw new Exception('Nothing to return from translation.');
    }


    /**
     * Translate a attribute name.
     *
     * @param string $name  The attribute name.
     * @return string  The translated attribute name, or the original attribute name if no translation was found.
     */
    public function getAttributeTranslation($name) {

        /* Normalize attribute name. */
        $normName = strtolower($name);
        $normName = str_replace(":", "_", $normName);

        /* Check for an extra dictionary. */
        $extraDict = $this->configuration->getString('attributes.extradictionary', NULL);
        if ($extraDict !== NULL) {
            $dict = $this->getDictionary($extraDict);
            if (array_key_exists($normName, $dict)) {
                return $this->getPreferredTranslation($dict[$normName]);
            }
        }

        /* Search the default attribute dictionary. */
        $dict = $this->getDictionary('attributes');
        if (array_key_exists('attribute_' . $normName, $dict)) {
            return $this->getPreferredTranslation($dict['attribute_' . $normName]);
        }

        /* No translations found. */
        return $name;
    }


    /**
     * Translate a tag into the current language, with a fallback to english.
     *
     * This function is used to look up a translation tag in dictionaries, and return the
     * translation into the current language. If no translation into the current language can be
     * found, english will be tried, and if that fails, placeholder text will be returned.
     *
     * An array can be passed as the tag. In that case, the array will be assumed to be on the
     * form (language => text), and will be used as the source of translations.
     *
     * This function can also do replacements into the translated tag. It will search the
     * translated tag for the keys provided in $replacements, and replace any found occurances
     * with the value of the key.
     *
     * @param string|array $tag  A tag name for the translation which should be looked up, or an
     *                           array with (language => text) mappings.
     * @param array $replacements  An associative array of keys that should be replaced with
     *                             values in the translated string.
     * @return string  The translated tag, or a placeholder value if the tag wasn't found.
     */
    public function t($tag, $replacements = array(), $fallbackdefault = true, $oldreplacements = array(), $striptags = FALSE) {
        if(!is_array($replacements)) {

            /* Old style call to t(...). Print warning to log. */
            $backtrace = debug_backtrace();
            $where = $backtrace[0]['file'] . ':' . $backtrace[0]['line'];
            \SimpleSAML_Logger::warning('Deprecated use of SimpleSAML_Template::t(...) at ' . $where .
                '. Please update the code to use the new style of parameters.');

            /* For backwards compatibility. */
            if(!$replacements && $this->getTag($tag) === NULL) {
                \SimpleSAML_Logger::warning('Code which uses $fallbackdefault === FALSE shouls be' .
                    ' updated to use the getTag-method instead.');
                return NULL;
            }

            $replacements = $oldreplacements;
        }

        if(is_array($tag)) {
            $tagData = $tag;
        } else {
            $tagData = $this->getTag($tag);
            if($tagData === NULL) {
                /* Tag not found. */
                \SimpleSAML_Logger::info('Template: Looking up [' . $tag . ']: not translated at all.');
                return $this->t_not_translated($tag, $fallbackdefault);
            }
        }

        $translated = $this->getPreferredTranslation($tagData);

#        if (!empty($replacements)){        echo('<pre> [' . $tag . ']'); print_r($replacements); exit; }
        foreach ($replacements as $k => $v) {
            /* try to translate if no replacement is given */
            if ($v == NULL) $v = $this->t($k);
            $translated = str_replace($k, $v, $translated);
        }
        return $translated;
    }

    /**
     * Return the string that should be used when no translation was found.
     *
     * @param $tag                A name tag of the string that should be returned.
     * @param $fallbacktag        If set to TRUE and string was not found in any languages, return
     *                     the tag it self. If FALSE return NULL.
     */
    private function t_not_translated($tag, $fallbacktag) {
        if ($fallbacktag) {
            return 'not translated (' . $tag . ')';
        } else {
            return $tag;
        }
    }


    /**
     * You can include translation inline instead of putting translation
     * in dictionaries. This function is reccomended to only be used from dynamic
     * data, or when the translation is already provided from an external source, as
     * a database or in metadata.
     *
     * @param $tag         The tag that has a translation
     * @param $translation The translation array
     */
    public function includeInlineTranslation($tag, $translation) {

        if (is_string($translation)) {
            $translation = array('en' => $translation);
        } elseif (!is_array($translation)) {
            throw new Exception("Inline translation should be string or array. Is " . gettype($translation) . " now!");
        }

        \SimpleSAML_Logger::debug('Template: Adding inline language translation for tag [' . $tag . ']');
        $this->langtext[$tag] = $translation;
    }

    /**
     * Include language file from the dictionaries directory.
     *
     * @param $file         File name of dictionary to include
     * @param $otherConfig  Optionally provide a different configuration object than
     *  the one provided in the constructor to be used to find the dictionary directory.
     *  This enables the possiblity of combining dictionaries inside simpleSAMLphp
     *  distribution with external dictionaries.
     */
    public function includeLanguageFile($file, $otherConfig = null) {

        $filebase = null;
        if (!empty($otherConfig)) {
            $filebase = $otherConfig->getPathValue('dictionarydir', 'dictionaries/');
        } else {
            $filebase = $this->configuration->getPathValue('dictionarydir', 'dictionaries/');
        }


        $lang = $this->readDictionaryFile($filebase . $file);
        \SimpleSAML_Logger::debug('Template: Merging language array. Loading [' . $file . ']');
        $this->langtext = array_merge($this->langtext, $lang);
    }


    /**
     * Read a dictionary file in json format.
     *
     * @param string $filename  The absolute path to the dictionary file, minus the .definition.json ending.
     * @return array  The translation array from the file.
     */
    private function readDictionaryJSON($filename) {
        $definitionFile = $filename . '.definition.json';
        assert('file_exists($definitionFile)');

        $fileContent = file_get_contents($definitionFile);
        $lang = json_decode($fileContent, TRUE);

        if (empty($lang)) {
            \SimpleSAML_Logger::error('Invalid dictionary definition file [' . $definitionFile . ']');
            return array();
        }

        $translationFile = $filename . '.translation.json';
        if (file_exists($translationFile)) {
            $fileContent = file_get_contents($translationFile);
            $moreTrans = json_decode($fileContent, TRUE);
            if (!empty($moreTrans)) {
                $lang = self::lang_merge($lang, $moreTrans);
            }
        }

        return $lang;
    }


    /**
     * Read a dictionary file in PHP format.
     *
     * @param string $filename  The absolute path to the dictionary file.
     * @return array  The translation array from the file.
     */
    private function readDictionaryPHP($filename) {
        $phpFile = $filename . '.php';
        assert('file_exists($phpFile)');

        $lang = NULL;
        include($phpFile);
        if (isset($lang)) {
            return $lang;
        }

        return array();
    }


    /**
     * Read a dictionary file.
     *
     * @param $filename  The absolute path to the dictionary file.
     * @return The translation array which was found in the dictionary file.
     */
    private function readDictionaryFile($filename) {
        assert('is_string($filename)');

        \SimpleSAML_Logger::debug('Template: Reading [' . $filename . ']');

        $jsonFile = $filename . '.definition.json';
        if (file_exists($jsonFile)) {
            return $this->readDictionaryJSON($filename);
        }


        $phpFile = $filename . '.php';
        if (file_exists($phpFile)) {
            return $this->readDictionaryPHP($filename);
        }

        \SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - Template: Could not find template file [' . $this->template . '] at [' . $filename . ']');
        return array();
    }


    // Merge two translation arrays.
    public static function lang_merge($def, $lang) {
        foreach($def AS $key => $value) {
            if (array_key_exists($key, $lang))
                $def[$key] = array_merge($value, $lang[$key]);
        }
        return $def;
    }


}
