<?php

namespace SimpleSAML\Module\statistics\Statistics\FieldPresentation;

class Entity extends Base
{
    public function getPresentation()
    {
        $mh = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler();
        $metadata = $mh->getList($this->config);

        $translation = array('_' => 'All services');
        foreach ($this->fields as $field) {
            if (array_key_exists($field, $metadata)) {
                if (array_key_exists('name', $metadata[$field])) {
                    $translation[$field] = $this->template->t($metadata[$field]['name'], array(), false);
                }
            }
        }
        return $translation;
    }
}
