<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_InfoCard
 * @subpackage Zend_InfoCard_Xml_Security
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Transform.php 9094 2008-03-30 18:36:55Z thomas $
 */

require_once 'Zend_InfoCard_Xml_Security_Transform_EnvelopedSignature.php';
require_once 'Zend_InfoCard_Xml_Security_Transform_XmlExcC14N.php';

/**
 * A class to create a transform rule set based on XML URIs and then apply those rules
 * in the correct order to a given XML input
 *
 * @category   Zend
 * @package    Zend_InfoCard
 * @subpackage Zend_InfoCard_Xml_Security
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_InfoCard_Xml_Security_Transform
{
    /**
     * A list of transforms to apply
     *
     * @var array
     */
    protected $_transformList = array();

    /**
     * Returns the name of the transform class based on a given URI
     *
     * @throws Exception
     * @param string $uri The transform URI
     * @return string The transform implementation class name
     */
    protected function _findClassbyURI($uri)
    {
        switch($uri) {
            case 'http://www.w3.org/2000/09/xmldsig#enveloped-signature':
                return 'Zend_InfoCard_Xml_Security_Transform_EnvelopedSignature';
            case 'http://www.w3.org/2001/10/xml-exc-c14n#':
                return 'Zend_InfoCard_Xml_Security_Transform_XmlExcC14N';
            default:
                SimpleSAML_Logger::debug("Unknown or Unsupported Transformation Requested");
        }
    }

    /**
     * Add a Transform URI to the list of transforms to perform
     *
     * @param string $uri The Transform URI
     * @return Zend_InfoCard_Xml_Security_Transform
     */
    public function addTransform($uri)
    {
        $class = $this->_findClassbyURI($uri);

        $this->_transformList[] = array('uri' => $uri,
                                        'class' => $class);
        return $this;
    }

    /**
     * Return the list of transforms to perform
     *
     * @return array The list of transforms
     */
    public function getTransformList()
    {
        return $this->_transformList;
    }

    /**
     * Apply the transforms in the transform list to the input XML document
     *
     * @param string $strXmlDocument The input XML
     * @return string The XML after the transformations have been applied
     */
    public function applyTransforms($strXmlDocument)
    {
        $transformer = null;
        foreach($this->_transformList as $transform) {
            switch($transform['class']) {
              case 'Zend_InfoCard_Xml_Security_Transform_EnvelopedSignature':
                  $transformer = new Zend_InfoCard_Xml_Security_Transform_EnvelopedSignature();
                  break;
              case 'Zend_InfoCard_Xml_Security_Transform_XmlExcC14N':
                  $transformer = new Zend_InfoCard_Xml_Security_Transform_XmlExcC14N();
                  break;
            }

            $strXmlDocument = $transformer->transform($strXmlDocument);
        }

        return $strXmlDocument;
    }
}
