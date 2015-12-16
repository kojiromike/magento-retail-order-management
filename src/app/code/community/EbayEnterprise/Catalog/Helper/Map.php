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

/**
 * Functions to help import EB2C attributes into Magento product attributes.
 *
 * Each function is passed a DOMNodeList of nodes matching the configured xpath expression and the product object currently being processed.
 *
 * @example: public function prototypicalMapFunction(DOMNodeList $nodes, Mage_Catalog_Model_Product $product);
 *
 * <code>
 * // Return the mapped type_id if the product doesn't already have one.
 * // Otherwise return the product's existing value.
 * public function getTypeIdIfNew(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
 * {
 *   return $product->getTypeId() ?: $nodes->item(0)->nodeValue;
 * }
 * </code>
 */
class EbayEnterprise_Catalog_Helper_Map
{
    const TYPE_GIFTCARD = 'giftcard';

    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /** @var EbayEnterprise_Eb2cCore_Helper_Data */
    protected $coreHelper;
    /** @var EbayEnterprise_Catalog_Helper_Data */
    protected $catalogHelper;

    /**
     * Map ownerDocuments to DomXPath objects to avoid recreating them.
     *
     * @var SplObjectStorage
     */
    protected $_splStorageDocMap = null;
    /**
     * Keep from having to reinstantiate this collection when doing the product imports.
     *
     * @var Mage_Catalog_Model_Resource_Category_Collection
     */
    protected $_categoryCollection = null;

    public function __construct(array $initParams = [])
    {
        list(
            $this->coreHelper,
            $this->catalogHelper,
            $this->logger,
            $this->context
        ) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'core_helper', Mage::helper('eb2ccore')),
            $this->nullCoalesce($initParams, 'catalog_helper', Mage::helper('ebayenterprise_catalog')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Eb2cCore_Helper_Data
     * @param  EbayEnterprise_Catalog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        EbayEnterprise_Eb2cCore_Helper_Data $coreHelper,
        EbayEnterprise_Catalog_Helper_Data $catalogHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    )
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * check if the node list has item and if the first item node value equal to 'active' to return
     * the status for enable otherwise status for disable
     * @param DOMNodeList $nodes
     * @return string
     */
    public function extractStatusValue(DOMNodeList $nodes)
    {
        return ($nodes->length && strtolower($nodes->item(0)->nodeValue) === 'active')?
            Mage_Catalog_Model_Product_Status::STATUS_ENABLED:
            Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
    }
    /**
     * extracts visibility from node list, returning an expected integer or translating an expected string
     * into an expected integer, returning null for unexpected values
     * @param DOMNodeList $nodes
     * @return int|null
     */
    public function extractVisibilityValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        $visibilityValues = [
            'Not Visible Individually' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
            'Catalog' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
            'Search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
            'Catalog, Search' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
        ];

        $visibility = $this->coreHelper->extractNodeVal($nodes);

        if (in_array($visibility, $visibilityValues)) {
            // If the value is an expected integer value, return that value
            return $visibility;
        } elseif (isset($visibilityValues[$visibility])) {
            // If the value is an expected string value, return the associated integer
            return $visibilityValues[$visibility];
        } else {
            // If the value is unexpected, return the current value
            return $product->getVisibility();
        }
    }
    /**
     * extract the first element of a dom node list make sure it is lower case
     * if there's no item in the DOMNodeList return the default simple product type constant value
     *
     * @param DOMNodeList $nodes
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function extractProductTypeValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        $value = strtolower($this->coreHelper->extractNodeVal($nodes));
        $type = ($this->_isValidProductType($value))? $value : Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
        $product->setTypeId($type)
            ->setTypeInstance(Mage_Catalog_Model_Product_Type::factory($product, true), true);
        return $type;
    }

    /**
     * check if a given string is a valid product type value
     * @param string $value the value that must match one of magento product type
     * @return bool true the value match magento product type otherwise false
     */
    protected function _isValidProductType($value)
    {
        return in_array($value, array(
            self::TYPE_GIFTCARD,
            Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
            Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
            Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
            Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        ));
    }

    /**
     * This should produce a serialized array of product links to be handled by
     * the product cleaner. Arrays should consist of
     *
     * @param DOMNodeList $nodes DOM nodes extracted from the feed
     * @return string Serialized array
     */
    public function extractProductLinks(DOMNodeList $nodes)
    {
        $links = array();
        foreach ($nodes as $linkNode) {
            $attrs = $linkNode->attributes;
            try {
                $linkType = $this->_convertToMagentoLinkType($attrs->getNamedItem('link_type')->nodeValue);
            } catch (Mage_Core_Exception $e) {
                // If the link_type in the feed dosn't match a known link type, do not
                // include it and move on to the next link.
                continue;
            }
            $links[] = array(
                'link_type' => $linkType,
                'operation_type' => $attrs->getNamedItem('operation_type')->nodeValue,
                'link_to_unique_id' => Mage::helper('ebayenterprise_catalog')->normalizeSku(
                    trim($linkNode->nodeValue),
                    $this->coreHelper->getConfigModel()->catalogId
                )
            );
        }
        return serialize($links);
    }
    /**
     * Convert the EB2C link types to link types Magento knows about through a
     * mapping in the product config.xml.
     * @param  string $linkType
     * @return string
     * @throws Mage_Core_Exception If the link type is not mapped to a Magento link type
     */
    protected function _convertToMagentoLinkType($linkType)
    {
        return Mage::helper('ebayenterprise_catalog')->getConfigModel()->getConfig(strtolower("link_types_$linkType"));
    }

    /**
     * extract the value in the nodelist and then passed it to helper
     * function to ensure the value has the right info
     * @param DOMNodeList $nodes
     * @return string
     */
    public function extractSkuValue(DOMNodeList $nodes)
    {
        $coreHelper = $this->coreHelper;
        return Mage::helper('ebayenterprise_catalog')->normalizeSku(
            $coreHelper->extractNodeVal($nodes),
            $coreHelper->getConfigModel()->catalogId
        );
    }
    /**
     * extract the the title value from the DOMNodelist object if value not empty
     * simply append the store id and return the vlaue. if the value is empty
     * append the a know string string with the sku and the store id and return
     * @param DOMNodeList $nodes
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function extractUrlKeyValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        $urlKey = $this->coreHelper->extractNodeVal($nodes);
        return ($urlKey !== '')?
            $urlKey . '-' . $product->getStoreId() :
            'Incomplete Product: ' . $product->getSku() . '-' . $product->getStoreId();
    }
    /**
     * given a gift card type return the gift card constant mapped to it
     * @param string $type the gift card type in this set (virtual, physical or combined)
     * @see Enterprise_GiftCard_Model_Giftcard
     * @return int|null
     */
    protected function _getGiftCardType($type)
    {
        switch ($type) {
            case self::GIFTCARD_PHYSICAL:
                return Enterprise_GiftCard_Model_Giftcard::TYPE_PHYSICAL;
            case self::GIFTCARD_COMBINED:
                return Enterprise_GiftCard_Model_Giftcard::TYPE_COMBINED;
            default:
                return Enterprise_GiftCard_Model_Giftcard::TYPE_VIRTUAL;
        }
    }
    /**
     * extract the giftcard tender code from the DOMNOdeList object get its map value
     * from the config and then return the actual constant to the know magento gift card sets
     * @param DOMNodeList $nodes
     * @return int|null integer value a valid tender type was extracted null tender type is not configure
     */
    public function extractGiftcardTenderValue(DOMNodeList $nodes)
    {
        $value = $this->coreHelper->extractNodeVal($nodes);
        $cfg = Mage::helper('ebayenterprise_catalog')->getConfigModel();
        $mapData = $cfg->getConfigData(EbayEnterprise_Catalog_Helper_Feed::GIFTCARD_TENDER_CONFIG_PATH);
        return isset($mapData[$value])? $this->_getGiftCardType($mapData[$value]) : null;
    }

    /**
     * given DOMNodeList object containing htscode data extract the htscode data
     * into an array of array of keys and return a serialize string of the build array of htscode data
     * @param DOMNodeList $nodes
     * @return string a serialize string of array of htscodes
     */
    public function extractHtsCodesValue(DOMNodeList $nodes)
    {
        $htscodes = array();
        foreach ($nodes as $item) {
            $htscodes[] = array(
                'mfn_duty_rate' => $item->getAttribute('mfn_duty_rate'),
                'destination_country' => $item->getAttribute('destination_country'),
                'restricted' => $item->getAttribute('restricted'),
                'hts_code' => $item->nodeValue
            );
        }
        return serialize($htscodes);
    }

    /**
     * extract the attribute set name
     *
     * @param DOMNodeList $nodes
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    public function extractAttributeSetValue(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        $attributeSetName = $this->coreHelper->extractNodeVal($nodes);
        $attributeSetId = Mage::helper('ebayenterprise_catalog')->getAttributeSetIdByName($attributeSetName);
        if (is_null($attributeSetId)) {
            // @todo: move to error confirmation feed
            $logData = ['attribute_set_name' => $attributeSetName];
            $logMessage = 'Attribute Set "{attribute_set_name}" has not yet been setup for this Magento instance.';
            $this->logger->warning($logMessage, $this->context->getMetaData(__CLASS__, $logData));
        }
        return $attributeSetId ?: $product->getAttributeSetId();
    }
    /**
     * extract the first element of a dom node list and return a string value
     * @param DOMNodeList $nodes
     * @return string
     */
    public function extractStringValue(DOMNodeList $nodes)
    {
        return ($nodes->length)? $nodes->item(0)->nodeValue : null;
    }
    /**
     * extract the first element of a dom node list and return a boolean
     * value of the extract string
     * @param DOMNodeList $nodes
     * @return bool
     */
    public function extractBoolValue(DOMNodeList $nodes)
    {
        return $this->coreHelper->parseBool(($nodes->length)? $nodes->item(0)->nodeValue : null);
    }
    /**
     * extract the first element of a dom node list and return the string value cast as integer value
     * @param DOMNodeList $nodes
     * @return int
     */
    public function extractIntValue(DOMNodeList $nodes)
    {
        return ($nodes->length)? (int) $nodes->item(0)->nodeValue : 0;
    }
    /**
     * extract the first element of a dom node list and return the string value cast as float value
     * @param DOMNodeList $nodes
     * @return int
     */
    public function extractFloatValue(DOMNodeList $nodes)
    {
        return ($nodes->length)? (float) $nodes->item(0)->nodeValue : 0;
    }
    /**
     * it return the pass in value parameter
     * it's a callback to return static value set in the config
     * @param mixed $value
     * @return mixed
     */
    public function passThrough($value)
    {
        return $value;
    }
    /**
     * Always return false.
     * This is useful for clearing a value to have it fallback to a higher scope.
     */
    public function extractFalse()
    {
        return false;
    }

    /**
     * return a sum of the data for all elements retrieved by the xpath.
     * @param DOMNodeList $nodes
     * @return float
     */
    public function extractFloatSum(DOMNodeList $nodes)
    {
        $sum = 0.0;
        foreach ($nodes as $node) {
            $sum += (float) $node->nodeValue;
        }
        return $sum;
    }

    /**
     * Return a negative sum of the data for all elements retrieved by the xpath.
     * Used to get a negative amount for discount sums.
     * @param DOMNodeList $nodes
     * @return float
     */
    public function extractDiscountSum(DOMNodeList $nodes)
    {
        return -$this->extractFloatSum($nodes);
    }

    /**
     * extract all custom attributes and set it to the passed in product object.
     *
     * @param  DOMNodeList
     * @param  Mage_Catalog_Model_Product
     * @return null
     */
    public function extractCustomAttributes(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        /** @var DOMElement $node */
        foreach ($nodes as $node) {
            /** @var string $attribute */
            $attribute = trim($node->getAttribute('name'));
            /** @var string $value */
            $value = trim($node->nodeValue);
            if ($attribute && $value) {
                $product->setData($attribute, $value);
            }
        }
    }

    /**
     * If the "ExtendedAttributes/AllowGiftMessage" node exist then we proceed
     * to set the product attribute "use_config_gift_message_available" to false
     * and return the boolean value of the "ExtendedAttributes/AllowGiftMessage" node.
     * Otherwise, if the "ExtendedAttributes/AllowGiftMessage" node doesn't exists
     * we simply return null in order to let the default value be set on the attribute.
     *
     * @param  DOMNodeList
     * @param  Mage_Catalog_Model_Product
     * @return int | null
     */
    public function extractAllowGiftMessage(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        if ($nodes->length) {
            $product->setUseConfigGiftMessageAvailable(0);
            // Explicitly casting the extracted boolean value as an integer value because
            // Magento only work with integer value 0 or 1 representing boolean value in the database.
            return (int) $this->coreHelper->parseBool(($nodes->length)? $nodes->item(0)->nodeValue : null);
        }
        return null;
    }

    /**
     * Map an array of website ids the product should be linked to, based upon
     * the item's client id, catalog id and/or store id.
     *
     * @param DOMNodeList
     * @param Mage_Catalog_Model_Product
     * @return array
     */
    public function extractWebsiteIds(DOMNodeList $nodes, Mage_Catalog_Model_Product $product)
    {
        $websiteIds = $product->getWebsiteIds();

        $itemNode = $nodes->item(0);

        // If there's no node to get data from, just let website ids remain
        // what it is.
        if (!$itemNode) {
            return $websiteIds;
        }

        $clientId = $itemNode->getAttribute('gsi_client_id');
        $catalogId = $itemNode->getAttribute('catalog_id');
        $storeId = $itemNode->getAttribute('gsi_store_id');

        $websites = $this->catalogHelper->loadWebsiteFilters();

        foreach ($websites as $websiteFilter) {

            $websiteId = $websiteFilter['mage_website_id'];
            // Assume no value in feed is a wildcard.
            $matches = !$catalogId || $catalogId === $websiteFilter['catalog_id'];
            $matches = $matches && (!$clientId || $clientId === $websiteFilter['client_id']);
            $matches = $matches && (!$storeId || $storeId === $websiteFilter['store_id']);

            // Just add any matched websites to the list, ignoring that this
            // might end up with dupes, list will be made unique when returned.
            if ($matches) {
                $websiteIds[] = $websiteId;
            }

        }

        return array_unique($websiteIds);
    }
}
