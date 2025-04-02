<?php

/**
 * Wrap twig trans to allow us to override the translator used.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Locale;

use Symfony\Contracts\Translation\TranslatorInterface;

use function call_user_func_array;

class TwigTranslator implements TranslatorInterface
{
    /** @var string|null $locale */
    private ?string $locale = null;

    /** @var callable $translator */
    private $translator;

    /**
     * @param callable $translator
     */
    public function __construct(callable $translator)
    {
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
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $this->locale = $locale;

        return call_user_func_array($this->translator, func_get_args());
    }

    /**
     * Returns the default locale.
     */
    public function getLocale(): string
    {
        return Language::FALLBACKLANGUAGE;
    }
}
