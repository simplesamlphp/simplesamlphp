<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\XHTML\IdPDisco;

/**
 * Tests for the IdPDisco class.
 *
 * For the full copyright and license information, please view the LICENSE file that was
 * distributed with this source code.
 *
 * @author Tim van Dijen <tvdijen@gmail.com>
 * @package simplesamlphp/simplesamlphp
 */
class IdPDiscoTest extends TestCase
{
    /** @var \SimpleSAML\Metadata\MetaDataStorageHandler */
    private $mhandler;

    /** @var array */
    private $config = [
        'language' => [
            'priorities' => [
                'nl' => ['nl', 'en', 'de'],
                'es' => ['es', 'fr', 'cz'],
            ],
        ],
        'language.default' => 'nl',

        'metadata.sources' => [
            [
                'type' => 'xml',
                'file' => 'vendor/simplesamlphp/simplesamlphp-test-framework/metadata/xml/unsigned-metadata.xml'
            ]
        ],
    ];


    /**
     * @return void
     */
    public function setUp(): void
    {
        $config = Configuration::loadFromArray($this->config);

        Configuration::setPreloadedConfig($config, 'config.php');

        $this->mhandler = MetaDataStorageHandler::getMetadataHandler();
    }


    /**
     */
    public function testPreferredTranslation(): void
    {
        $disco = new IdPDisco(['saml20-idp-remote'], 'unittest');

        $reflection = new \ReflectionClass(get_class($disco));
        $method = $reflection->getMethod('getPreferredTranslations');
        $method->setAccessible(true);

        $idpList = $method->invokeArgs($disco, $idpList);

        // Assert that result comes with the right translation based on default languages and available translations
    }


    /**
     */
    public function testIdPSortingOrder(): void
    {
        $disco = new IdPDisco(['saml20-idp-remote'], 'unittest');

        $reflection = new \ReflectionClass(get_class($disco));
        $method = $reflection->getMethod('getPreferredTranslations');
        $method->setAccessible(true);

        $idpList = $method->invokeArgs($disco, $idpList);

        // Assert that result comes alphabetically ordered, whatever language or translation is used
    }
}
