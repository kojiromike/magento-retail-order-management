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

class EbayEnterprise_Inventory_Override_Block_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
    /** @var EbayEnterprise_Inventory_Helper_Data */
    protected $inventoryHelper;
    /** @var EbayEnterprise_Inventory_Model_Details_Service */
    protected $detailService;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $inventoryConfig;

    public function __construct(array $initParams = [])
    {
        list($this->inventoryHelper, $this->detailService, $this->inventoryConfig) = $this->_checkTypes(
            $this->_nullCoalesce($initParams, 'inventory_helper', Mage::helper('ebayenterprise_inventory')),
            $this->_nullCoalesce($initParams, 'detail_service', Mage::getSingleton('ebayenterprise_inventory/details_service')),
            $this->_nullCoalesce($initParams, 'inventory_config', Mage::helper('ebayenterprise_inventory')->getConfigModel())
        );
        parent::__construct($this->_removeKnownKeys($initParams));
    }

    /**
     * Populate a new array with keys that not in the array of known keys.
     *
     * @param  array
     * @return array
     */
    protected function _removeKnownKeys(array $initParams)
    {
        $newParams = [];
        $knownKeys = ['inventory_helper', 'detail_service', 'inventory_config'];
        foreach ($initParams as $key => $value) {
            if (!in_array($key, $knownKeys)) {
                $newParams[$key] = $value;
            }
        }
        return $newParams;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Inventory_Helper_Data
     * @param  EbayEnterprise_Inventory_Model_Details_Service
     * @param  EbayEnterprise_Eb2cCore_Model_Config_Registry
     * @return array
     */
    protected function _checkTypes(
        EbayEnterprise_Inventory_Helper_Data $inventoryHelper,
        EbayEnterprise_Inventory_Model_Details_Service $detailService,
        EbayEnterprise_Eb2cCore_Model_Config_Registry $inventoryConfig
    ) {
        return [$inventoryHelper, $detailService, $inventoryConfig];
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Get an estimated delivery message for a quote item.
     *
     * @return string
     */
    public function getEddMessage()
    {
        /** @var Mage_Sales_Model_Quote_Item $item */
        $item = $this->getItem();
        /** @var string $singularOrPluralItem */
        $singularOrPluralItem = ((int) $item->getQty() > 1) ? 's' : '';
        /** @var EbayEnterprise_Inventory_Model_Details_Item | Varien_Object | null $eddItem */
        $eddItem = $this->detailService->getDetailsForItem($item)
            ?: $this->inventoryHelper->getStreetDateForBackorderableItem($item);
        return $eddItem ? $this->inventoryHelper->__(
            $this->inventoryConfig->estimatedDeliveryTemplate,
            $singularOrPluralItem,
            $eddItem->getDeliveryWindowFromDate()->format('m/d/y'),
            $eddItem->getDeliveryWindowToDate()->format('m/d/y')
        ) : '';
    }
}
