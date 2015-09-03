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
 * Abstract index process class
 * Predefine list of methods required by indexer
 */
class EbayEnterprise_Catalog_Model_Indexer_Stub extends Mage_Index_Model_Indexer
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /**
     * @param array
     */
    public function __construct(array $args = [])
    {
        list($this->_logger, $this->_context) = $this->_checkTypes(
            $this->_nullCoalesce($args, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce($args, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type checks for constructor args array.
     *
     * @param EbayEnterprise_MageLog_Helper_Data
     */
    protected function _checkTypes(
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     * @param array      $arr
     * @param string|int $field Valid array key
     * @param mixed      $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Stub the processEntityAction method from Mage_Index_Model_Indexer
     * to prevent any lockage.
     */
    public function processEntityAction(Varien_Object $entity, $entityType, $eventType)
    {
        $this->_logger->debug("Stubbed Indexer Skipping Reindex of $entityType, $eventType", $this->_context->getMetaData(__CLASS__));
    }
}
