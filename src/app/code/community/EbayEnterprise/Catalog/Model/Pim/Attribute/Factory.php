<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class EbayEnterprise_Catalog_Model_Pim_Attribute_Factory
{
    // Config path to PIM Attribute Mappings
    const CONFIG_PATH_ATTRIBUTE_MAPPINGS = 'ebayenterprise_catalog/feed_pim_mapping';
    // Config path to the default PIM attribute mapping
    const CONFIG_PATH_DEFAULT_MAPPING = 'ebayenterprise_catalog/default_pim_mapping';
    /**
     * Mapping of attribute codes to specific handling for the attirbute
     * @var array
     */
    protected $_attributeMappings = array();
    /**
     * Default config mapping to use for any product attribute that does not
     * have an explicit mapping in the config.xml
     * @var array
     */
    protected $_defaultMapping = array();
    /**
     * Load the attribute mappins from config.xml and store them.
     */
    public function __construct()
    {
        $cfg = Mage::helper('ebayenterprise_catalog')->getConfigModel();
        $this->_attributeMappings = $cfg
            ->getConfigData(self::CONFIG_PATH_ATTRIBUTE_MAPPINGS);
        $this->_defaultMapping = $cfg
            ->getConfigData(self::CONFIG_PATH_DEFAULT_MAPPING);
    }
    /**
     * Get a new PIM Attribute model for the given product attribute and product
     * @param  string $attribute
     * @param  Mage_Catalog_Model_Product          $product
     * @param  EbayEnterprise_Dom_Document             $doc
     * @param  array $key
     * @return EbayEnterprise_Catalog_Model_Pim_Attribute
     */
    public function getPimAttribute(
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc,
        array $config
    ) {

        $resolvedArgs = $this->_resolveMappedCallback(
            $this->_getAttributeMapping($attribute, $config),
            $attribute,
            $product,
            $doc
        );

        return $resolvedArgs ? Mage::getModel('ebayenterprise_catalog/pim_attribute', $resolvedArgs) : null;
    }
    /**
     * Get the attribute mapping. The result will be an empty array.
     * @param  string $attribute
     * @param  string $key
     * @return array
     */
    protected function _getAttributeMapping($attribute, $config)
    {
        return isset($config['mappings'][$attribute]) ? $config['mappings'][$attribute] : array();
    }

    /**
     * Lookup the callable described in the mapping xml to export this attribute for this product.
     *
     * @param array $callbackMapping
     * @param $attribute
     * @param Mage_Catalog_Model_Product $product
     * @param EbayEnterprise_Dom_Document $doc
     * @return array|null
     */
    protected function _resolveMappedCallback(
        array $callbackMapping = array(),
        $attribute,
        Mage_Catalog_Model_Product $product,
        EbayEnterprise_Dom_Document $doc
    ) {
        // type "disabled" callbacks should always just return null
        if (empty($callbackMapping) || $callbackMapping['type'] === 'disabled') {
            return null;
        }
        $callbackMapping['parameters'] = array(
            $product->getDataUsingMethod($attribute),
            $attribute,
            $product,
            $doc
        );
        return $this->_createPimAttributeArgs(
            $callbackMapping,
            Mage::helper('eb2ccore')->invokeCallback($callbackMapping),
            $product
        );
    }
    /**
     * Create the argument array to be passed to the constructor when creating
     * a new PIM Attribute model.
     * @param  array                      $callbackMapping
     * @param  DOMNode                    $pimAttributeValue
     * @param  Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function _createPimAttributeArgs(
        array $callbackMapping,
        DOMNode $pimAttributeValue = null,
        Mage_Catalog_Model_Product $product
    ) {
        return array(
            'destination_xpath' => $callbackMapping['xml_dest'],
            'sku' => $product->getSku(),
            'language' => $callbackMapping['translate'] ?
                $product->getPimLanguageCode() :
                null,
            'value' => $pimAttributeValue,
        );
    }
}
