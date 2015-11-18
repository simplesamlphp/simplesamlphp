<?php
/**
 * Test for the metarefres:MetaLoader filter.
 */
class Test_Metarefresh_MetaLoader extends PHPUnit_Framework_TestCase
{

    /**
     * Test load metadata file with no filtering
     *
     */
    public function testLoadMetadataFile()
    {
        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        $this->verifyEntityPresent(true, $entities, 'urn:mace:incommon:osu.edu');
        $this->verifyEntityPresent(true, $entities, 'https://idp.nuim.ie/idp/shibboleth');
        $this->verifyEntityPresent(true, $entities, 'https://carmenwiki.osu.edu/shibboleth');
    }

    /**
     * Test load metadata file with blacklist
     *
     */
    public function testLoadMetadataFileWithBlacklist()
    {

        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
            'blacklist' => array('https://carmenwiki.osu.edu/shibboleth')
        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        $this->verifyEntityPresent(true, $entities, 'urn:mace:incommon:osu.edu');
        // This SP is blacklisted
        $this->verifyEntityPresent(false, $entities, 'https://carmenwiki.osu.edu/shibboleth');
    }

    /**
     * Test load metadata file with whitelist
     *
     */
    public function testLoadMetadataFileWithWhitelist()
    {

        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
            'whitelist' => array('https://carmenwiki.osu.edu/shibboleth')
        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        // This Idp is not whitelisted
        $this->verifyEntityPresent(false, $entities, 'urn:mace:incommon:osu.edu');
        $this->verifyEntityPresent(true, $entities, 'https://carmenwiki.osu.edu/shibboleth');
    }

    /**
     * Test load metadata file with callback
     *
     */
    public function testLoadMetadataFileWithCallback()
    {

        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
            'filterCallback' => 'Test_Metarefresh_MetaLoader::entityIdIsWiki'
        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        // This Idp doesn't match the filter
        $this->verifyEntityPresent(false, $entities, 'urn:mace:incommon:osu.edu');
        // Only Wikis match the filter
        $this->verifyEntityPresent(true, $entities, 'https://carmenwiki.osu.edu/shibboleth');
        $this->verifyEntityPresent(true, $entities, 'https://wiki.cac.washington.edu/');
        $this->verifyEntityPresent(false, $entities, 'https://beta.projecteuclid.org/shibboleth-sp');
    }

    public static function entityIdIsWiki(SimpleSAML_Metadata_SAMLParser $entityDesc) {
        return strpos($entityDesc->getEntityID(), 'wiki') !== false;
    }

    /**
     * Test load metadata file with invalid callback name
     *
     */
    public function testLoadMetadataFileWithBadCallback()
    {

        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
            'filterCallback' => 'invalid::reference'
        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        // Nothing makes it through an invalid filter
        $this->verifyEntityPresent(false, $entities, 'urn:mace:incommon:osu.edu');
        $this->verifyEntityPresent(false, $entities, 'https://carmenwiki.osu.edu/shibboleth');
    }

    /**
     * Test load metadata file with a factory generated callback that looks at entity attributes
     *
     */
    public function testLoadMetadataFileWithAttributeFilterFactory()
    {

        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
            'filterFactory' => 'sspmod_metarefresh_CommonFilters::entityAttributeFactory',
            'filterFactoryArgs' => array('http://macedir.org/entity-category',
                'http://refeds.org/category/research-and-scholarship')

        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        // Only sample entity with the correct category attribute name and value
        $this->verifyEntityPresent(true, $entities, 'https://carmenwiki.osu.edu/shibboleth');
        //osu.edu has the refeds category under a different name
        $this->verifyEntityPresent(false, $entities, 'urn:mace:incommon:osu.edu');
        $this->verifyEntityPresent(false, $entities, 'https://idp.nuim.ie/idp/shibboleth');

    }

    /**
     * Test load metadata file with function factory
     *
     */
    public function testLoadMetadataFileWithAuthorityFilterFactory()
    {

        $src = array(
            'src' =>  __DIR__ . '/metadata-sample.xml',
            'filterFactory' => 'sspmod_metarefresh_CommonFilters::registeredAuthorityFilterFactory',
            'filterFactoryArgs' => array('http://www.heanet.ie')
        );
        $loader = new sspmod_metarefresh_MetaLoader();
        $loader->loadSource($src);

        $entities = $loader->getMetadata();

        // Only heanet stuff makes it through the filter
        $this->verifyEntityPresent(false, $entities, 'urn:mace:incommon:osu.edu');
        $this->verifyEntityPresent(false, $entities, 'https://carmenwiki.osu.edu/shibboleth');
        $this->verifyEntityPresent(true, $entities, 'https://idp.nuim.ie/idp/shibboleth');

    }

    /**
     * Assert that the metadata has or doesn't have the entityId
     * @param $present
     * @param $metadata
     * @param $entityId
     */
    private function verifyEntityPresent($present, $metadata, $entityId)
    {
        $found = false;
        // Check both idp and sp
        $types = ['saml20-idp-remote', 'saml20-sp-remote'];
        foreach ($types as $type) {
            if (!isset($metadata[$type])) {
                continue;
            }
            foreach ($metadata[$type] as $entry) {
                if ($entry['metadata']['entityid'] === $entityId) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertEquals($present, $found, "Expected {$entityId} presence incorrect");

    }


}
