<?php

/**
 * Wrap twig trans to allow us to override the translator used.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use Symfony\Contracts\Translation\TranslatorInterface;

class TwigTranslator implements TranslatorInterface
{
    private $translator;

    /**
     * @param callable|null $translator
     */
    public function __construct(callable $translator = null)
    {
        if (!is_callable($translator)) {
            $translator = fn($string) => gettext($string);
        }

        $this->translator = $translator;
    }

    /**
     * Translate message via configured translator.
     *
     * @param string $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     */
    public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null)
    {
        $this->locale = $locale;

        return call_user_func_array($this->translator, func_get_args());
    }
}
