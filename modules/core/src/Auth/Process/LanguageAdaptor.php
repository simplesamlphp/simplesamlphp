<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Auth\Process;

use SimpleSAML\{Auth, Logger};
use SimpleSAML\Assert\Assert;
use SimpleSAML\Locale\Language;

use function array_key_exists;

/**
 * Filter to set and get language settings from attributes.
 *
 * @package SimpleSAMLphp
 */
class LanguageAdaptor extends Auth\ProcessingFilter
{
    /** @var string */
    private string $langattr = 'preferredLanguage';


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (array_key_exists('attributename', $config)) {
            $this->langattr = $config['attributename'];
        }
    }


    /**
     * Apply filter to add or replace attributes.
     *
     * Add or replace existing attributes with the configured values.
     *
     * @param array &$state  The current request
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');

        $attributes = &$state['Attributes'];

        $attrlang = null;
        if (array_key_exists($this->langattr, $attributes)) {
            $attrlang = $attributes[$this->langattr][0];
        }

        $lang = Language::getLanguageCookie();

        if (isset($attrlang)) {
            Logger::debug('LanguageAdaptor: Language in attribute was set [' . $attrlang . ']');
        }
        if (isset($lang)) {
            Logger::debug('LanguageAdaptor: Language in session was set [' . $lang . ']');
        }

        if (isset($attrlang) && !isset($lang)) {
            // Language set in attribute but not in cookie - update cookie
            Language::setLanguageCookie($attrlang);
        } elseif (!isset($attrlang) && isset($lang)) {
            // Language set in cookie, but not in attribute. Update attribute
            $state['Attributes'][$this->langattr] = [$lang];
        }
    }
}
