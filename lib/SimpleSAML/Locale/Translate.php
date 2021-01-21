<?php

/**
 * The translation-relevant bits from our original minimalistic XHTML PHP based template system.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use Gettext\BaseTranslator;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module;

class Translate
{
    /**
     * The configuration to be used for this translator.
     *
     * @var \SimpleSAML\Configuration
     */
    private Configuration $configuration;

    /**
     * Associative array of languages.
     *
     * @var array
     */
    private array $langtext = [];

    /**
     * Associative array of dictionaries.
     *
     * @var array
     */
    private array $dictionaries = [];

    /**
     * The default dictionary.
     *
     * @var string|null
     */
    private ?string $defaultDictionary = null;

    /**
     * The language object we'll use internally.
     *
     * @var \SimpleSAML\Locale\Language
     */
    private Language $language;


    /**
     * Constructor
     *
     * @param \SimpleSAML\Configuration $configuration Configuration object
     * @param string|null               $defaultDictionary The default dictionary where tags will come from.
     */
    public function __construct(Configuration $configuration, ?string $defaultDictionary = null)
    {
        $this->configuration = $configuration;
        $this->language = new Language($configuration);
        $this->defaultDictionary = $defaultDictionary;
    }


    /**
     * Return the internal language object used by this translator.
     *
     * @return \SimpleSAML\Locale\Language
     */
    public function getLanguage(): Language
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
    private function getDictionary(string $name): array
    {
        if (!array_key_exists($name, $this->dictionaries)) {
            $sepPos = strpos($name, ':');
            if ($sepPos !== false) {
                $module = substr($name, 0, $sepPos);
                $fileName = substr($name, $sepPos + 1);
                $dictDir = Module::getModuleDir($module) . '/dictionaries/';
            } else {
                $dictDir = $this->configuration->getPathValue('dictionarydir', 'dictionaries/') ?: 'dictionaries/';
                $fileName = $name;
            }

            $this->dictionaries[$name] = $this->readDictionaryFile($dictDir . $fileName);
        }

        return $this->dictionaries[$name];
    }


    /**
     * This method retrieves a tag as an array with language => string mappings.
     *
     * @param string $tag The tag name. The tag name can also be on the form '{<dictionary>:<tag>}', to retrieve a tag
     * from the specific dictionary.
     *
     * @return array|null An associative array with language => string mappings, or null if the tag wasn't found.
     */
    public function getTag(string $tag): ?array
    {
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
    public function getPreferredTranslation(array $translations): string
    {
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
    public function getAttributeTranslation(string $name): string
    {
        // normalize attribute name
        $normName = strtolower($name);
        $normName = str_replace([":", "-"], "_", $normName);

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
        if (array_key_exists('attribute_' . $normName, $dict)) {
            return $this->getPreferredTranslation($dict['attribute_' . $normName]);
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
    public static function noop(string $tag): string
    {
        return $tag;
    }


    /**
     * Include a translation inline instead of putting translations in dictionaries. This function is recommended to be
     * used ONLY for variable data, or when the translation is already provided by an external source, as a database
     * or in metadata.
     *
     * @param string $tag The tag that has a translation
     * @param mixed  $translation The translation array
     *
     * @throws \Exception If $translation is neither a string nor an array.
     */
    public function includeInlineTranslation(string $tag, $translation): void
    {
        if (is_string($translation)) {
            $translation = ['en' => $translation];
        } elseif (!is_array($translation)) {
            throw new \Exception(
                "Inline translation should be string or array. Is " . gettype($translation) . " now!"
            );
        }

        Logger::debug('Translate: Adding inline language translation for tag [' . $tag . ']');
        $this->langtext[$tag] = $translation;
    }


    /**
     * Include a language file from the dictionaries directory.
     *
     * @param string                         $file File name of dictionary to include
     * @param \SimpleSAML\Configuration|null $otherConfig Optionally provide a different configuration object than the
     * one provided in the constructor to be used to find the directory of the dictionary. This allows to combine
     * dictionaries inside the SimpleSAMLphp main code distribution together with external dictionaries. Defaults to
     * null.
     */
    public function includeLanguageFile(string $file, Configuration $otherConfig = null): void
    {
        if (!empty($otherConfig)) {
            $filebase = $otherConfig->getPathValue('dictionarydir', 'dictionaries/');
        } else {
            $filebase = $this->configuration->getPathValue('dictionarydir', 'dictionaries/');
        }
        $filebase = $filebase ?: 'dictionaries/';

        $lang = $this->readDictionaryFile($filebase . $file);
        Logger::debug('Translate: Merging language array. Loading [' . $file . ']');
        $this->langtext = array_merge($this->langtext, $lang);
    }


    /**
     * Read a dictionary file in JSON format.
     *
     * @param string $filename The absolute path to the dictionary file, minus the .definition.json ending.
     *
     * @return array An array holding all the translations in the file.
     */
    private function readDictionaryJSON(string $filename): array
    {
        $definitionFile = $filename . '.definition.json';
        Assert::true(file_exists($definitionFile));

        $fileContent = file_get_contents($definitionFile);
        $lang = json_decode($fileContent, true);

        if (empty($lang)) {
            Logger::error('Invalid dictionary definition file [' . $definitionFile . ']');
            return [];
        }

        $translationFile = $filename . '.translation.json';
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
    private function readDictionaryPHP(string $filename): array
    {
        $phpFile = $filename . '.php';
        Assert::true(file_exists($phpFile));

        $lang = null;
        include($phpFile);
        /** @psalm-var array|null $lang */
        if (isset($lang)) {
            return $lang;
        }

        return [];
    }


    /**
     * Read a dictionary file.
     *
     * @param string $filename The absolute path to the dictionary file.
     *
     * @return array An array holding all the translations in the file.
     */
    private function readDictionaryFile(string $filename): array
    {
        Logger::debug('Translate: Reading dictionary [' . $filename . ']');

        $jsonFile = $filename . '.definition.json';
        if (file_exists($jsonFile)) {
            return $this->readDictionaryJSON($filename);
        }

        $phpFile = $filename . '.php';
        if (file_exists($phpFile)) {
            return $this->readDictionaryPHP($filename);
        }

        Logger::error(
            $_SERVER['PHP_SELF'] . ' - Translate: Could not find dictionary file at [' . $filename . ']'
        );
        return [];
    }


    /**
     * Translate a singular text.
     *
     * @param string|null $original The string before translation.
     *
     * @return string The translated string.
     */
    public static function translateSingularGettext(?string $original): string
    {
        // This may happen if you forget to set a variable and then run undefinedVar through the trans-filter
        $original = $original ?? 'undefined variable';

        $text = BaseTranslator::$current->gettext($original);

        if (func_num_args() === 1) {
            return $text;
        }

        $args = array_slice(func_get_args(), 1);

        return strtr($text, is_array($args[0]) ? $args[0] : $args);
    }


    /**
     * Translate a plural text.
     *
     * @param string|null $original The string before translation.
     * @param string $plural
     * @param string $value
     *
     * @return string The translated string.
     */
    public static function translatePluralGettext(?string $original, string $plural, string $value): string
    {
        // This may happen if you forget to set a variable and then run undefinedVar through the trans-filter
        $original = $original ?? 'undefined variable';

        $text = BaseTranslator::$current->ngettext($original, $plural, $value);

        if (func_num_args() === 3) {
            return $text;
        }

        $args = array_slice(func_get_args(), 3);

        return strtr($text, is_array($args[0]) ? $args[0] : $args);
    }


    /**
     * Pick a translation from a given array of translations for the current language.
     *
     * @param array|null $context An array of options. The current language must be specified
     *     as an ISO 639 code accessible with the key "currentLanguage" in the array.
     * @param array|null $translations An array of translations. Each translation has an
     *     ISO 639 code as its key, identifying the language it corresponds to.
     *
     * @return null|string The translation appropriate for the current language, or null if none found. If the
     * $context or $translations arrays are null, or $context['currentLanguage'] is not defined, null is also returned.
     */
    public static function translateFromArray(?array $context, ?array $translations): ?string
    {
        if (!is_array($translations)) {
            return null;
        } elseif (!is_array($context) || !isset($context['currentLanguage'])) {
            return null;
        } elseif (isset($translations[$context['currentLanguage']])) {
            return $translations[$context['currentLanguage']];
        }

        // we don't have a translation for the current language, load alternative priorities
        $sspcfg = Configuration::getInstance();
        /** @psalm-var \SimpleSAML\Configuration $langcfg */
        $langcfg = $sspcfg->getConfigItem('language');
        $priorities = $langcfg->getArray('priorities', []);

        if (!empty($priorities[$context['currentLanguage']])) {
            foreach ($priorities[$context['currentLanguage']] as $lang) {
                if (isset($translations[$lang])) {
                    return $translations[$lang];
                }
            }
        }

        // nothing we can use, return null so that we can set a default
        return null;
    }
}
