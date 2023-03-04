<?php

/**
 * The translation-relevant bits from our original minimalistic XHTML PHP based template system.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
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
     * The language object we'll use internally.
     *
     * @var \SimpleSAML\Locale\Language
     */
    private Language $language;


    /**
     * Constructor
     *
     * @param \SimpleSAML\Configuration $configuration Configuration object
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->language = new Language($configuration);
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
     * Mark a string for translation without translating it.
     *
     * @param string $tag A tag name to mark for translation.
     *
     * @return string The tag, unchanged.
     */
    public static function noop(string $tag): string
    {
        return $tag;
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

        $text = TranslatorFunctions::getTranslator()->gettext($original);

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
     * @param int $value
     *
     * @return string The translated string.
     */
    public static function translatePluralGettext(?string $original, string $plural, int $value): string
    {
        // This may happen if you forget to set a variable and then run undefinedVar through the trans-filter
        $original = $original ?? 'undefined variable';

        $text = TranslatorFunctions::getTranslator()->ngettext($original, $plural, $value);

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
        } elseif (isset($translations[Language::FALLBACKLANGUAGE])) {
            return $translations[Language::FALLBACKLANGUAGE];
        }

        // nothing we can use, return null so that we can set a default
        return null;
    }

    /**
     * Prefix tag
     *
     * @param string $tag Translation tag
     * @param string $prefix Prefix to be added
     *
     * @return string Prefixed tag
     */
    public static function addTagPrefix(string $tag, string $prefix): string
    {
        $tagPos = strrpos($tag, ':');
        // if tag contains ':' target actual tag
        $tagPos = ($tagPos === false) ? 0 : $tagPos + 1;
        // add prefix at $tagPos
        return substr_replace($tag, $prefix, $tagPos, 0);
    }
}
